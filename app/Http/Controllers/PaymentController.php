<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Receipt;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function store(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1|max:' . $invoice->balance_due,
            'payment_date' => 'required|date',
            'payment_method' => 'required|string'
        ]);

        try {
            DB::beginTransaction();

            // 1. Create Payment
            $payment = $invoice->payments()->create($validated);

            // 2. Update Invoice Status
            $totalPaid = $invoice->payments()->sum('amount');
            if ($totalPaid >= $invoice->total_amount) {
                $invoice->update(['status' => 'paid']);
            } else {
                $invoice->update(['status' => 'partial']);
            }

            // 3. Generate Receipt
            $year = date('Y');
            $count = Receipt::whereYear('created_at', $year)->count() + 1;
            $receiptNumber = "RCPT/WIRODEV/{$year}/" . str_pad($count, 3, '0', STR_PAD_LEFT);

            $payment->receipt()->create([
                'receipt_number' => $receiptNumber,
                'issued_date' => $request->payment_date
            ]);

            DB::commit();
            return back()->with('success', 'Payment recorded and Receipt generated.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to record payment: ' . $e->getMessage());
        }
    }
}
