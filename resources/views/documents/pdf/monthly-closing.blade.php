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
            font-size: 11px;
        }
        .container {
            padding: 20px;
        }

        /* Kop Surat Styles */
        @include('documents.pdf.partials.kop_style')

        .doc-title-box {
            text-align: center;
            margin-bottom: 20px;
        }
        .doc-title {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 2px solid #000;
            display: inline-block;
            padding: 0 15px 4px 15px;
            letter-spacing: 0.5px;
        }
        .doc-subtitle {
            font-size: 11px;
            color: #555;
            margin-top: 4px;
            font-weight: 600;
        }

        .meta-table {
            width: 100%;
            margin-bottom: 18px;
            font-size: 11px;
            border-collapse: collapse;
        }
        .meta-table td {
            padding: 3px 0;
            vertical-align: top;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            background: #f1f5f9;
            padding: 6px 10px;
            border-left: 4px solid #003399;
            margin-top: 15px;
            margin-bottom: 8px;
            color: #0f172a;
        }

        .financial-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .financial-table th {
            background: #f8fafc;
            padding: 6px 8px;
            text-align: left;
            font-size: 10px;
            font-weight: bold;
            color: #000;
            text-transform: uppercase;
            border: 1px solid #cbd5e1;
        }
        .financial-table td {
            padding: 6px 8px;
            border: 1px solid #cbd5e1;
            font-size: 10px;
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
            top: 130px;
            right: 40px;
            border: 3px solid #22c55e;
            color: #22c55e;
            padding: 6px 16px;
            font-weight: bold;
            font-size: 16px;
            opacity: 0.7;
            transform: rotate(-10deg);
            border-radius: 4px;
        }

        .signatures-table {
            width: 100%;
            margin-top: 30px;
            border-collapse: collapse;
        }
        .signatures-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            font-size: 11px;
        }
        .sign-space {
            height: 65px;
        }
        .sign-name {
            font-weight: bold;
            border-bottom: 1px solid #000;
            display: inline-block;
            padding: 0 25px;
        }
        .sign-title {
            color: #555;
            font-size: 10px;
            margin-top: 3px;
        }
    </style>
</head>
<body>
    @if($closing->audit_status === 'approved')
        <div class="status-badge">APPROVED</div>
    @elseif($closing->audit_status === 'audited')
        <div class="status-badge" style="border-color: #2563eb; color: #2563eb;">AUDITED</div>
    @else
        <div class="status-badge" style="border-color: #f59e0b; color: #f59e0b;">DRAFT</div>
    @endif

    <div class="container">
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
                    <td style="padding-left: 20px;">HPP Beban Tenaga Kerja Langsung Proyek (Man-Hour)</td>
                    <td class="text-center">{{ $closing->total_revenue > 0 ? round(($cogsItems->where('account_code', '5000-01-01')->sum('debit') / $closing->total_revenue) * 100, 1) : 0 }}%</td>
                    <td class="text-right">Rp {{ number_format($cogsItems->where('account_code', '5000-01-01')->sum('debit'), 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td style="padding-left: 20px;">HPP Beban Infrastruktur & Lisensi Server Proyek</td>
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
                <tr>
                    <td style="padding-left: 20px;">Beban Pemasaran & Iklan Berbayar (Marketing Ads)</td>
                    <td class="text-center">{{ $closing->total_revenue > 0 ? round(($opexItems->where('account_code', '6000-01-01')->sum('debit') / $closing->total_revenue) * 100, 1) : 0 }}%</td>
                    <td class="text-right">Rp {{ number_format($opexItems->where('account_code', '6000-01-01')->sum('debit'), 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td style="padding-left: 20px;">Beban Utilitas & Operasional Kantor Umum</td>
                    <td class="text-center">{{ $closing->total_revenue > 0 ? round(($opexItems->where('account_code', '6000-02-01')->sum('debit') / $closing->total_revenue) * 100, 1) : 0 }}%</td>
                    <td class="text-right">Rp {{ number_format($opexItems->where('account_code', '6000-02-01')->sum('debit'), 0, ',', '.') }}</td>
                </tr>
                <tr class="row-total" style="color: #b45309;">
                    <td class="font-bold">4. TOTAL BEBAN OPERASIONAL (OPEX)</td>
                    <td class="text-center font-bold">{{ $closing->opex_ratio }}%</td>
                    <td class="text-right font-bold">(Rp {{ number_format($closing->total_opex, 0, ',', '.') }})</td>
                </tr>
                <tr style="background-color: #dcfce7;">
                    <td class="font-black" style="font-size: 11px;">5. LABA BERSIH OPERASIONAL (NET PROFIT)</td>
                    <td class="text-center font-black">{{ $closing->net_profit_margin }}%</td>
                    <td class="text-right font-black" style="font-size: 11px;">Rp {{ number_format($closing->net_profit, 0, ',', '.') }}</td>
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
        <div style="background: #f8fafc; border: 1px solid #cbd5e1; padding: 10px; font-size: 10px; white-space: pre-line; margin-bottom: 20px;">
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
</body>
</html>
