<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceApiController extends Controller
{
    /**
     * Display a listing of invoices with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Invoice::with([
            'project:id,title,client_id',
            'project.client:id,name,company_name',
        ]);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($projectId = $request->input('project_id')) {
            $query->where('project_id', $projectId);
        }

        if ($search = $request->input('search', $request->input('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'LIKE', "%{$search}%")
                  ->orWhereHas('project', function ($pq) use ($search) {
                      $pq->where('title', 'LIKE', "%{$search}%")
                         ->orWhereHas('client', function ($cq) use ($search) {
                             $cq->where('name', 'LIKE', "%{$search}%")
                                ->orWhere('company_name', 'LIKE', "%{$search}%");
                         });
                  });
            });
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $invoices = $query->latest('issued_date')->paginate($perPage);

        // Append computed attributes for API consumers
        $invoices->getCollection()->transform(function ($invoice) {
            $invoice->paid_amount = $invoice->paid_amount;
            $invoice->balance_due = $invoice->balance_due;
            return $invoice;
        });

        return response()->json([
            'status' => 'success',
            'data' => $invoices,
        ]);
    }

    /**
     * Get the next recommended official invoice number for a given project.
     */
    public function getNextNumber(Project $project): JsonResponse
    {
        $nextNumber = Invoice::generateNextNumber($project);

        return response()->json([
            'status' => 'success',
            'data' => [
                'project_id' => $project->id,
                'project_title' => $project->title,
                'invoice_number' => $nextNumber,
            ],
        ]);
    }

    /**
     * Store a newly created invoice (Quick Invoice Creation).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'invoice_number' => 'nullable|string|unique:invoices,invoice_number',
            'issued_date' => 'required|date',
            'due_date' => 'nullable|date',
            'tax' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.qty' => 'required|numeric|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        $project = Project::findOrFail($validated['project_id']);

        // Use supplied number or auto-generate official next number matching Wirodev web rules
        $invoiceNumber = !empty($validated['invoice_number'])
            ? trim($validated['invoice_number'])
            : Invoice::generateNextNumber($project);

        // Ensure uniqueness if race condition or auto-generated collision occurs
        if (Invoice::where('invoice_number', $invoiceNumber)->exists()) {
            $invoiceNumber = Invoice::generateNextNumber($project);
        }

        try {
            DB::beginTransaction();

            $subtotal = collect($validated['items'])->sum(function ($item) {
                return $item['qty'] * $item['price'];
            });

            $tax = (float) ($validated['tax'] ?? 0);
            $discount = (float) ($validated['discount'] ?? 0);
            $totalAmount = $subtotal + $tax - $discount;

            $invoice = $project->invoices()->create([
                'invoice_number' => $invoiceNumber,
                'type' => 'initial',
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'total_amount' => $totalAmount,
                'due_date' => $validated['due_date'] ?? null,
                'issued_date' => $validated['issued_date'],
                'status' => 'issued',
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                $invoice->items()->create([
                    'description' => $item['description'],
                    'qty' => $item['qty'],
                    'price' => $item['price'],
                    'subtotal' => $item['qty'] * $item['price'],
                ]);
            }

            DB::commit();

            $invoice->load(['project.client', 'items']);
            $invoice->paid_amount = $invoice->paid_amount;
            $invoice->balance_due = $invoice->balance_due;

            return response()->json([
                'status' => 'success',
                'message' => 'Invoice berhasil dibuat.',
                'data' => $invoice,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membuat invoice: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified invoice with details.
     */
    public function show(Invoice $invoice): JsonResponse
    {
        $invoice->load([
            'project.client',
            'items',
            'payments' => function ($q) {
                $q->latest();
            },
        ]);

        $invoice->paid_amount = $invoice->paid_amount;
        $invoice->balance_due = $invoice->balance_due;

        return response()->json([
            'status' => 'success',
            'data' => $invoice,
        ]);
    }
}

