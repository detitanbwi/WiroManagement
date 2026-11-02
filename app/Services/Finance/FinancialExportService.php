<?php

namespace App\Services\Finance;

use App\Models\MonthlyClosing;
use App\Models\QuarterlyClosing;
use App\Models\Shareholder;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class FinancialExportService
{
    /**
     * Generate DomPdf instance for the given monthly closing.
     */
    public function generatePdf(MonthlyClosing $closing)
    {
        $closing->load(['items.project', 'auditor', 'approver']);

        $logo = $this->getBase64Logo();

        $revenueItems = $closing->items->where('category', 'revenue');
        $cogsItems = $closing->items->where('category', 'cogs');
        $opexItems = $closing->items->where('category', 'opex');
        $cashItems = $closing->items->where('category', 'cash_mutation');

        $data = [
            'closing' => $closing,
            'items' => $closing->items,
            'revenueItems' => $revenueItems,
            'cogsItems' => $cogsItems,
            'opexItems' => $opexItems,
            'cashItems' => $cashItems,
            'logo' => $logo,
        ];

        return Pdf::loadView('documents.pdf.monthly-closing', $data)->setPaper('a4', 'portrait');
    }

    /**
     * Stream PDF response in browser.
     */
    public function streamPdf(MonthlyClosing $closing): Response
    {
        $pdf = $this->generatePdf($closing);
        $filename = "Monthly-Closing-{$closing->period_month}.pdf";
        return $pdf->stream($filename);
    }

    /**
     * Download PDF file.
     */
    public function downloadPdf(MonthlyClosing $closing): Response
    {
        $pdf = $this->generatePdf($closing);
        $filename = "Monthly-Closing-{$closing->period_month}.pdf";
        return $pdf->download($filename);
    }

    /**
     * Generate DomPdf instance for the RUPS Minutes (Berita Acara RUPS) document.
     */
    public function generateRupsPdf(QuarterlyClosing $closing)
    {
        $closing->load(['profitDistribution.payouts.shareholder', 'approver', 'capitalHistories.shareholder']);

        $logo = $this->getBase64Logo();
        $shareholders = Shareholder::where('is_active', true)->orderBy('ownership_percentage', 'desc')->get();
        $distribution = $closing->profitDistribution;

        $data = [
            'closing' => $closing,
            'distribution' => $distribution,
            'shareholders' => $shareholders,
            'logo' => $logo,
        ];

        return Pdf::loadView('documents.pdf.rups-minutes', $data)->setPaper('a4', 'portrait');
    }

    /**
     * Stream RUPS Minutes PDF in browser.
     */
    public function streamRupsPdf(QuarterlyClosing $closing): Response
    {
        $pdf = $this->generateRupsPdf($closing);
        $docNum = $closing->profitDistribution?->document_number ?: "BA-RUPS-{$closing->period_label}";
        $sanitizedDocNum = str_replace(['/', '\\'], '-', $docNum);
        return $pdf->stream("{$sanitizedDocNum}.pdf");
    }

    /**
     * Download RUPS Minutes PDF.
     */
    public function downloadRupsPdf(QuarterlyClosing $closing): Response
    {
        $pdf = $this->generateRupsPdf($closing);
        $docNum = $closing->profitDistribution?->document_number ?: "BA-RUPS-{$closing->period_label}";
        $sanitizedDocNum = str_replace(['/', '\\'], '-', $docNum);
        return $pdf->download("{$sanitizedDocNum}.pdf");
    }

    /**
     * Encode public/logo-wirodev.png into Base64 format for DomPDF compatibility.
     */
    private function getBase64Logo(): ?string
    {
        $path = public_path('logo-wirodev.png');
        if (!file_exists($path)) {
            $path = public_path('logo.png');
        }
        if (!file_exists($path)) {
            return null;
        }

        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        return 'data:image/' . $type . ';base64,' . base64_encode($data);
    }
}
