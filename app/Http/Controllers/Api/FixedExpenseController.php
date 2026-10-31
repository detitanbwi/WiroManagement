<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreFixedExpenseRequest;
use App\Http\Requests\Finance\UpdateFixedExpenseRequest;
use App\Models\FixedExpense;
use App\Models\FixedExpenseRealization;
use App\Services\Finance\FixedExpenseService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FixedExpenseController extends Controller
{
    public function __construct(
        protected FixedExpenseService $fixedExpenseService
    ) {}

    /**
     * Display a listing of fixed expenses with optional category and is_active filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = FixedExpense::query();

        if ($request->filled('category')) {
            $query->where('category', strtoupper($request->category));
        }

        if ($request->has('is_active') && $request->is_active !== null && $request->is_active !== '') {
            $isActive = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($isActive !== null) {
                $query->where('is_active', $isActive);
            }
        }

        $expenses = $query->orderBy('due_day_of_month', 'asc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar pengeluaran tetap berhasil diambil.',
            'data' => $expenses,
        ]);
    }

    /**
     * Store a newly created fixed expense.
     */
    public function store(StoreFixedExpenseRequest $request): JsonResponse
    {
        $expense = $this->fixedExpenseService->createFixedExpense($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Pengeluaran tetap berhasil dibuat.',
            'data' => $expense,
        ], 201);
    }

    /**
     * Display the specified fixed expense.
     */
    public function show(string $id): JsonResponse
    {
        $expense = FixedExpense::with('realizations')->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Detail pengeluaran tetap berhasil diambil.',
            'data' => $expense,
        ]);
    }

    /**
     * Update the specified fixed expense.
     */
    public function update(UpdateFixedExpenseRequest $request, string $id): JsonResponse
    {
        $expense = FixedExpense::findOrFail($id);
        $updated = $this->fixedExpenseService->updateFixedExpense($expense, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Pengeluaran tetap berhasil diperbarui.',
            'data' => $updated,
        ]);
    }

    /**
     * Remove the specified fixed expense (soft delete).
     */
    public function destroy(string $id): JsonResponse
    {
        $expense = FixedExpense::findOrFail($id);
        $this->fixedExpenseService->deleteFixedExpense($expense);

        return response()->json([
            'success' => true,
            'message' => 'Pengeluaran tetap berhasil dihapus.',
            'data' => null,
        ]);
    }

    /**
     * Get monthly financial summary of fixed expenses.
     */
    public function monthlySummary(Request $request): JsonResponse
    {
        $month = $request->input('month') ?? $request->input('month_period') ?? Carbon::now()->format('Y-m');

        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            return response()->json([
                'success' => false,
                'message' => 'Format bulan tidak valid. Gunakan format YYYY-MM (contoh: 2026-10).',
                'data' => null,
            ], 422);
        }

        $summary = $this->fixedExpenseService->getMonthlySummary($month);

        return response()->json([
            'success' => true,
            'message' => 'Ringkasan pengeluaran tetap bulanan berhasil diambil.',
            'data' => $summary,
        ]);
    }

    /**
     * Generate realization cycle for a given month period.
     */
    public function generateCycle(Request $request): JsonResponse
    {
        $month = $request->input('month') ?? $request->input('month_period') ?? Carbon::now()->format('Y-m');

        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            return response()->json([
                'success' => false,
                'message' => 'Format bulan tidak valid. Gunakan format YYYY-MM (contoh: 2026-10).',
                'data' => null,
            ], 422);
        }

        $realizations = $this->fixedExpenseService->generateMonthlyRealizations($month);

        return response()->json([
            'success' => true,
            'message' => 'Siklus realisasi pengeluaran tetap berhasil digenerate.',
            'data' => $realizations,
        ]);
    }

    /**
     * Mark a realization as PAID.
     */
    public function markPaid(Request $request, string $id): JsonResponse
    {
        $realization = FixedExpenseRealization::with('fixedExpense')->findOrFail($id);

        $request->validate([
            'amount' => 'nullable|numeric|gt:0',
            'paid_amount' => 'nullable|numeric|gt:0',
            'payment_date' => 'nullable|date',
            'receipt_url' => 'nullable|string|max:500',
            'proof_of_payment' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:2000',
        ]);

        $amount = (float) (
            $request->input('amount') ??
            $request->input('paid_amount') ??
            $realization->fixedExpense?->amount ??
            0
        );

        $receiptUrl = $request->input('receipt_url') ?? $request->input('proof_of_payment');
        $paymentDate = $request->input('payment_date');

        if ($request->filled('notes')) {
            $realization->notes = $request->input('notes');
            $realization->save();
        }

        $updated = $this->fixedExpenseService->markAsPaid(
            $realization,
            $amount,
            $paymentDate,
            $receiptUrl
        );

        return response()->json([
            'success' => true,
            'message' => 'Realisasi pengeluaran tetap berhasil ditandai telah dibayar.',
            'data' => $updated,
        ]);
    }
}
