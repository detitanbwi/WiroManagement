<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\ProjectExpense;

class ProjectExpenseController extends Controller
{
    public function store(Request $request, Project $project)
    {
        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
        ]);

        $project->expenses()->create($validated);

        return back()->with('success', 'Pengeluaran berhasil ditambahkan.');
    }

    public function update(Request $request, ProjectExpense $expense)
    {
        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
        ]);

        $expense->update($validated);

        return back()->with('success', 'Pengeluaran berhasil diperbarui.');
    }

    public function destroy(ProjectExpense $expense)
    {
        $expense->delete();
        return back()->with('success', 'Pengeluaran berhasil dihapus.');
    }
}
