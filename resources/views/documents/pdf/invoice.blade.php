<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #333; line-height: 1.2; margin: 0; padding: 0; background: #fff; }
        .container { padding: 20px; }
        
        /* Kop Surat Styles */
        @include('documents.pdf.partials.kop_style')
        
        .invoice-title-box { text-align: center; margin-bottom: 20px; }
        .invoice-title { font-size: 18px; font-weight: bold; text-transform: uppercase; border-bottom: 1px solid #000; display: inline-block; padding: 0 20px; }
        
        .info-table { width: 100%; margin-bottom: 30px; font-size: 13px; }
        .info-left { width: 60%; vertical-align: top; }
        .info-right { width: 40%; vertical-align: top; }

        .table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .table th { background: #f8fafc; padding: 10px; text-align: left; font-size: 11px; font-weight: bold; color: #000; text-transform: uppercase; border: 1px solid #000; }
        .table td { padding: 10px; border: 1px solid #000; font-size: 12px; vertical-align: top; }
        
        .total-box { float: right; width: 250px; }
        .total-row { padding: 5px 0; font-size: 13px; }
        .grand-total { font-size: 16px; font-weight: 900; color: #000; border-top: 2px solid #000; margin-top: 5px; padding-top: 5px; }
        
        .terms { margin-top: 40px; font-size: 10px; color: #333; width: 60%; }
        .status-badge { position: absolute; top: 150px; right: 50px; border: 3px solid #ccc; padding: 10px 20px; font-weight: bold; font-size: 24px; opacity: 0.3; transform: rotate(-15deg); z-index: 10; }

        /* Rich Text Styles */
        .rich-text-content { line-height: 1.4; text-align: left; }
        .rich-text-content h1, .rich-text-content h2, .rich-text-content h3 { color: #111; margin-top: 10px; margin-bottom: 5px; }
        .rich-text-content h1 { font-size: 12px; }
        .rich-text-content h2 { font-size: 11px; }
        .rich-text-content h3 { font-size: 10px; }
        .rich-text-content p { margin-bottom: 6px; }
        .rich-text-content ul, .rich-text-content ol { margin-left: 15px; margin-bottom: 6px; }
        .rich-text-content li { margin-bottom: 2px; }
        .rich-text-content strong { color: #111; }
    </style>
</head>
<body>
    @if($invoice->status == 'paid')
        <div class="status-badge" style="border-color: #22c55e; color: #22c55e;">PAID</div>
    @endif

    <div class="container">
        <!-- Kop Surat -->
        @include('documents.pdf.partials.kop')

        <div class="invoice-title-box">
            <div class="invoice-title">INVOICE</div>
        </div>

        <table class="info-table">
            <tr>
                <td class="info-left">
                    <div style="font-weight: bold; margin-bottom: 5px;">Bill to:</div>
                    <div style="font-size: 14px;">{{ $client->name }}</div>
                    @if($client->company_name)
                        <div style="color: #444;">{{ $client->company_name }}</div>
                    @endif
                    <div style="color: #666; font-size: 11px; white-space: pre-line;">{{ $client->address }}</div>
                </td>
                <td class="info-right">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="font-weight: bold; padding: 2px 0;">Date</td>
                            <td style="padding: 2px 5px;">: {{ $invoice->issued_date->format('d/m/Y') }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; padding: 2px 0;">Invoice No.</td>
                            <td style="padding: 2px 5px;">: {{ $invoice->invoice_number }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; padding: 2px 0;">Due Date</td>
                            <td style="padding: 2px 5px; color: #dc2626;">: {{ $invoice->due_date->format('d/m/Y') }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table class="table">
            <thead>
                <tr>
                    <th>DESKRIPSI</th>
                    <th style="text-align: center; width: 50px;">QTY</th>
                    <th style="text-align: right; width: 120px;">HARGA SATUAN</th>
                    <th style="text-align: right; width: 120px;">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td style="text-align: center;">{{ $item->qty }}</td>
                    <td style="text-align: right;">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                    <td style="text-align: right; font-weight: bold;">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
                @endforeach
                
                <!-- Footer Tabel Terpadu -->
                <tr>
                    <td colspan="2" rowspan="3" style="vertical-align: top; background: #fff;">
                        <div style="font-size: 10px; font-weight: bold; text-transform: uppercase; color: #666; margin-bottom: 5px;">Terbilang:</div>
                        <div style="font-size: 11px; font-style: italic;"># {{ App\Http\Controllers\ProjectController::formatTerbilang($invoice->total_amount) }} Rupiah #</div>
                    </td>
                    <td style="font-weight: bold; font-size: 11px; background: #fdfdfd;">DISCOUNT</td>
                    <td style="text-align: right; font-weight: bold;">Rp 0</td>
                </tr>
                <tr>
                    <td style="font-weight: bold; font-size: 11px; background: #fdfdfd;">PAJAK / LAINNYA</td>
                    <td style="text-align: right; font-weight: bold;">Rp {{ number_format($invoice->tax, 0, ',', '.') }}</td>
                </tr>
                <tr style="background: #f0f7ff;">
                    <td style="font-weight: bold; font-size: 11px;">TOTAL AKHIR</td>
                    <td style="text-align: right; font-size: 14px; font-weight: 900; color: #2563eb;">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <div style="margin-top: 30px;">
            <div style="float: right; width: 250px; text-align: center;">
                <p style="font-size: 12px; margin-bottom: 60px;">Banyuwangi, {{ $invoice->issued_date->format('d F Y') }}</p>
                <p style="font-weight: bold; text-decoration: underline; margin-bottom: 0;">Wirodev Administration</p>
                <p style="font-size: 10px; color: #666; margin-top: 2px;">Finance Department</p>
            </div>
            
            <div style="float: left; width: 60%;">
                @if($terms)
                <div class="terms" style="width: 100%; margin-top: 0;">
                    <div style="font-size: 11px; font-weight: bold; margin-bottom: 5px; color: #111;">Syarat & Ketentuan:</div>
                    <div class="rich-text-content" style="font-size: 10px; color: #444;">
                        {!! $terms !!}
                    </div>
                </div>
                @endif
            </div>
            <div style="clear: both;"></div>
        </div>

        <!-- Aktivasi / Ketentuan Otomatis -->
        <div style="margin-top: 50px; border-top: 1px solid #ccc; padding-top: 10px; text-align: center; font-style: italic; font-size: 10px; color: #444;">
            "Dengan melakukan pembayaran Down Payment (DP), Klien dinyatakan telah membaca, memahami, dan menyetujui seluruh Syarat & Ketentuan yang berlaku di Wirodayan Digital tanpa terkecuali."
        </div>
    </div>
</body>
</html>