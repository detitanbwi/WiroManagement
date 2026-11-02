<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Closing Keuangan {{ $closing->period_month }}</title>
    <style>
        body {
            font-family: 'Helvetica', sans-serif;
            color: #222;
            line-height: 1.3;
            margin: 0;
            padding: 0;
            background: #fff;
            font-size: 10px;
        }
        .container {
            padding: 18px 22px;
        }

        /* Kop Surat Styles */
        @include('documents.pdf.partials.kop_style')

        .kop-surat {
            margin-bottom: 12px;
            padding-bottom: 6px;
        }

        .doc-title-box {
            text-align: center;
            margin-bottom: 12px;
        }
        .doc-title {
            font-size: 15px;
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 2px solid #000;
            display: inline-block;
            padding: 0 15px 3px 15px;
            letter-spacing: 0.5px;
        }
        .doc-subtitle {
            font-size: 10.5px;
            color: #555;
            margin-top: 3px;
            font-weight: 600;
        }

        .meta-table {
            width: 100%;
            margin-bottom: 12px;
            font-size: 10px;
            border-collapse: collapse;
        }
        .meta-table td {
            padding: 2px 0;
            vertical-align: top;
        }

        .section-title {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            background: #f1f5f9;
            padding: 4px 8px;
            border-left: 4px solid #003399;
            margin-top: 10px;
            margin-bottom: 6px;
            color: #0f172a;
        }

        .financial-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .financial-table th {
            background: #f8fafc;
            padding: 4px 6px;
            text-align: left;
            font-size: 9.5px;
            font-weight: bold;
            color: #000;
            text-transform: uppercase;
            border: 1px solid #cbd5e1;
        }
        .financial-table td {
            padding: 4px 6px;
            border: 1px solid #cbd5e1;
            font-size: 9.5px;
            vertical-align: middle;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .font-black { font-weight: 900; }

        .row-total {
            background-color: #f8fafc;
            font-weight: bold;
        }
        .row-highlight {
            background-color: #e0e7ff;
            font-weight: bold;
        }

        .status-badge {
            position: absolute;
            top: 115px;
            right: 35px;
            border: 3px solid #22c55e;
            color: #22c55e;
            padding: 5px 14px;
            font-weight: bold;
            font-size: 15px;
            opacity: 0.75;
            transform: rotate(-10deg);
            border-radius: 4px;
        }

        .signatures-table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }
        .signatures-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            font-size: 10.5px;
        }
        .sign-space {
            height: 48px;
        }
        .sign-name {
            font-weight: bold;
            border-bottom: 1px solid #000;
            display: inline-block;
            padding: 0 25px;
        }
        .sign-title {
            color: #555;
            font-size: 9.5px;
            margin-top: 3px;
        }

        /* DomPDF Page Break & Multi-page Utilities */
        .page-break {
            page-break-after: always;
        }

        /* Halaman Kedua: Rincian Transaksi / Buku Besar (Hitam-Putih / Grayscale Minimalis) */
        .ledger-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            margin-bottom: 10px;
        }
        .ledger-table thead {
            display: table-header-group;
        }
        .ledger-table tfoot {
            display: table-footer-group;
        }
        .ledger-table tr {
            page-break-inside: avoid;
        }
        .ledger-table th {
            background-color: #f2f2f2;
            border: 1px solid #444;
            padding: 5px 6px;
            font-size: 9px;
            font-weight: bold;
            color: #000;
            text-transform: uppercase;
            text-align: left;
        }
        .ledger-table td {
            border: 1px solid #444;
            padding: 4px 6px;
            font-size: 9px;
            line-height: 1.25;
            color: #000;
            vertical-align: top;
            background-color: #fff;
        }
        .ledger-table tbody tr:nth-child(even) td {
            background-color: #fafafa;
        }
        .ledger-table tfoot tr td {
            background-color: #f5f5f5 !important;
            font-weight: bold;
            font-size: 9.5px;
            border-top: 1px solid #000 !important;
            border-bottom: 3px double #000 !important;
            padding: 5px 6px;
            color: #000;
        }
    </style>
</head>
<body>

    <!-- ========================================== -->
    <!-- HALAMAN PERTAMA: RINGKASAN EKSEKUTIF      -->
    <!-- ========================================== -->
    <div class="container">
        @if($closing->audit_status === 'approved')
            <div class="status-badge">APPROVED</div>
        @elseif($closing->audit_status === 'audited')
            <div class="status-badge" style="border-color: #2563eb; color: #2563eb;">AUDITED</div>
        @else
            <div class="status-badge" style="border-color: #f59e0b; color: #f59e0b;">DRAFT</div>
        @endif

        <!-- Official Kop Surat -->
        @include('documents.pdf.partials.kop')

        <div class="doc-title-box">
            <div class="doc-title">LAPORAN CLOSING KEUANGAN BULANAN</div>
            <div class="doc-subtitle">PERIODE: {{ strtoupper(\Carbon\Carbon::parse($closing->period_month . '-01')->translatedFormat('F Y')) }} ({{ $closing->period_month }})</div>
        </div>

        <table class="meta-table">
            <tr>
                <td style="width: 20%; font-weight: bold;">Tanggal Cetak</td>
                <td style="width: 30%;">: {{ date('d F Y') }}</td>
                <td style="width: 20%; font-weight: bold;">Status Audit</td>
                <td style="width: 30%;">: {{ strtoupper($closing->audit_status) }}</td>
            </tr>
            <tr>
                <td style="font-weight: bold;">Auditor Internal</td>
                <td>: {{ $closing->auditor?->name ?? 'Tim Keuangan Wirodev' }}</td>
                <td style="font-weight: bold;">Disetujui Oleh</td>
                <td>: {{ $closing->approver?->name ?? 'Direksi / Managing Partner' }}</td>
            </tr>
        </table>

        <!-- 1. Ringkasan Eksekutif & Laporan Laba Rugi -->
        <div class="section-title">I. LAPORAN LABA RUGI (INCOME STATEMENT)</div>
        <table class="financial-table">
            <thead>
                <tr>
                    <th style="width: 60%;">Komponen Akun Finansial</th>
                    <th style="width: 20%; text-align: center;">Rasio</th>
                    <th style="width: 20%; text-align: right;">Nominal (IDR)</th>
                </tr>
            </thead>
            <tbody>
                <tr class="row-highlight">
                    <td class="font-bold">1. TOTAL PENDAPATAN PROYEK (REVENUE)</td>
                    <td class="text-center font-bold">100.0%</td>
                    <td class="text-right font-black">Rp {{ number_format($closing->total_revenue, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td style="padding-left: 15px;">HPP Beban Tenaga Kerja Langsung Proyek (Man-Hour)</td>
                    <td class="text-center">{{ $closing->total_revenue > 0 ? round(($cogsItems->where('account_code', '5000-01-01')->sum('debit') / $closing->total_revenue) * 100, 1) : 0 }}%</td>
                    <td class="text-right">Rp {{ number_format($cogsItems->where('account_code', '5000-01-01')->sum('debit'), 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td style="padding-left: 15px;">HPP Beban Infrastruktur & Lisensi Server Proyek</td>
                    <td class="text-center">{{ $closing->total_revenue > 0 ? round(($cogsItems->where('account_code', '5000-02-01')->sum('debit') / $closing->total_revenue) * 100, 1) : 0 }}%</td>
                    <td class="text-right">Rp {{ number_format($cogsItems->where('account_code', '5000-02-01')->sum('debit'), 0, ',', '.') }}</td>
                </tr>
                <tr class="row-total" style="color: #b91c1c;">
                    <td class="font-bold">2. TOTAL BEBAN POKOK PENDAPATAN (HPP / COGS)</td>
                    <td class="text-center font-bold">{{ $closing->cogs_ratio }}%</td>
                    <td class="text-right font-bold">(Rp {{ number_format($closing->total_cogs, 0, ',', '.') }})</td>
                </tr>
                <tr class="row-highlight">
                    <td class="font-bold">3. LABA KOTOR (GROSS PROFIT)</td>
                    <td class="text-center font-bold">{{ $closing->total_revenue > 0 ? round(($closing->gross_profit / $closing->total_revenue) * 100, 1) : 0 }}%</td>
                    <td class="text-right font-black">Rp {{ number_format($closing->gross_profit, 0, ',', '.') }}</td>
                </tr>
                @php
                    $opexGroupList = $opexItems->groupBy('account_code');
                    $opexPdfCategoryNames = [
                        '6000-01-01' => 'Beban Pemasaran & Iklan Berbayar (Marketing Ads)',
                        '6000-02-01' => 'Beban Operasional Kantor Umum & Tracker',
                        '6000-02-02' => 'Beban Operasional Tetap Lainnya',
                        '6000-03-01' => 'Beban Utilitas & Fasilitas (Listrik, Air, Internet)',
                        '6000-04-01' => 'Beban Langganan Software, Tools & Layanan SaaS',
                        '6000-05-01' => 'Beban Tetap (PAYROLL): Gaji Pokok Struktural / Manajerial',
                        '6000-06-01' => 'Beban Infrastruktur Server & Hosting Kantor',
                    ];
                @endphp
                @forelse($opexGroupList as $code => $groupItems)
                    @php
                        $groupDebit = $groupItems->sum('debit');
                        $groupTitle = $opexPdfCategoryNames[$code] ?? ($groupItems->first()?->account_name ?? 'Beban Operasional ' . $code);
                        $ratio = $closing->total_revenue > 0 ? round(($groupDebit / $closing->total_revenue) * 100, 1) : 0;
                    @endphp
                    <tr>
                        <td style="padding-left: 15px;">
                            <span style="font-family: 'Courier New', Courier, monospace; font-size: 8px; font-weight: bold; color: #444;">[{{ $code }}]</span>
                            {{ $groupTitle }}
                            @if($groupItems->count() > 1)
                                <span style="font-size: 7.5px; color: #666;">({{ $groupItems->count() }} pos)</span>
                            @endif
                            @if($groupItems->count() == 1 && $groupItems->first()?->notes)
                                <div style="font-size: 7.5px; color: #666; padding-left: 8px; margin-top: 1px;">
                                    {{ \Illuminate\Support\Str::limit($groupItems->first()->notes, 65) }}
                                </div>
                            @endif
                        </td>
                        <td class="text-center">{{ $ratio }}%</td>
                        <td class="text-right">Rp {{ number_format($groupDebit, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td style="padding-left: 15px; font-style: italic; color: #777;">Beban Operasional Nihil</td>
                        <td class="text-center">0.0%</td>
                        <td class="text-right">Rp 0</td>
                    </tr>
                @endforelse
                <tr class="row-total" style="color: #b45309;">
                    <td class="font-bold">4. TOTAL BEBAN OPERASIONAL (OPEX)</td>
                    <td class="text-center font-bold">{{ $closing->opex_ratio }}%</td>
                    <td class="text-right font-bold">(Rp {{ number_format($closing->total_opex, 0, ',', '.') }})</td>
                </tr>
                <tr style="background-color: #dcfce7;">
                    <td class="font-black" style="font-size: 10.5px;">5. LABA BERSIH OPERASIONAL (NET PROFIT)</td>
                    <td class="text-center font-black">{{ $closing->net_profit_margin }}%</td>
                    <td class="text-right font-black" style="font-size: 10.5px;">Rp {{ number_format($closing->net_profit, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <!-- 2. Rekonsiliasi Arus Kas -->
        <div class="section-title">II. REKONSILIASI ARUS KAS (CASH FLOW RECONCILIATION)</div>
        <table class="financial-table">
            <thead>
                <tr>
                    <th style="width: 70%;">Keterangan Pos Arus Kas</th>
                    <th style="width: 30%; text-align: right;">Nominal (IDR)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Saldo Kas & Rekening Bank Awal Periode</td>
                    <td class="text-right font-bold">Rp {{ number_format($closing->opening_cash_balance, 0, ',', '.') }}</td>
                </tr>
                <tr style="color: #15803d;">
                    <td>(+) Penerimaan Kas Masuk (Customer Payments Inflow)</td>
                    <td class="text-right font-bold">+ Rp {{ number_format($closing->cash_inflow, 0, ',', '.') }}</td>
                </tr>
                <tr style="color: #b91c1c;">
                    <td>(-) Pengeluaran Kas Keluar Realisasi (COGS & OPEX Outflow)</td>
                    <td class="text-right font-bold">- Rp {{ number_format($closing->cash_outflow, 0, ',', '.') }}</td>
                </tr>
                <tr class="row-highlight">
                    <td class="font-black">(=) Saldo Kas & Rekening Bank Akhir Rekonsiliasi</td>
                    <td class="text-right font-black">Rp {{ number_format($closing->ending_cash_balance, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <!-- 3. Catatan Auditor -->
        @if($closing->auditor_notes)
        <div class="section-title">III. CATATAN & REKOMENDASI AUDITOR</div>
        <div style="background: #f8fafc; border: 1px solid #cbd5e1; padding: 6px 8px; font-size: 9.5px; white-space: pre-line; margin-bottom: 10px;">
            {{ $closing->auditor_notes }}
        </div>
        @endif

        <!-- Signatures Table -->
        <table class="signatures-table">
            <tr>
                <td>
                    <div>Dibuat & Direkonsiliasi Oleh,</div>
                    <div class="sign-space"></div>
                    <div class="sign-name">{{ $closing->auditor?->name ?? 'Auditor Keuangan' }}</div>
                    <div class="sign-title">Finance & Project Accountant</div>
                </td>
                <td>
                    <div>Disetujui & Ditetapkan Oleh,</div>
                    <div class="sign-space"></div>
                    <div class="sign-name">{{ $closing->approver?->name ?? 'Managing Partner' }}</div>
                    <div class="sign-title">Chief Executive / Managing Director</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Page break eksplisit DomPDF -->
    <div style="page-break-after: always;"></div>

    <!-- ========================================== -->
    <!-- HALAMAN KEDUA: RINCIAN MENDALAM TRANSAKSI  -->
    <!-- ========================================== -->
    <div class="container">
        <div class="doc-title-box">
            <div class="doc-title" style="font-size: 13px;">LAMPIRAN: RINCIAN BUKU BESAR &amp; TRANSAKSI CLOSING</div>
            <div class="doc-subtitle">PERIODE: {{ strtoupper(\Carbon\Carbon::parse($closing->period_month . '-01')->translatedFormat('F Y')) }} ({{ $closing->period_month }})</div>
        </div>

        <table style="width: 100%; margin-bottom: 8px; font-size: 9px; border-collapse: collapse; color: #333;">
            <tr>
                <td style="width: 50%;"><strong>Entitas:</strong> Wirodayan Digital (Wirodev)</td>
                <td style="width: 50%; text-align: right;"><strong>Status Audit:</strong> {{ strtoupper($closing->audit_status) }} &nbsp;|&nbsp; <strong>Tgl Cetak:</strong> {{ date('d/m/Y') }}</td>
            </tr>
        </table>

        <table class="ledger-table">
            <thead>
                <tr>
                    <th style="width: 4%; text-align: center;">No</th>
                    <th style="width: 13%; text-align: center;">Kode Akun</th>
                    <th style="width: 27%;">Nama Akun &amp; Kategori</th>
                    <th style="width: 16%;">Proyek Terkait</th>
                    <th style="width: 12%; text-align: right;">Debit (Rp)</th>
                    <th style="width: 12%; text-align: right;">Kredit (Rp)</th>
                    <th style="width: 16%;">Keterangan / Catatan</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $allItems = $items ?? $closing->items;
                    $totalDebit = 0;
                    $totalCredit = 0;
                @endphp
                @forelse($allItems as $item)
                    @php
                        $totalDebit += (float)$item->debit;
                        $totalCredit += (float)$item->credit;
                    @endphp
                    <tr>
                        <td style="text-align: center;">{{ $loop->iteration }}</td>
                        <td style="text-align: center; font-family: 'Courier New', Courier, monospace; font-size: 8.5px; font-weight: bold;">
                            {{ $item->account_code }}
                        </td>
                        <td>
                            <div style="font-weight: bold; color: #000;">{{ $item->account_name }}</div>
                            <div style="font-size: 8px; color: #555; text-transform: uppercase;">
                                Kat: {{ strtoupper(str_replace('_', ' ', $item->category)) }}
                            </div>
                        </td>
                        <td>
                            {{ $item->project?->name ?? $item->project?->title ?? '-' }}
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            {{ $item->debit > 0 ? number_format($item->debit, 0, ',', '.') : '-' }}
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            {{ $item->credit > 0 ? number_format($item->credit, 0, ',', '.') : '-' }}
                        </td>
                        <td style="color: #333; font-size: 8.5px;">
                            {{ $item->notes ?: '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 15px; color: #666; font-style: italic;">
                            Tidak ada rincian item buku besar pada periode ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="row-ledger-total">
                    <td colspan="4" style="text-align: right; font-weight: bold; text-transform: uppercase; padding-right: 8px;">
                        TOTAL DEBIT &amp; KREDIT:
                    </td>
                    <td style="text-align: right; font-weight: bold; white-space: nowrap;">
                        Rp {{ number_format($totalDebit, 0, ',', '.') }}
                    </td>
                    <td style="text-align: right; font-weight: bold; white-space: nowrap;">
                        Rp {{ number_format($totalCredit, 0, ',', '.') }}
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        <div style="margin-top: 10px; font-size: 8.5px; color: #666; text-align: right; font-style: italic;">
            * Lampiran ini merupakan rekonsiliasi rincian transaksi buku besar resmi yang menyertai Laporan Closing Keuangan Bulanan periode {{ $closing->period_month }}.
        </div>
    </div>

</body>
</html>
