<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Quotation;
use Illuminate\Support\Facades\Storage;

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
            'status' => 'required|in:draft,issued,approved',
            'attachment_pdf' => 'nullable|file|mimes:pdf|max:10240'
        ]);

        if ($request->hasFile('attachment_pdf')) {
            $path = $request->file('attachment_pdf')->store('attachments/quotations', 'public');
            $validated['attachment_pdf'] = $path;
        }

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
            'status' => 'required|in:draft,issued,approved',
            'attachment_pdf' => 'nullable|file|mimes:pdf|max:10240'
        ]);

        if ($request->hasFile('attachment_pdf')) {
            // Delete old file if exists
            if ($quotation->attachment_pdf) {
                Storage::disk('public')->delete($quotation->attachment_pdf);
            }
            
            $path = $request->file('attachment_pdf')->store('attachments/quotations', 'public');
            $validated['attachment_pdf'] = $path;
        }

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

        if ($quotation->attachment_pdf) {
            Storage::disk('public')->delete($quotation->attachment_pdf);
        }

        $quotation->delete();

        return redirect()->route('projects.show', $project)->with('success', 'Quotation deleted successfully.');
    }
    public function convertToInvoice(Quotation $quotation)
    {
        if ($quotation->status !== 'approved') {
            return back()->with('error', 'Hanya quotation yang sudah APPROVED yang dapat dikonversi menjadi invoice.');
        }

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            $project = $quotation->project;
            
            // Create Invoice
            $invoice = $project->invoices()->create([
                'invoice_number' => 'INV/' . date('Ymd') . '/' . str_pad($project->invoices()->count() + 1, 2, '0', STR_PAD_LEFT),
                'type' => 'final',
                'subtotal' => $quotation->total_amount,
                'tax' => 0,
                'total_amount' => $quotation->total_amount,
                'due_date' => now()->addDays(7),
                'issued_date' => now(),
                'status' => 'issued',
                'notes' => 'Generated from Quotation ' . $quotation->quotation_number
            ]);

            // Create Invoice Item
            $invoice->items()->create([
                'description' => $quotation->description ?? $project->title,
                'qty' => 1,
                'price' => $quotation->total_amount,
                'subtotal' => $quotation->total_amount
            ]);

            \Illuminate\Support\Facades\DB::commit();

            return redirect()->route('invoices.show', $invoice)->with('success', 'Quotation berhasil dikonversi menjadi Invoice.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return back()->with('error', 'Gagal konversi quotation: ' . $e->getMessage());
        }
    }
}
