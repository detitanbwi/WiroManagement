<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Shareholder;
use App\Models\ShareholderCapitalHistory;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShareholderController extends Controller
{
    /**
     * Display a listing of shareholders and equity distribution overview.
     */
    public function index(Request $request): View
    {
        $shareholders = Shareholder::with(['user', 'capitalHistories'])
            ->orderBy('is_active', 'desc')
            ->orderBy('ownership_percentage', 'desc')
            ->paginate(15);

        $allShareholders = Shareholder::all();
        $totalSharesCount = (int)$allShareholders->sum('shares_count');
        $totalOwnershipPercentage = (float)$allShareholders->where('is_active', true)->sum('ownership_percentage');
        $totalActiveCount = $allShareholders->where('is_active', true)->count();
        $totalCapitalAmount = (float)$allShareholders->where('is_active', true)->sum('capital_amount');
        $internalUsers = User::where('is_active', true)->orderBy('name')->get();

        $recentCapitalHistories = ShareholderCapitalHistory::with(['shareholder', 'quarterlyClosing'])
            ->latest('id')
            ->take(20)
            ->get();

        return view('finance.shareholders.index', compact(
            'shareholders',
            'totalSharesCount',
            'totalOwnershipPercentage',
            'totalActiveCount',
            'totalCapitalAmount',
            'internalUsers',
            'recentCapitalHistories'
        ));
    }

    /**
     * Store a newly created shareholder in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:shareholders,email',
            'phone' => 'nullable|string|max:50',
            'shares_count' => 'required|integer|min:0',
            'capital_amount' => 'nullable|numeric|min:0',
            'ownership_percentage' => 'required|numeric|between:0,100',
            'bank_name' => 'required|string|max:100',
            'bank_account_number' => 'required|string|max:100',
            'bank_account_holder' => 'required|string|max:255',
            'user_id' => 'nullable|exists:users,id',
            'is_active' => 'nullable|boolean',
        ], [
            'name.required' => 'Nama pemegang saham wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email ini sudah terdaftar sebagai pemegang saham.',
            'shares_count.required' => 'Jumlah lembar saham wajib diisi.',
            'ownership_percentage.required' => 'Persentase kepemilikan modal wajib diisi.',
            'ownership_percentage.between' => 'Persentase kepemilikan harus di antara 0% sampai 100%.',
            'bank_name.required' => 'Nama bank wajib diisi.',
            'bank_account_number.required' => 'Nomor rekening bank wajib diisi.',
            'bank_account_holder.required' => 'Nama pemilik rekening wajib diisi.',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $capitalAmount = (float)($validated['capital_amount'] ?? 0);
        $validated['capital_amount'] = $capitalAmount;

        $shareholder = Shareholder::create($validated);

        if ($capitalAmount > 0) {
            $currentQuarter = date('Y') . '-Q' . ceil(date('n') / 3);
            ShareholderCapitalHistory::create([
                'shareholder_id' => $shareholder->id,
                'transaction_type' => 'initial_balance',
                'effective_quarter_label' => $currentQuarter,
                'amount' => $capitalAmount,
                'opening_capital' => 0,
                'closing_capital' => $capitalAmount,
                'opening_shares_count' => 0,
                'closing_shares_count' => $shareholder->shares_count,
                'opening_ownership_percentage' => 0,
                'closing_ownership_percentage' => $shareholder->ownership_percentage,
                'notes' => 'Saldo modal disetor awal pendiri',
            ]);
        }

        return redirect()->back()->with('success', "Pemegang saham {$shareholder->name} ({$shareholder->ownership_percentage}%) berhasil ditambahkan.");
    }

    /**
     * Update the specified shareholder in storage.
     */
    public function update(Request $request, Shareholder $shareholder): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:shareholders,email,' . $shareholder->id,
            'phone' => 'nullable|string|max:50',
            'shares_count' => 'required|integer|min:0',
            'capital_amount' => 'nullable|numeric|min:0',
            'ownership_percentage' => 'required|numeric|between:0,100',
            'bank_name' => 'required|string|max:100',
            'bank_account_number' => 'required|string|max:100',
            'bank_account_holder' => 'required|string|max:255',
            'user_id' => 'nullable|exists:users,id',
            'is_active' => 'nullable|boolean',
        ], [
            'name.required' => 'Nama pemegang saham wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email ini sudah terdaftar sebagai pemegang saham.',
            'shares_count.required' => 'Jumlah lembar saham wajib diisi.',
            'ownership_percentage.required' => 'Persentase kepemilikan modal wajib diisi.',
            'ownership_percentage.between' => 'Persentase kepemilikan harus di antara 0% sampai 100%.',
            'bank_name.required' => 'Nama bank wajib diisi.',
            'bank_account_number.required' => 'Nomor rekening bank wajib diisi.',
            'bank_account_holder.required' => 'Nama pemilik rekening wajib diisi.',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        if (array_key_exists('capital_amount', $validated)) {
            $validated['capital_amount'] = (float)($validated['capital_amount'] ?? 0);
        }

        $shareholder->update($validated);

        return redirect()->back()->with('success', "Data pemegang saham {$shareholder->name} berhasil diperbarui.");
    }

    /**
     * Remove the specified shareholder from storage.
     */
    public function destroy(Shareholder $shareholder): RedirectResponse
    {
        // Safety guard: check if dividend payout has already been transferred
        if ($shareholder->payouts()->where('payment_status', 'transferred')->exists()) {
            return redirect()->back()->with('error', "Pemegang saham {$shareholder->name} tidak dapat dihapus karena sudah memiliki riwayat pencairan dividen yang ditransfer. Silakan non-aktifkan statusnya.");
        }

        $name = $shareholder->name;
        $shareholder->delete();

        return redirect()->back()->with('success', "Pemegang saham {$name} berhasil dihapus dari sistem.");
    }
}
