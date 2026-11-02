<?php

namespace Tests\Feature\Finance;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\MonthlyClosing;
use App\Models\MonthlyClosingItem;
use App\Models\Payment;
use App\Models\Project;
use App\Models\User;
use App\Services\Finance\FinancialExportService;
use App\Services\Finance\MonthlyClosingService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonthlyClosingPdfTest extends TestCase
{
    use RefreshDatabase;

    protected User $financeUser;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);

        $this->financeUser = User::factory()->create([
            'name' => 'Finance Specialist',
            'email' => 'finance@wirodev.test',
            'is_active' => true,
        ]);
        $this->financeUser->syncRoles(['finance']);

        $client = Client::create([
            'name' => 'PT Client Test',
            'email' => 'client@test.com',
            'phone' => '08123456789',
        ]);

        $this->project = Project::create([
            'client_id' => $client->id,
            'title' => 'Sistem Rekam Medis RS',
            'project_code' => 'PRJ-2026-MED',
            'status' => 'in_progress',
            'start_date' => '2026-10-01',
        ]);
    }

    public function test_monthly_closing_pdf_compiles_successfully_with_items_and_two_pages(): void
    {
        $closing = MonthlyClosing::create([
            'period_month' => '2026-10',
            'opening_cash_balance' => 25000000,
            'total_revenue' => 45000000,
            'total_cogs' => 12000000,
            'gross_profit' => 33000000,
            'total_opex' => 8000000,
            'net_profit' => 25000000,
            'net_profit_margin' => 55.56,
            'cash_inflow' => 45000000,
            'cash_outflow' => 20000000,
            'ending_cash_balance' => 50000000,
            'audit_status' => 'approved',
            'auditor_notes' => "Catatan auditor: Laporan closing bulan Oktober 2026 telah diverifikasi.\nSemua bukti transaksi terlampir valid.",
            'audited_by' => $this->financeUser->id,
            'approved_by' => $this->financeUser->id,
        ]);

        // Add various items across categories
        MonthlyClosingItem::create([
            'closing_id' => $closing->id,
            'account_code' => '1000-00-01',
            'account_name' => 'Saldo Kas Awal Periode',
            'category' => 'cash_mutation',
            'debit' => 25000000,
            'credit' => 0,
            'project_id' => null,
            'notes' => 'Saldo awal kas',
        ]);

        MonthlyClosingItem::create([
            'closing_id' => $closing->id,
            'account_code' => '4000-01-01',
            'account_name' => 'Pendapatan Proyek: Sistem Rekam Medis RS',
            'category' => 'revenue',
            'debit' => 0,
            'credit' => 45000000,
            'project_id' => $this->project->id,
            'notes' => 'Pembayaran termin 1 via BCA',
        ]);

        MonthlyClosingItem::create([
            'closing_id' => $closing->id,
            'account_code' => '5000-01-01',
            'account_name' => 'HPP Tenaga Kerja Langsung: Tim Frontend & Backend',
            'category' => 'cogs',
            'debit' => 10000000,
            'credit' => 0,
            'project_id' => $this->project->id,
            'notes' => '50 man-hours work logs',
        ]);

        MonthlyClosingItem::create([
            'closing_id' => $closing->id,
            'account_code' => '5000-02-01',
            'account_name' => 'HPP Infrastruktur & Server Cloud',
            'category' => 'cogs',
            'debit' => 2000000,
            'credit' => 0,
            'project_id' => $this->project->id,
            'notes' => 'Biaya AWS EC2 & RDS',
        ]);

        MonthlyClosingItem::create([
            'closing_id' => $closing->id,
            'account_code' => '6000-01-01',
            'account_name' => 'Beban Iklan & Pemasaran: Google Ads B2B',
            'category' => 'opex',
            'debit' => 5000000,
            'credit' => 0,
            'project_id' => null,
            'notes' => 'Kampanye Search Ads Google',
        ]);

        MonthlyClosingItem::create([
            'closing_id' => $closing->id,
            'account_code' => '6000-02-01',
            'account_name' => 'Beban Kantor: Operasional & Sewa Coworking',
            'category' => 'opex',
            'debit' => 3000000,
            'credit' => 0,
            'project_id' => null,
            'notes' => 'Sewa dan utilitas internet kantor',
        ]);

        MonthlyClosingItem::create([
            'closing_id' => $closing->id,
            'account_code' => '6000-03-01',
            'account_name' => 'Beban Tetap (UTILITY): Listrik PLN & Fiber Optic',
            'category' => 'opex',
            'debit' => 1500000,
            'credit' => 0,
            'project_id' => null,
            'notes' => 'Tagihan PLN Pasca Bayar & Biznet',
        ]);

        MonthlyClosingItem::create([
            'closing_id' => $closing->id,
            'account_code' => '6000-04-01',
            'account_name' => 'Beban Tetap (SUBSCRIPTION): GitHub Enterprise & Figma Pro',
            'category' => 'opex',
            'debit' => 2500000,
            'credit' => 0,
            'project_id' => null,
            'notes' => 'SaaS licenses',
        ]);

        MonthlyClosingItem::create([
            'closing_id' => $closing->id,
            'account_code' => '6000-05-01',
            'account_name' => 'Beban Tetap (PAYROLL): Gaji Pokok Tim Internal',
            'category' => 'opex',
            'debit' => 15000000,
            'credit' => 0,
            'project_id' => null,
            'notes' => 'Gaji bulanan 5 staf',
        ]);

        $exportService = app(FinancialExportService::class);
        $pdf = $exportService->generatePdf($closing);

        // Render PDF output
        $pdfOutput = $pdf->output();
        $this->assertNotEmpty($pdfOutput);

        // Check page count from Dompdf Canvas
        $dompdf = $pdf->getDomPDF();
        $canvas = $dompdf->getCanvas();
        $pageCount = $canvas->get_page_count();

        // Must be exactly 2 pages for normal transaction list
        $this->assertEquals(2, $pageCount, "Laporan closing harus terbagi rapi menjadi 2 halaman.");

        // Check web show view renders dynamic OPEX breakdown
        $showResponse = $this->actingAs($this->financeUser)->get(route('finance.closings.show', $closing));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('Rincian Pos Beban Operasional (OPEX)');
        $showResponse->assertSee('6000-01-01');
        $showResponse->assertSee('6000-03-01');
        $showResponse->assertSee('6000-04-01');
        $showResponse->assertSee('6000-05-01');
        $showResponse->assertSee('Beban Tetap (PAYROLL)');

        // Check controller route export
        $response = $this->actingAs($this->financeUser)->get(route('finance.closings.pdf', $closing));
        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
    }

    public function test_monthly_closing_pdf_with_many_items_gracefully_paginates(): void
    {
        $closing = MonthlyClosing::create([
            'period_month' => '2026-10',
            'opening_cash_balance' => 10000000,
            'total_revenue' => 10000000,
            'total_cogs' => 5000000,
            'gross_profit' => 5000000,
            'total_opex' => 2000000,
            'net_profit' => 3000000,
            'net_profit_margin' => 30.0,
            'cash_inflow' => 10000000,
            'cash_outflow' => 7000000,
            'ending_cash_balance' => 13000000,
            'audit_status' => 'audited',
        ]);

        // Create 35 items so it exceeds 1 page on the ledger table
        for ($i = 1; $i <= 35; $i++) {
            MonthlyClosingItem::create([
                'closing_id' => $closing->id,
                'account_code' => '5000-01-' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'account_name' => "Rincian Item Transaksi Pengeluaran Ke-{$i}",
                'category' => 'cogs',
                'debit' => 100000 * $i,
                'credit' => 0,
                'project_id' => $this->project->id,
                'notes' => "Catatan transaksi operasional #{$i}",
            ]);
        }

        $exportService = app(FinancialExportService::class);
        $pdf = $exportService->generatePdf($closing);
        $pdf->render();

        $dompdf = $pdf->getDomPDF();
        $canvas = $dompdf->getCanvas();
        $pageCount = $canvas->get_page_count();

        // Page 1 is executive summary, subsequent pages are ledger items
        $this->assertGreaterThanOrEqual(3, $pageCount);
    }
}
