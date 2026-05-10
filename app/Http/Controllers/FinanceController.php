<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\TrackerAccount;
use App\Models\TrackerExpense;

class FinanceController extends Controller
{
    public function overview()
    {
        return view('finance.overview');
    }

    public function bankAccounts()
    {
        $accounts = TrackerAccount::all();
        return view('finance.bank-accounts', compact('accounts'));
    }

    public function storeBankAccount(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'type' => 'required|in:personal,company',
            'balance' => 'required|numeric'
        ]);

        TrackerAccount::create($validated);
        return redirect()->back()->with('success', 'Akun berhasil ditambahkan.');
    }

    public function updateBankAccount(Request $request, $id)
    {
        $account = TrackerAccount::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string',
            'type' => 'required|in:personal,company',
            'balance' => 'required|numeric'
        ]);

        $account->update($validated);
        return redirect()->back()->with('success', 'Akun berhasil diperbarui.');
    }

    public function deleteBankAccount($id)
    {
        TrackerAccount::destroy($id);
        return redirect()->back()->with('success', 'Akun berhasil dihapus.');
    }

    public function transactions()
    {
        $expenses = TrackerExpense::with(['account', 'category'])->latest('expense_date')->paginate(15);
        return view('finance.transactions', compact('expenses'));
    }
}
