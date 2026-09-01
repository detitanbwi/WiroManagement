<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\Quotation;
use App\Models\Payment;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use iio\libmergepdf\Merger;

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
        $pdf = Pdf::loadView('documents.pdf.invoice', $data)->setPaper('a4', 'portrait');

        return $this->outputPdfWithAttachment($pdf, $invoice->attachment_pdf, "Invoice-{$filename}.pdf");
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
        $terms = Setting::where('key', 'quotation_terms')->first()?->value;

        $data = [
            'quotation' => $quotation,
            'client' => $quotation->project->client,
            'notes' => $notes,
            'terms' => $terms,
            'logo' => $this->getBase64Logo()
        ];

        $filename = str_replace('/', '-', $quotation->quotation_number);
        $pdf = Pdf::loadView('documents.pdf.quotation', $data)->setPaper('a4', 'portrait');
        
        return $this->outputPdfWithAttachment($pdf, $quotation->attachment_pdf, "Quotation-{$filename}.pdf");
    }

    /**
     * Check if a value looks like a valid file path (not '0', empty string, etc.)
     */
    private function isValidAttachmentValue(?string $value): bool
    {
        if ($value === null || $value === '' || $value === '0' || $value === 'null') {
            return false;
        }
        // Must contain at least a slash or dot to look like a file path
        return str_contains($value, '/') || str_contains($value, '.');
    }

    /**
     * Resolve the absolute path of an attachment file across storage disks or paths.
     */
    private function resolveAttachmentPath(?string $relativePath): ?string
    {
        if (!$this->isValidAttachmentValue($relativePath)) {
            return null;
        }

        $cleanPath = ltrim($relativePath, '/');

        // Try Storage disk first (most reliable)
        if (Storage::disk('public')->exists($cleanPath)) {
            return Storage::disk('public')->path($cleanPath);
        }

        // Direct storage path
        $appPublicPath = storage_path('app/public/' . $cleanPath);
        if (file_exists($appPublicPath)) {
            return $appPublicPath;
        }

        // Via public symlink
        $publicStoragePath = public_path('storage/' . $cleanPath);
        if (file_exists($publicStoragePath)) {
            return $publicStoragePath;
        }

        // Absolute path
        if (file_exists($relativePath)) {
            return $relativePath;
        }

        try { Log::warning("Attachment PDF path not found on disk: [{$relativePath}]"); } catch (\Throwable $_) {}
        return null;
    }

    /**
     * Merge generated DomPDF with attachment PDF if available.
     * Strategy: try FPDI first (fast, in-process), fallback to qpdf for PDF 1.5+.
     */
    private function outputPdfWithAttachment($pdf, ?string $attachmentRelativePath, string $downloadFilename)
    {
        // Generate the main document PDF first
        try {
            $mainPdfOutput = $pdf->output();
        } catch (\Throwable $e) {
            try { Log::error("Failed to generate main PDF ({$downloadFilename}): " . $e->getMessage()); } catch (\Throwable $_) {}
            abort(500, 'Gagal membuat dokumen PDF.');
        }

        $attachmentPath = $this->resolveAttachmentPath($attachmentRelativePath);

        $headers = [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $downloadFilename . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0'
        ];

        if ($attachmentPath) {
            // Try 1: libmergepdf (FPDI) — works for PDF ≤1.4
            try {
                $merger = new Merger();
                $merger->addRaw($mainPdfOutput);
                $merger->addFile($attachmentPath);
                $mergedPdf = $merger->merge();

                return response($mergedPdf, 200, $headers);
            } catch (\Throwable $e) {
                try { Log::info("FPDI merge failed, trying qpdf fallback: " . $e->getMessage()); } catch (\Throwable $_) {}
            }

            // Try 2: qpdf binary fallback — handles all PDF versions
            $mergedViaQpdf = $this->mergeWithQpdf($mainPdfOutput, $attachmentPath);
            if ($mergedViaQpdf !== null) {
                return response($mergedViaQpdf, 200, $headers);
            }
        }

        // Final fallback: return main document PDF without attachment
        return response($mainPdfOutput, 200, $headers);
    }

    /**
     * Merge PDFs using qpdf command-line tool (handles PDF 1.5+ / 2.0).
     */
    private function mergeWithQpdf(string $mainPdfContent, string $attachmentPath): ?string
    {
        $qpdfBin = $this->findQpdfBinary();
        if (!$qpdfBin) {
            try { Log::warning('qpdf binary not found. Cannot merge PDF 1.5+ attachments.'); } catch (\Throwable $_) {}
            return null;
        }

        $tmpMain = null;
        $tmpOut = null;

        try {
            $tmpMain = tempnam(sys_get_temp_dir(), 'pdf_main_') . '.pdf';
            $tmpOut = tempnam(sys_get_temp_dir(), 'pdf_merged_') . '.pdf';

            file_put_contents($tmpMain, $mainPdfContent);

            $cmd = escapeshellarg($qpdfBin) . ' --empty --pages '
                . escapeshellarg($tmpMain) . ' '
                . escapeshellarg($attachmentPath) . ' -- '
                . escapeshellarg($tmpOut) . ' 2>&1';

            exec($cmd, $output, $returnCode);

            if ($returnCode === 0 && file_exists($tmpOut)) {
                $mergedContent = file_get_contents($tmpOut);
                try { Log::info("PDF merged successfully via qpdf ({$attachmentPath})"); } catch (\Throwable $_) {}
                return $mergedContent;
            }

            try { Log::error("qpdf merge failed (code {$returnCode}): " . implode("\n", $output)); } catch (\Throwable $_) {}
            return null;
        } catch (\Throwable $e) {
            try { Log::error("qpdf merge exception: " . $e->getMessage()); } catch (\Throwable $_) {}
            return null;
        } finally {
            if ($tmpMain && file_exists($tmpMain)) @unlink($tmpMain);
            if ($tmpOut && file_exists($tmpOut)) @unlink($tmpOut);
        }
    }

    /**
     * Locate the qpdf binary on the system.
     */
    private function findQpdfBinary(): ?string
    {
        $paths = [
            '/opt/homebrew/bin/qpdf',
            '/usr/local/bin/qpdf',
            '/usr/bin/qpdf',
        ];

        foreach ($paths as $path) {
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }

        // Try which
        $which = trim(shell_exec('which qpdf 2>/dev/null') ?? '');
        if ($which && file_exists($which)) {
            return $which;
        }

        return null;
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
