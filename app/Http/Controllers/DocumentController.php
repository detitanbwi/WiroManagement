<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\Quotation;
use App\Models\Payment;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;

class DocumentController extends Controller
{
    public function streamInvoice(Invoice $invoice)
    {
        $invoice->load(['project.client', 'items']);
        
        $terms = Setting::where('key', 'terms_conditions')->first()?->value;

        $data = [
            'invoice' => $invoice,
            'client' => $invoice->project->client,
            'items' => $invoice->items,
            'terms' => $terms,
            'logo' => $this->getBase64Logo()
        ];

        $filename = str_replace('/', '-', $invoice->invoice_number);
        $pdf = Pdf::loadView('documents.pdf.invoice', $data);
        return $pdf->stream("Invoice-{$filename}.pdf");
    }

    public function streamReceipt(Payment $payment)
    {
        $payment->load(['invoice.project.client', 'receipt']);

        $data = [
            'payment' => $payment,
            'receipt' => $payment->receipt,
            'invoice' => $payment->invoice,
            'client' => $payment->invoice->project->client,
            'logo' => $this->getBase64Logo()
        ];

        $filename = str_replace('/', '-', $payment->receipt->receipt_number);
        $pdf = Pdf::loadView('documents.pdf.receipt', $data);
        return $pdf->stream("Receipt-{$filename}.pdf");
    }

    public function streamQuotation(Quotation $quotation)
    {
        $quotation->load(['project.client']);

        $notes = Setting::where('key', 'quotation_notes')->first()?->value;

        $data = [
            'quotation' => $quotation,
            'client' => $quotation->project->client,
            'notes' => $notes,
            'logo' => $this->getBase64Logo()
        ];

        $filename = str_replace('/', '-', $quotation->quotation_number);
        $pdf = Pdf::loadView('documents.pdf.quotation', $data)->setPaper('a4', 'portrait');
        return $pdf->stream("Quotation-{$filename}.pdf");
    }

    private function getBase64Logo()
    {
        $path = public_path('logo.png');
        if (!file_exists($path)) return null;

        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        return 'data:image/' . $type . ';base64,' . base64_encode($data);
    }
}
