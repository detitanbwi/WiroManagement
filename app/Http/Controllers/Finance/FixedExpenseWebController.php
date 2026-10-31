<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FixedExpense;
use App\Models\FixedExpenseRealization;
use App\Services\Finance\FixedExpenseService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class FixedExpenseWebController extends Controller
{
    public function __construct(
        protected FixedExpenseService $fixedExpenseService
    ) {}

    /**
     * Display a listing of monthly fixed expense realizations and master expenses.
     */
    public function index(Request $request): View
    {
        $month = $request->input('month');
        if (!$month || !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            $month = Carbon::now()->format('Y-m');
        }

        // 1. Get Monthly Financial Summary & KPIs
        $summary = $this->fixedExpenseService->getMonthlySummary($month);

        // 2. Realization checklist for selected month
        $realizations = FixedExpenseRealization::where('month_period', $month)
            ->with('fixedExpense')
            ->get()
            ->sortBy(function ($r) {
                return $r->fixedExpense?->due_day_of_month ?? 99;
            })
            ->values();

        // 3. Master pos pengeluaran tetap
        $fixedExpenses = FixedExpense::orderBy('is_active', 'desc')
            ->orderBy('due_day_of_month', 'asc')
            ->get();

        $categories = ['OPEX', 'UTILITY', 'SUBSCRIPTION', 'PAYROLL', 'INFRASTRUCTURE'];
        $paymentMethods = [
            'Bank Transfer',
            'Auto-Debit',
            'Credit Card',
            'Corporate Cash',
            'Virtual Account',
        ];

        return view('finance.fixed-expenses.index', compact(
            'summary',
            'realizations',
            'fixedExpenses',
            'month',
            'categories',
            'paymentMethods'
        ));
    }

    /**
     * Store a newly created master fixed expense.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|in:OPEX,UTILITY,SUBSCRIPTION,PAYROLL,INFRASTRUCTURE,opex,utility,subscription,payroll,infrastructure',
            'amount' => 'required|numeric|gt:0',
            'due_day_of_month' => 'required|integer|between:1,31',
            'payment_method' => 'required|string|max:100',
            'vendor' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
        ], [
            'name.required' => 'Nama pengeluaran tetap wajib diisi.',
            'amount.required' => 'Nominal pengeluaran wajib diisi.',
            'amount.gt' => 'Nominal pengeluaran harus lebih besar dari 0.',
            'due_day_of_month.required' => 'Tanggal jatuh tempo wajib diisi.',
            'due_day_of_month.between' => 'Tanggal jatuh tempo harus di antara tanggal 1 sampai 31.',
            'payment_method.required' => 'Metode pembayaran wajib diisi.',
        ]);

        $validated['category'] = strtoupper($validated['category']);
        $validated['is_active'] = $request->boolean('is_active', true);

        $this->fixedExpenseService->createFixedExpense($validated);

        return redirect()->back()->with('success', 'Master pos pengeluaran tetap berhasil ditambahkan.');
    }

    /**
     * Update the specified master fixed expense.
     */
    public function update(Request $request, FixedExpense $fixedExpense): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|in:OPEX,UTILITY,SUBSCRIPTION,PAYROLL,INFRASTRUCTURE,opex,utility,subscription,payroll,infrastructure',
            'amount' => 'required|numeric|gt:0',
            'due_day_of_month' => 'required|integer|between:1,31',
            'payment_method' => 'required|string|max:100',
            'vendor' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
        ], [
            'name.required' => 'Nama pengeluaran tetap wajib diisi.',
            'amount.required' => 'Nominal pengeluaran wajib diisi.',
            'amount.gt' => 'Nominal pengeluaran harus lebih besar dari 0.',
            'due_day_of_month.between' => 'Tanggal jatuh tempo harus di antara tanggal 1 sampai 31.',
        ]);

        $validated['category'] = strtoupper($validated['category']);
        $validated['is_active'] = $request->boolean('is_active');

        $this->fixedExpenseService->updateFixedExpense($fixedExpense, $validated);

        return redirect()->back()->with('success', 'Master pos pengeluaran tetap berhasil diperbarui.');
    }

    /**
     * Remove the specified master fixed expense.
     */
    public function destroy(FixedExpense $fixedExpense): RedirectResponse
    {
        $this->fixedExpenseService->deleteFixedExpense($fixedExpense);

        return redirect()->back()->with('success', 'Master pos pengeluaran tetap berhasil dihapus.');
    }

    /**
     * Trigger monthly cycle generation for active fixed expenses.
     */
    public function generateCycle(Request $request): RedirectResponse
    {
        $request->validate([
            'month' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
        ], [
            'month.required' => 'Bulan periode wajib diisi.',
            'month.regex' => 'Format bulan harus berupa YYYY-MM.',
        ]);

        $month = $request->input('month');
        $this->fixedExpenseService->generateMonthlyRealizations($month);

        return redirect()->route('finance.fixed-expenses.index', ['month' => $month])
            ->with('success', "Siklus tagihan pengeluaran tetap untuk periode {$month} berhasil digenerate.");
    }

    /**
     * Mark a fixed expense realization as PAID with optional receipt file upload.
     */
    public function markPaid(Request $request, FixedExpenseRealization $realization): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => 'nullable|numeric|gt:0',
            'payment_date' => 'nullable|date',
            'receipt_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:5120',
            'receipt_url' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:2000',
        ], [
            'amount.gt' => 'Nominal pembayaran harus lebih besar dari 0.',
            'receipt_file.mimes' => 'Bukti bayar harus berupa berkas PDF, JPG, JPEG, PNG, atau WEBP.',
            'receipt_file.max' => 'Ukuran berkas bukti bayar maksimal 5 MB.',
        ]);

        $amount = (float) (
            $request->input('amount') ??
            $realization->fixedExpense?->amount ??
            0
        );

        $receiptUrl = null;
        if ($request->hasFile('receipt_file')) {
            $path = $request->file('receipt_file')->store('receipts', 'public');
            $receiptUrl = '/storage/' . $path;
        } elseif ($request->filled('receipt_url')) {
            $receiptUrl = $request->input('receipt_url');
        } elseif ($realization->receipt_url) {
            $receiptUrl = $realization->receipt_url;
        }

        $paymentDate = $request->input('payment_date') ?? Carbon::now()->toDateString();

        if ($request->filled('notes')) {
            $realization->notes = $request->input('notes');
            $realization->save();
        }

        $this->fixedExpenseService->markAsPaid(
            $realization,
            $amount,
            $paymentDate,
            $receiptUrl
        );

        $periodMonth = $realization->month_period;

        return redirect()->route('finance.fixed-expenses.index', ['month' => $periodMonth])
            ->with('success', "Pembayaran tagihan {$realization->fixedExpense?->name} berhasil dicatat sebagai LUNAS.");
    }
}
