<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Berita Acara RUPS {{ $closing->period_label }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #1e293b;
            line-height: 1.35;
            margin: 0;
            padding: 0;
            background: #fff;
            font-size: 9.5px;
        }
        .container {
            padding: 16px 22px;
        }

        /* Kop Surat Styles */
        @include('documents.pdf.partials.kop_style')

        .kop-surat {
            margin-bottom: 10px;
            padding-bottom: 6px;
        }

        .doc-title-box {
            text-align: center;
            margin-bottom: 12px;
        }
        .doc-title {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 2px solid #0f172a;
            display: inline-block;
            padding: 0 16px 3px 16px;
            letter-spacing: 0.5px;
            color: #0f172a;
        }
        .doc-subtitle {
            font-size: 10px;
            color: #475569;
            margin-top: 3px;
            font-weight: 600;
        }

        .meta-table {
            width: 100%;
            margin-bottom: 10px;
            font-size: 9.5px;
            border-collapse: collapse;
        }
        .meta-table td {
            padding: 2.5px 0;
            vertical-align: top;
        }

        .section-title {
            font-size: 10.5px;
            font-weight: bold;
            text-transform: uppercase;
            background: #f1f5f9;
            padding: 4px 8px;
            border-left: 3.5px solid #003399;
            margin-top: 10px;
            margin-bottom: 6px;
            color: #0f172a;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .data-table th {
            background: #f8fafc;
            padding: 4px 6px;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            border: 1px solid #cbd5e1;
        }
        .data-table td {
            padding: 4px 6px;
            border: 1px solid #cbd5e1;
            font-size: 9px;
            vertical-align: middle;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .font-black { font-weight: 900; }
        .font-mono { font-family: 'Courier New', Courier, monospace; }

        .row-total {
            background-color: #f8fafc;
            font-weight: bold;
        }

        .summary-box {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            padding: 6px 10px;
            margin-bottom: 10px;
            border-radius: 4px;
        }

        .signatures-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
            page-break-inside: avoid;
        }
        .signatures-table td {
            vertical-align: top;
            text-align: center;
            padding: 6px;
        }
        .signature-line {
            margin-top: 45px;
            border-top: 1px solid #334155;
            font-weight: bold;
            padding-top: 3px;
            font-size: 9px;
            color: #0f172a;
        }
        .signature-role {
            font-size: 8px;
            color: #64748b;
            margin-top: 1px;
        }
    </style>
</head>
<body>
<div class="container">
    {{-- 1. Kop Surat Resmi --}}
    @include('documents.pdf.partials.kop')

    {{-- 2. Judul & Nomor Surat --}}
    <div class="doc-title-box">
        <div class="doc-title">BERITA ACARA RAPAT UMUM PEMEGANG SAHAM (RUPS)</div>
        <div class="doc-subtitle">
            Nomor: {{ $distribution?->document_number ?? ('BA-RUPS/' . $closing->year . '-Q' . $closing->quarter . '/WD/01') }}
        </div>
    </div>

    {{-- 3. Data Waktu & Tempat RUPS --}}
    <table class="meta-table">
        <tr>
            <td style="width: 18%;" class="font-bold">Periode Buku</td>
            <td style="width: 2%;">:</td>
            <td style="width: 30%;">Kuartal {{ $closing->quarter }} (Tahun Buku {{ $closing->year }}) - {{ $closing->period_label }}</td>
            <td style="width: 18%;" class="font-bold">Tanggal RUPS</td>
            <td style="width: 2%;">:</td>
            <td style="width: 30%;">
                {{ $distribution?->distribution_date ? $distribution->distribution_date->translatedFormat('d F Y') : now()->translatedFormat('d F Y') }}
            </td>
        </tr>
        <tr>
            <td class="font-bold">Tempat Pelaksanaan</td>
            <td>:</td>
            <td>Kantor Operasional Wirodayan Digital, Banyuwangi</td>
            <td class="font-bold">Status Putusan</td>
            <td>:</td>
            <td>
                <span class="font-bold" style="color: #059669; text-transform: uppercase;">
                    {{ $distribution?->status ?? 'APPROVED (SAH)' }}
                </span>
            </td>
        </tr>
        <tr>
            <td class="font-bold">Daftar Hadir Direksi & Pendiri</td>
            <td>:</td>
            <td colspan="4">
                {{ $shareholders->pluck('name')->implode(', ') }} (Kuorum 100% Tercapai)
            </td>
        </tr>
    </table>

    {{-- 4. Ringkasan Kinerja Keuangan Triwulanan --}}
    <div class="section-title">I. Ringkasan Kinerja Keuangan Konsolidasi (Closing Triwulan)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Indikator Keuangan</th>
                <th class="text-right">Nominal (Rupiah)</th>
                <th>Keterangan / Rasio Evaluasi</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Total Pendapatan Usaha (Revenue)</td>
                <td class="text-right font-mono font-bold">Rp {{ number_format($closing->total_revenue, 0, ',', '.') }}</td>
                <td>Pendapatan dari seluruh invoice dan proyek terealisasi</td>
            </tr>
            <tr>
                <td>Beban Pokok Pendapatan (HPP / COGS)</td>
                <td class="text-right font-mono font-bold">Rp {{ number_format($closing->total_cogs, 0, ',', '.') }}</td>
                <td>Honor teknis langsung pengerjaan proyek klien</td>
            </tr>
            <tr>
                <td>Laba Kotor (Gross Profit)</td>
                <td class="text-right font-mono font-bold">Rp {{ number_format($closing->gross_profit, 0, ',', '.') }}</td>
                <td>Marjin Kotor Perseroan</td>
            </tr>
            <tr>
                <td>Total Beban Operasional (OPEX)</td>
                <td class="text-right font-mono font-bold">Rp {{ number_format($closing->total_opex, 0, ',', '.') }}</td>
                <td>Biaya tetap, server, iklan, dan operasional kantor</td>
            </tr>
            <tr style="background-color: #f1f5f9;">
                <td class="font-bold">Laba Bersih Usaha (Net Profit)</td>
                <td class="text-right font-mono font-black" style="color: #0f172a;">Rp {{ number_format($closing->net_profit, 0, ',', '.') }}</td>
                <td class="font-bold">NPM: {{ number_format($closing->net_profit_margin, 2) }}%</td>
            </tr>
            <tr>
                <td>Posisi Kas Bank Akhir Kuartal</td>
                <td class="text-right font-mono font-bold">Rp {{ number_format($closing->ending_cash_balance, 0, ',', '.') }}</td>
                <td>Saldo likuiditas kas bank pada akhir periode</td>
            </tr>
            <tr>
                <td>Batas Aman Kas Cadangan (Safety Cash Floor)</td>
                <td class="text-right font-mono font-bold">Rp {{ number_format($closing->cash_floor_target, 0, ',', '.') }}</td>
                <td>Cadangan minimum statis untuk proteksi kelangsungan usaha</td>
            </tr>
            <tr style="background-color: #f8fafc;">
                <td class="font-bold">Plafon Likuiditas Kas Aman Di Atas Floor</td>
                <td class="text-right font-mono font-black" style="color: #0284c7;">Rp {{ number_format($closing->cash_surplus_above_floor, 0, ',', '.') }}</td>
                <td>Maksimal kas yang diizinkan dicairkan untuk dividen tunai</td>
            </tr>
        </tbody>
    </table>

    {{-- 5. Keputusan Alokasi Laba & Dividen RUPS --}}
    <div class="section-title">II. Keputusan Alokasi Laba Bersih & Dividen</div>
    <div class="summary-box">
        <table style="width: 100%; font-size: 9.5px;">
            <tr>
                <td style="width: 33%;">
                    <strong>Cadangan Laba Ditahan (Retained):</strong><br>
                    <span class="font-mono font-bold">
                        {{ number_format($distribution?->retained_earnings_percentage ?? 20, 2) }}%
                        (Rp {{ number_format($distribution?->retained_earnings_amount ?? 0, 0, ',', '.') }})
                    </span>
                </td>
                <td style="width: 33%;">
                    <strong>Total Dividen yang Disahkan:</strong><br>
                    <span class="font-mono font-black" style="color: #059669;">
                        Rp {{ number_format($distribution?->distributable_profit ?? 0, 0, ',', '.') }}
                    </span>
                </td>
                <td style="width: 34%;">
                    <strong>Dividen Tunai vs Reinvestasi:</strong><br>
                    <span class="font-mono">
                        Tunai: Rp {{ number_format($distribution?->total_cash_payout ?? 0, 0, ',', '.') }} |
                        Reinvest: Rp {{ number_format($distribution?->total_reinvested_capital ?? 0, 0, ',', '.') }}
                    </span>
                </td>
            </tr>
        </table>
    </div>

    {{-- Tabel Keputusan Pembagian Dividen Per Pemegang Saham --}}
    <table class="data-table">
        <thead>
            <tr>
                <th>Pemegang Saham</th>
                <th class="text-center">Porsi Saham</th>
                <th class="text-right">Total Hak Dividen</th>
                <th class="text-right">Dividen Tunai (Bank)</th>
                <th class="text-right">Dividen Reinvestasi Modal</th>
                <th class="text-center">% Reinvest</th>
            </tr>
        </thead>
        <tbody>
            @php
                $payouts = $distribution?->payouts ?? collect();
            @endphp
            @forelse($payouts as $payout)
            <tr>
                <td>
                    <div class="font-bold">{{ $payout->shareholder->name }}</div>
                    <div style="font-size: 8px; color: #64748b;">
                        {{ $payout->shareholder->bank_name }} - {{ $payout->shareholder->bank_account_number }}
                    </div>
                </td>
                <td class="text-center font-mono">
                    {{ number_format($payout->shares_count, 0, ',', '.') }} lbr
                    ({{ number_format($payout->ownership_percentage, 2) }}%)
                </td>
                <td class="text-right font-mono font-bold">
                    Rp {{ number_format($payout->payout_amount, 0, ',', '.') }}
                </td>
                <td class="text-right font-mono font-bold" style="color: #0284c7;">
                    Rp {{ number_format($payout->cash_amount, 0, ',', '.') }}
                </td>
                <td class="text-right font-mono font-bold" style="color: #059669;">
                    Rp {{ number_format($payout->reinvest_amount, 0, ',', '.') }}
                </td>
                <td class="text-center font-mono">
                    {{ number_format($payout->reinvest_percentage, 2) }}%
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center" style="color: #94a3b8; padding: 10px;">
                    Rincian dividen belum disahkan.
                </td>
            </tr>
            @endforelse
            <tr class="row-total">
                <td colspan="2" class="font-bold">TOTAL PEMBAGIAN</td>
                <td class="text-right font-mono font-black">
                    Rp {{ number_format($payouts->sum('payout_amount'), 0, ',', '.') }}
                </td>
                <td class="text-right font-mono font-black" style="color: #0284c7;">
                    Rp {{ number_format($payouts->sum('cash_amount'), 0, ',', '.') }}
                </td>
                <td class="text-right font-mono font-black" style="color: #059669;">
                    Rp {{ number_format($payouts->sum('reinvest_amount'), 0, ',', '.') }}
                </td>
                <td class="text-center">-</td>
            </tr>
        </tbody>
    </table>

    {{-- 6. Tabel Posisi Permodalan Kuartal Berikutnya (Capital Structure Roll-Forward) --}}
    @php
        $nextQuarterYear = $closing->quarter === 4 ? $closing->year + 1 : $closing->year;
        $nextQuarterNum = $closing->quarter === 4 ? 1 : $closing->quarter + 1;
        $nextQuarterLabel = sprintf('%04d-Q%d', $nextQuarterYear, $nextQuarterNum);
    @endphp
    <div class="section-title">III. Struktur Permodalan & Ekuitas Efektif Kuartal Berikutnya ({{ $nextQuarterLabel }})</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Pemegang Saham</th>
                <th class="text-right">Modal Sebelum RUPS</th>
                <th class="text-right">Reinvestasi Dividen</th>
                <th class="text-right font-bold">Modal Disetor Baru</th>
                <th class="text-center font-bold">Porsi Kepemilikan Baru (%)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($shareholders as $sh)
            @php
                $payout = $payouts->firstWhere('shareholder_id', $sh->id);
                $reinvest = (float)($payout?->reinvest_amount ?? 0);
                $hist = $closing->capitalHistories->firstWhere('shareholder_id', $sh->id);
                $openingCap = $hist ? (float)$hist->opening_capital : (float)$sh->capital_amount - $reinvest;
                $closingCap = $hist ? (float)$hist->closing_capital : (float)$sh->capital_amount;
                $closingPct = $hist ? (float)$hist->closing_ownership_percentage : (float)$sh->ownership_percentage;
            @endphp
            <tr>
                <td class="font-bold">{{ $sh->name }}</td>
                <td class="text-right font-mono">Rp {{ number_format(max(0, $openingCap), 0, ',', '.') }}</td>
                <td class="text-right font-mono font-bold" style="color: #059669;">+ Rp {{ number_format($reinvest, 0, ',', '.') }}</td>
                <td class="text-right font-mono font-bold">Rp {{ number_format($closingCap, 0, ',', '.') }}</td>
                <td class="text-center font-mono font-black" style="color: #003399;">{{ number_format($closingPct, 2) }}%</td>
            </tr>
            @endforeach
            <tr class="row-total">
                <td class="font-bold">TOTAL MODAL DISETOR PERSEROAN</td>
                <td colspan="2" class="text-right"></td>
                <td class="text-right font-mono font-black">
                    Rp {{ number_format($distribution?->next_quarter_total_capital ?? $shareholders->sum('capital_amount'), 0, ',', '.') }}
                </td>
                <td class="text-center font-mono font-black">100.00%</td>
            </tr>
        </tbody>
    </table>

    {{-- Catatan RUPS --}}
    @if($distribution?->notes)
    <div style="margin-top: 8px; font-size: 8.5px; color: #475569; background: #f8fafc; padding: 6px 8px; border-left: 2px solid #94a3b8;">
        <strong>Catatan Musyawarah:</strong> {{ $distribution->notes }}
    </div>
    @endif

    {{-- 7. Lembar Tanda Tangan Notulensi --}}
    <table class="signatures-table">
        <tr>
            <td style="width: 30%;">
                <div style="font-size: 8.5px; color: #64748b;">Mengetahui & Menyetujui,</div>
                <div style="font-weight: bold; font-size: 9px; margin-top: 2px;">Direktur Utama</div>
                <div class="signature-line">
                    {{ $shareholders->first()?->name ?? 'Titan Anggaraksa' }}
                </div>
                <div class="signature-role">Direktur Utama Wirodayan Digital</div>
            </td>
            <td style="width: 40%;">
                <div style="font-size: 8.5px; color: #64748b;">Notulis Rapat / Konsultan Keuangan,</div>
                <div style="font-weight: bold; font-size: 9px; margin-top: 2px;">Finance & Auditor Internal</div>
                <div class="signature-line">
                    {{ $closing->approver?->name ?? 'Auditor Keuangan Perseroan' }}
                </div>
                <div class="signature-role">Wirodayan Corporate Finance</div>
            </td>
            <td style="width: 30%;">
                <div style="font-size: 8.5px; color: #64748b;">Mewakili Pemegang Saham,</div>
                <div style="font-weight: bold; font-size: 9px; margin-top: 2px;">Mitra Pendiri / Komisaris</div>
                <div class="signature-line">
                    {{ $shareholders->count() > 1 ? $shareholders[1]->name : 'Bima Jati' }}
                </div>
                <div class="signature-role">Mitra Pendiri / Pemegang Saham</div>
            </td>
        </tr>
    </table>
</div>
</body>
</html>
