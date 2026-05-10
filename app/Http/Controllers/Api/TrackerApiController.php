<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\TrackerAccount;
use App\Models\TrackerCategory;
use App\Models\TrackerExpense;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TrackerApiController extends Controller
{
    // --- ACCOUNTS ---
    public function getAccounts(Request $request)
    {
        $query = TrackerAccount::query();
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        return response()->json($query->get());
    }

    public function storeAccount(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'type' => 'required|in:personal,company',
            'balance' => 'numeric'
        ]);

        $account = TrackerAccount::create($validated);
        return response()->json($account, 201);
    }

    public function updateAccount(Request $request, $id)
    {
        $account = TrackerAccount::findOrFail($id);
        $account->update($request->all());
        return response()->json($account);
    }

    public function deleteAccount($id)
    {
        TrackerAccount::destroy($id);
        return response()->json(['message' => 'Deleted']);
    }

    // --- CATEGORIES ---
    public function getCategories(Request $request)
    {
        $query = TrackerCategory::query();
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        return response()->json($query->get());
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'type' => 'required|in:personal,company',
            'icon' => 'nullable|string'
        ]);

        $category = TrackerCategory::create($validated);
        return response()->json($category, 201);
    }

    public function updateCategory(Request $request, $id)
    {
        $category = TrackerCategory::findOrFail($id);
        $category->update($request->all());
        return response()->json($category);
    }

    public function deleteCategory($id)
    {
        TrackerCategory::destroy($id);
        return response()->json(['message' => 'Deleted']);
    }

    // --- EXPENSES ---
    public function getExpenses(Request $request)
    {
        $query = TrackerExpense::with(['account', 'category']);
        
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('expense_date', [$request->start_date, $request->end_date]);
        }

        return response()->json($query->latest('expense_date')->paginate(50));
    }

    public function syncExpenses(Request $request)
    {
        $validated = $request->validate([
            'expenses' => 'required|array',
            'expenses.*.id' => 'required|uuid',
            'expenses.*.account_id' => 'required|uuid|exists:tracker_accounts,id',
            'expenses.*.category_id' => 'required|uuid|exists:tracker_categories,id',
            'expenses.*.type' => 'required|in:personal,company',
            'expenses.*.amount' => 'required|numeric',
            'expenses.*.expense_date' => 'required|date',
            'expenses.*.description' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();
            foreach ($validated['expenses'] as $item) {
                TrackerExpense::updateOrCreate(['id' => $item['id']], $item);
            }
            DB::commit();

            return response()->json(['message' => 'Sync successful'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Sync failed: " . $e->getMessage());
            return response()->json(['message' => 'Sync failed', 'error' => $e->getMessage()], 500);
        }
    }

    public function deleteExpense($id)
    {
        TrackerExpense::destroy($id);
        return response()->json(['message' => 'Deleted']);
    }
}
