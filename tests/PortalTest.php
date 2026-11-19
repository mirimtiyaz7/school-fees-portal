<?php
namespace Tests;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function signIn(string $email): void
    {
        $this->actingAs(User::where('email', $email)->firstOrFail());
    }

    private function endpoint(): string
    {
        $account = DB::table('accounts')->where('student', 'Mina Shah')->value('id');
        return "/accounts/$account/payments";
    }

    public function test_login_and_logout(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->post('/login', ['email' => 'parent@cedar.test', 'password' => 'SchoolDemo2026!'])->assertRedirect('/');
        $this->get('/')->assertOk()->assertSee('Mina Shah')->assertDontSee('Ravi Noor');
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_accountant_can_add_a_student_and_parent_can_search_by_partial_name(): void
    {
        $this->signIn('accountant@cedar.test');
        $this->get('/students/create')->assertOk()->assertSee('parent@cedar.test')->assertDontSee('parent@willow.test');
        $parent = User::where('email', 'parent@cedar.test')->firstOrFail();
        $this->post('/students', ['student' => 'Sara Mir', 'parent_id' => $parent->id, 'fee' => '1299.25'])
            ->assertRedirect(route('home', ['q' => 'Sara Mir']));
        $this->assertDatabaseHas('accounts', ['student' => 'Sara Mir', 'fee_paise' => 129925, 'parent_id' => $parent->id]);
        $this->signIn('parent@cedar.test');
        $this->get('/?q=ARA')->assertOk()->assertSee('Sara Mir')->assertDontSee('Mina Shah');
        $this->signIn('parent2@cedar.test');
        $this->get('/?q=sara')->assertOk()->assertSee('No matching students found.');
    }

    public function test_student_creation_requires_an_accountant_and_a_parent_at_the_same_school(): void
    {
        $this->signIn('accountant@cedar.test');
        $form = ['student' => 'Sara Mir', 'fee' => '100'];
        foreach (['parent@willow.test', 'accountant@cedar.test'] as $email) {
            $form['parent_id'] = User::where('email', $email)->value('id');
            $this->post('/students', $form)->assertSessionHasErrors('parent_id');
        }
        $this->signIn('parent@cedar.test');
        $this->get('/students/create')->assertForbidden();
        $this->post('/students', $form)->assertForbidden();
        $this->assertDatabaseMissing('accounts', ['student' => 'Sara Mir']);
    }

    public function test_partial_payment_retry_and_receipt_visibility(): void
    {
        $this->signIn('accountant@cedar.test');
        $url = $this->endpoint();
        $form = ['request_key' => (string) Str::uuid(), 'amount' => '5000'];
        $this->post($url, $form)->assertRedirect('/');
        $this->post($url, $form)->assertRedirect('/');
        $this->assertDatabaseCount('payments', 1);
        $this->post($url, array_merge($form, ['amount' => '1']))->assertStatus(409);
        $id = DB::table('payments')->value('id');
        $this->post($url, ['request_key' => (string) Str::uuid(), 'amount' => '250'])->assertRedirect('/');
        $this->signIn('parent@cedar.test');
        $this->get('/')->assertOk()->assertSee('₹6,750.00');
        $this->get("/receipts/$id/print")->assertOk()->assertSee('INR 7,000.00')->assertDontSee('INR 6,750.00');
        $pdf = $this->get("/receipts/$id/pdf")->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        $this->get("/receipts/$id")->assertOk()->assertSee('INR 5,000.00')
            ->assertHeader('Content-Disposition', "attachment; filename=\"receipt-R-$id.txt\"");
        $this->post($url, $form)->assertForbidden();
        $this->signIn('parent2@cedar.test');
        $this->get("/receipts/$id")->assertNotFound();
        $this->get("/receipts/$id/print")->assertNotFound();
        $this->get("/receipts/$id/pdf")->assertNotFound();
        $this->signIn('accountant@willow.test');
        $this->get('/')->assertOk()->assertDontSee('Mina Shah');
        $this->get("/receipts/$id")->assertNotFound();
        $this->get("/receipts/$id/print")->assertNotFound();
        $this->get("/receipts/$id/pdf")->assertNotFound();
        $this->post($url, $form)->assertNotFound();
    }

    public function test_amount_validation_and_final_paise(): void
    {
        $this->signIn('accountant@cedar.test');
        foreach (['0', '-1', '12000.01', '4.123'] as $amount) {
            $this->post($this->endpoint(), ['request_key' => (string) Str::uuid(), 'amount' => $amount])
                ->assertSessionHasErrors('amount');
        }
        foreach (['11999.99', '0.01'] as $amount) {
            $this->post($this->endpoint(), ['request_key' => (string) Str::uuid(), 'amount' => $amount])
                ->assertSessionHasNoErrors()->assertRedirect('/');
        }
        $this->assertEquals(1200000, DB::table('payments')->sum('amount_paise'));
    }
}
