<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Invoice;
use App\Models\Project;
use App\Models\InvoiceItem;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function index(Project $project)
    {
        $invoices = $project->invoices()->latest()->get();
        return view('invoices.index', compact('project', 'invoices'));
    }

    public function create(Project $project)
    {
        return view('invoices.create', compact('project'));
    }

    public function store(Request $request, Project $project)
    {
        $validated = $request->validate([
            'invoice_number' => 'required|unique:invoices,invoice_number',
            'type' => 'required|in:initial,change_request,final',
            'due_date' => 'required|date',
            'issued_date' => 'required|date',
            'tax' => 'required|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.qty' => 'required|numeric|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'notes' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            $subtotal = collect($request->items)->sum(fn($item) => $item['qty'] * $item['price']);
            $total_amount = $subtotal + $request->tax;

            $invoice = $project->invoices()->create([
                'invoice_number' => $request->invoice_number,
                'type' => $request->type,
                'subtotal' => $subtotal,
                'tax' => $request->tax,
                'total_amount' => $total_amount,
                'due_date' => $request->due_date,
                'issued_date' => $request->issued_date,
                'status' => 'issued',
                'notes' => $request->notes
            ]);

            foreach ($request->items as $item) {
                $invoice->items()->create([
                    'description' => $item['description'],
                    'qty' => $item['qty'],
                    'price' => $item['price'],
                    'subtotal' => $item['qty'] * $item['price']
                ]);
            }

            DB::commit();
            return redirect()->route('projects.show', $project)->with('success', 'Invoice created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to create invoice: ' . $e->getMessage());
        }
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['project.client', 'items', 'payments']);
        return view('invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice)
    {
        if ($invoice->status !== 'draft') {
            return back()->with('error', 'Only draft invoices can be edited.');
        }
        return view('invoices.edit', compact('invoice'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        if ($invoice->status !== 'draft') {
            return back()->with('error', 'Only draft invoices can be updated.');
        }
        // ... update logic similar to store but with existing IDs if needed
    }

    public function destroy(Invoice $invoice)
    {
        if ($invoice->status !== 'draft') {
            return back()->with('error', 'Issued or Paid invoices cannot be deleted.');
        }
        $project = $invoice->project;
        $invoice->delete();
        return redirect()->route('projects.show', $project)->with('success', 'Invoice deleted.');
    }
}
