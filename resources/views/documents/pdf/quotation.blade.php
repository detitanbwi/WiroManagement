<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Quotation {{ $quotation->quotation_number }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #333; line-height: 1.4; margin: 0; padding: 0; background: #fff; font-size: 11px; }
        .container { padding: 30px; }
        
        @include('documents.pdf.partials.kop_style')
        
        .title-box { text-align: center; margin-bottom: 25px; }
        .title { font-size: 16px; font-weight: bold; text-transform: uppercase; border-bottom: 1.5px solid #333; display: inline-block; padding: 0 40px 3px 40px; }
        
        .info-table { width: 100%; margin-bottom: 25px; }
        .info-left { width: 50%; vertical-align: top; }
        .info-right { width: 50%; vertical-align: top; }

        .client-box { border: 1px solid #e5e7eb; padding: 12px; border-radius: 5px; background: #f9fafb; }

        .table { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
        .table th { background: #2563eb; padding: 10px; text-align: left; font-size: 10px; font-weight: bold; color: #fff; text-transform: uppercase; }
        .table td { padding: 12px 10px; border-bottom: 1px solid #f3f4f6; font-size: 11px; vertical-align: top; }
        
        .total-row { background: #f8fafc; font-weight: bold; }
        .total-label { font-size: 10px; text-transform: uppercase; color: #666; }
        .total-value { font-size: 14px; color: #2563eb; font-weight: 900; }

        .description-box { margin-bottom: 25px; padding: 12px; border: 1px solid #e5e7eb; border-radius: 5px; }
        .section-title { font-size: 10px; font-weight: bold; color: #2563eb; margin-bottom: 8px; text-transform: uppercase; border-left: 3px solid #2563eb; padding-left: 8px; }

        .footer { margin-top: 40px; }
        .signature-box { float: right; width: 220px; text-align: center; }
        .notes-box { float: left; width: 55%; font-size: 9px; color: #666; }
        
        /* Rich Text / T&C Styles */
        .terms-page { page-break-before: always; padding: 20px 0; }
        .terms-title { font-size: 16px; font-weight: bold; margin-bottom: 20px; border-bottom: 1px solid #333; padding-bottom: 8px; }
        
        .rich-text-content { line-height: 1.4; text-align: left; }
        .rich-text-content h1, .rich-text-content h2, .rich-text-content h3 { color: #111; margin-top: 12px; margin-bottom: 6px; }
        .rich-text-content h1 { font-size: 13px; }
        .rich-text-content h2 { font-size: 11px; }
        .rich-text-content h3 { font-size: 10px; }
        .rich-text-content p { margin-bottom: 8px; }
        .rich-text-content ul, .rich-text-content ol { margin-left: 15px; margin-bottom: 8px; }
        .rich-text-content li { margin-bottom: 3px; }
        .rich-text-content strong { color: #111; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Kop Surat -->
        @include('documents.pdf.partials.kop')

        <div class="title-box">
            <div class="title">QUOTATION / PENAWARAN HARGA</div>
        </div>

        <table class="info-table">
            <tr>
                <td class="info-left">
                    <div class="client-box">
                        <div style="font-size: 9px; font-weight: bold; color: #6b7280; text-transform: uppercase; margin-bottom: 4px;">Kepada Yth:</div>
                        <div style="font-size: 13px; font-weight: bold; color: #111827;">{{ $client->name }}</div>
                        @if($client->company_name)
                            <div style="font-size: 11px; font-weight: bold; color: #4b5563; margin-top: 1px;">{{ $client->company_name }}</div>
                        @endif
                        <div style="color: #6b7280; font-size: 10px; margin-top: 4px; line-height: 1.5; white-space: pre-line;">{{ $client->address }}</div>
                    </div>
                </td>
                <td class="info-right" style="padding-left: 15px;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 11px;">
                        <tr>
                            <td style="font-weight: bold; color: #6b7280; padding: 3px 0; width: 110px; vertical-align: top;">No Quotation</td>
                            <td style="padding: 3px 5px; font-weight: bold; vertical-align: top;">: {{ $quotation->quotation_number }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; color: #6b7280; padding: 3px 0; vertical-align: top;">Tanggal</td>
                            <td style="padding: 3px 5px; vertical-align: top;">: {{ $quotation->created_at->format('d F Y') }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; color: #6b7280; padding: 3px 0; vertical-align: top;">Berlaku Sampai</td>
                            <td style="padding: 3px 5px; color: #dc2626; vertical-align: top;">: {{ $quotation->created_at->addDays(14)->format('d F Y') }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; color: #6b7280; padding: 3px 0; vertical-align: top;">Durasi Kerja</td>
                            <td style="padding: 3px 5px; vertical-align: top;">: {{ $quotation->working_duration }}</td>
                        </tr>
                        @if($quotation->warranty_days > 0)
                        <tr>
                            <td style="font-weight: bold; color: #6b7280; padding: 3px 0; vertical-align: top;">Garansi</td>
                            <td style="padding: 3px 5px; vertical-align: top;">: {{ $quotation->warranty_days }} Hari</td>
                        </tr>
                        @endif
                    </table>
                </td>
            </tr>
        </table>

        <div class="description-box">
            <div class="section-title">Lingkup Pekerjaan & Deskripsi Proyek</div>
            <div style="font-weight: bold; font-size: 12px; margin-bottom: 6px; color: #111827;">{{ $quotation->project->title }}</div>
            <div class="rich-text-content" style="font-size: 11px; color: #4b5563;">
                {!! $quotation->description ?? 'Pengembangan sistem software sesuai dengan rincian teknis yang telah didiskusikan sebelumnya.' !!}
            </div>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th style="width: 70%;">DESKRIPSI INVESTASI</th>
                    <th style="text-align: right; width: 30%;">TOTAL HARGA</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <div style="font-weight: bold; color: #111827;">Biaya Pengembangan Proyek (One-Time Cost)</div>
                        <div style="font-size: 10px; color: #6b7280; margin-top: 4px;">Sesuai dengan lingkup pekerjaan yang tertera pada bagian rincian deskripsi di atas.</div>
                    </td>
                    <td style="text-align: right; font-weight: bold; vertical-align: middle;">
                        Rp {{ number_format($quotation->total_amount, 0, ',', '.') }}
                    </td>
                </tr>
                <tr class="total-row">
                    <td style="text-align: right; border-bottom: none; vertical-align: middle; padding: 15px 10px;">
                        <span class="total-label">Total Nilai Investasi (Subtotal):</span>
                    </td>
                    <td style="text-align: right; border-bottom: none; vertical-align: middle;">
                        <span class="total-value">Rp {{ number_format($quotation->total_amount, 0, ',', '.') }}</span>
                    </td>
                </tr>
            </tbody>
        </table>

        <div style="font-size: 9px; color: #6b7280; font-style: italic; margin-bottom: 35px;">
            <span style="font-weight: bold; color: #4b5563;">Terbilang:</span> {{ App\Http\Controllers\ProjectController::formatTerbilang($quotation->total_amount) }} Rupiah.
        </div>

        <div class="footer">
            <div class="signature-box">
                <p style="margin-bottom: 65px;">Banyuwangi, {{ date('d F Y') }}</p>
                <p style="font-weight: bold; text-decoration: underline; margin-bottom: 2px; font-size: 12px;">WIRODEV ADMINISTRATION</p>
                <p style="font-size: 9px; color: #6b7280; text-transform: uppercase; font-weight: bold;">Authorized Representative</p>
            </div>
            
            <div class="notes-box">
                @if($notes)
                    <p style="font-weight: bold; color: #374151; margin-bottom: 5px; text-decoration: underline;">Catatan Penawaran:</p>
                    <div class="rich-text-content" style="font-size: 10px; color: #666;">
                        {!! $notes !!}
                    </div>
                @endif
            </div>
            <div style="clear: both;"></div>
        </div>
        @if($terms)
        <div class="terms-page">
            <div class="terms-title">SYARAT & KETENTUAN LAYANAN</div>
            <div class="rich-text-content" style="font-size: 10px; line-height: 1.6;">
                {!! $terms !!}
            </div>
            
            <div style="margin-top: 50px; border-top: 1px dashed #ccc; padding-top: 20px;">
                <table style="width: 100%;">
                    <tr>
                        <td style="width: 100%; font-size: 10px; color: #444; font-style: italic; text-align: center; line-height: 1.5;">
                            "Dengan melakukan pembayaran Down Payment (DP), Klien dinyatakan telah membaca, memahami, dan menyetujui seluruh Syarat & Ketentuan yang berlaku di Wirodayan Digital tanpa terkecuali."
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align: right; font-size: 9px; color: #999; font-style: italic; padding-top: 15px;">
                            Halaman ini merupakan lampiran tidak terpisahkan dari Quotation #{{ $quotation->quotation_number }}
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        @endif
    </div>
</body>
</html>
