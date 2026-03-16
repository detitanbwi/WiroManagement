<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Quotation;

class QuotationController extends Controller
{
    public function create(Project $project)
    {
        return view('quotations.create', compact('project'));
    }

    public function store(Request $request, Project $project)
    {
        $validated = $request->validate([
            'quotation_number' => 'required|unique:quotations,quotation_number',
            'description' => 'nullable|string',
            'warranty_days' => 'required|integer|min:0',
            'working_duration' => 'required|string|max:255',
            'total_amount' => 'required|numeric|min:0',
            'status' => 'required|in:draft,issued,approved'
        ]);

        $project->quotations()->create($validated);

        return redirect()->route('projects.show', $project)->with('success', 'Quotation created successfully.');
    }

    public function edit(Quotation $quotation)
    {
        $project = $quotation->project;
        return view('quotations.edit', compact('quotation', 'project'));
    }

    public function update(Request $request, Quotation $quotation)
    {
        $validated = $request->validate([
            'quotation_number' => 'required|unique:quotations,quotation_number,' . $quotation->id,
            'description' => 'nullable|string',
            'warranty_days' => 'required|integer|min:0',
            'working_duration' => 'required|string|max:255',
            'total_amount' => 'required|numeric|min:0',
            'status' => 'required|in:draft,issued,approved'
        ]);

        $quotation->update($validated);

        return redirect()->route('projects.show', $quotation->project)->with('success', 'Quotation updated successfully.');
    }

    public function show(Quotation $quotation)
    {
        return view('quotations.show', compact('quotation'));
    }

    public function destroy(Quotation $quotation)
    {
        $project = $quotation->project;

        if ($quotation->status === 'approved') {
            return back()->with('error', 'Quotation yang sudah APPROVED tidak dapat dihapus.');
        }

        $quotation->delete();

        return redirect()->route('projects.show', $project)->with('success', 'Quotation deleted successfully.');
    }
}
