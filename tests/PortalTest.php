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
        $this->signIn('parent@cedar.test');
        $this->get('/')->assertOk()->assertSee('₹7,000.00');
        $this->get("/receipts/$id")->assertOk()->assertSee('INR 5,000.00')
            ->assertHeader('Content-Disposition', "attachment; filename=\"receipt-R-$id.txt\"");
        $this->post($url, $form)->assertForbidden();
        $this->signIn('parent2@cedar.test');
        $this->get("/receipts/$id")->assertNotFound();
        $this->signIn('accountant@willow.test');
        $this->get('/')->assertOk()->assertDontSee('Mina Shah');
        $this->get("/receipts/$id")->assertNotFound();
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
