<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PortalController
{
    public function login(Request $request)
    {
        $credentials = $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        if (!Auth::attempt($credentials)) {
            throw ValidationException::withMessages(['email' => 'Check your email and password.']);
        }
        $request->session()->regenerate();
        return redirect()->route('home');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    private function accounts(User $user)
    {
        $query = DB::table('accounts')->where('accounts.school_id', $user->school_id);
        if ($user->role === 'parent') {
            $query->where('accounts.parent_id', $user->id);
        }
        return $query;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $school = DB::table('schools')->where('id', $user->school_id)->value('name');
        $filters = $request->validate(['q' => 'nullable|string|max:120']);
        $search = trim($filters['q'] ?? '');
        $visible = $this->accounts($user);
        if ($search !== '') {
            $visible->whereRaw('strpos(lower(accounts.student), lower(?)) > 0', [$search]);
        }
        $accounts = (clone $visible)->select('accounts.*')
            ->selectSub(DB::table('payments')->selectRaw('coalesce(sum(amount_paise),0)')
                ->whereColumn('account_id', 'accounts.id'), 'paid')
            ->orderBy('student')->get();
        $payments = DB::table('payments')->whereIn('account_id', $visible->select('id'))
            ->join('accounts', 'accounts.id', '=', 'payments.account_id')
            ->select('payments.*', 'accounts.student')->orderByDesc('payments.id')->paginate(15)->withQueryString();
        return view('home', compact('accounts', 'payments', 'school', 'user', 'search'));
    }

    public function createStudent(Request $request)
    {
        $user = $request->user();
        abort_unless($user->role === 'accountant', 403);
        $parents = User::where('school_id', $user->school_id)->where('role', 'parent')
            ->orderBy('email')->get(['id', 'name', 'email']);
        return view('student-create', compact('parents'));
    }

    public function storeStudent(Request $request)
    {
        $user = $request->user();
        abort_unless($user->role === 'accountant', 403);
        $data = $request->validate([
            'student' => ['required', 'string', 'max:120'],
            'parent_id' => ['required', 'integer', Rule::exists('users', 'id')
                ->where('school_id', $user->school_id)->where('role', 'parent')],
            'fee' => ['required', 'string', 'regex:/^[0-9]{1,8}(?:\.[0-9]{1,2})?$/D'],
        ]);
        $fee = $this->toPaise($data['fee']);
        if ($fee < 1) {
            throw ValidationException::withMessages(['fee' => 'Enter a fee greater than zero.']);
        }
        DB::table('accounts')->insert([
            'school_id' => $user->school_id, 'parent_id' => $data['parent_id'],
            'student' => $data['student'], 'fee_paise' => $fee,
        ]);
        return redirect()->route('home', ['q' => $data['student']])->with('status', 'Student added.');
    }

    private function toPaise(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount), 2, '');
        return (int) $whole * 100 + (int) str_pad($fraction, 2, '0');
    }

    public function pay(Request $request, int $account)
    {
        $user = $request->user();
        abort_unless($user->role === 'accountant', 403);
        $data = $request->validate([
            'amount' => ['required', 'string', 'regex:/^[0-9]{1,8}(?:\.[0-9]{1,2})?$/D'],
            'request_key' => 'required|uuid',
        ]);
        $paise = $this->toPaise($data['amount']);
        $id = DB::transaction(function () use ($user, $account, $data, $paise) {
            $row = $this->accounts($user)->where('id', $account)->lockForUpdate()->first();
            abort_unless($row, 404);
            $ledger = DB::table('payments')->where('account_id', $account);
            $prior = (clone $ledger)->where('request_key', $data['request_key'])->first();
            if ($prior) {
                abort_if((int) $prior->amount_paise !== $paise, 409, 'This submission already has a different amount.');
                return $prior->id;
            }
            $balance = $row->fee_paise - $ledger->sum('amount_paise');
            if ($paise < 1 || $paise > $balance) {
                throw ValidationException::withMessages(['amount' => 'Enter a positive amount within the remaining balance.']);
            }
            return DB::table('payments')->insertGetId([
                'school_id' => $user->school_id, 'account_id' => $account, 'posted_by' => $user->id,
                'amount_paise' => $paise, 'request_key' => $data['request_key'],
            ]);
        });
        return redirect()->route('home')->with('status', "Payment recorded. Receipt R-$id is available below.");
    }

    public function receipt(Request $request, int $payment)
    {
        $row = $this->accounts($request->user())
            ->join('payments', 'payments.account_id', '=', 'accounts.id')
            ->join('schools', 'schools.id', '=', 'accounts.school_id')
            ->where('payments.id', $payment)
            ->first(['schools.name', 'accounts.student', 'payments.amount_paise', 'payments.created_at']);
        abort_unless($row, 404);
        $amount = number_format($row->amount_paise / 100, 2);
        $body = "$row->name\nSchool fee receipt R-$payment\n\nStudent: $row->student\nAmount received: INR $amount\nRecorded: $row->created_at\n";
        return response($body)->header('Content-Type', 'text/plain; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"receipt-R-$payment.txt\"")
            ->header('Cache-Control', 'private, no-store');
    }
}
