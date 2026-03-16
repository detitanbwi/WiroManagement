<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Kuitansi {{ $receipt->receipt_number }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #333; line-height: 1.2; margin: 0; padding: 0; background: #fff; }
        .container { padding: 20px; }
        
        /* Kop Surat Styles */
        @include('documents.pdf.partials.kop_style')
        
        .receipt-title-box { text-align: center; margin-bottom: 20px; }
        .receipt-title { font-size: 18px; font-weight: bold; text-transform: uppercase; border-bottom: 1px solid #000; display: inline-block; padding: 0 20px; }
        
        .info-table { width: 100%; margin-bottom: 30px; font-size: 13px; }
        .info-left { width: 60%; vertical-align: top; }
        .info-right { width: 40%; vertical-align: top; }
        
        .row { display: table; width: 100%; margin-bottom: 10px; border-bottom: 1px dashed #ccc; padding-bottom: 5px; }
        .label { display: table-cell; width: 150px; font-size: 11px; font-weight: bold; color: #000; text-transform: uppercase; }
        .value { display: table-cell; font-size: 13px; color: #000; font-weight: bold; }
        
        .amount-box { margin-top: 30px; background: #f0f7ff; padding: 15px; border-left: 5px solid #2563eb; }
        .amount-label { font-size: 10px; font-weight: bold; color: #000; text-transform: uppercase; }
        .amount-value { font-size: 22px; font-weight: 900; color: #2563eb; }
        
        .terbilang { margin-top: 20px; font-size: 12px; font-style: italic; color: #444; border: 1px solid #eee; padding: 10px; }
        .signature { margin-top: 40px; float: right; width: 250px; text-align: center; font-size: 13px; }
        .sig-line { border-bottom: 1px solid #000; margin-bottom: 5px; height: 60px; width: 200px; margin-left: auto; margin-right: auto; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Kop Surat -->
        @include('documents.pdf.partials.kop')

        <div class="receipt-title-box">
            <div class="receipt-title">RECEIPT</div>
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
                            <td style="padding: 2px 5px;">: {{ $payment->payment_date->format('d/m/Y') }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; padding: 2px 0;">Receipt No.</td>
                            <td style="padding: 2px 5px;">: {{ $receipt->receipt_number }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; padding: 2px 0;">Currency</td>
                            <td style="padding: 2px 5px;">: RUPIAH (IDR)</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

            <div class="row">
                <div class="label">Untuk Pembayaran:</div>
                <div class="value">Invoice #{{ $invoice->invoice_number }} - {{ $invoice->project->title }}</div>
            </div>

            <div class="row">
                <div class="label">Keterangan:</div>
                <div class="value">Pembayaran via {{ $payment->payment_method }}</div>
            </div>

            <div class="row">
                <div class="label">Tanggal:</div>
                <div class="value">{{ $payment->payment_date->format('d F Y') }}</div>
            </div>

            <div class="amount-box">
                <div class="amount-label">Sejumlah:</div>
                <div class="amount-value">Rp {{ number_format($payment->amount, 0, ',', '.') }},-</div>
            </div>

            <div style="margin-top: 30px; font-size: 12px; color: #64748b;">
                <strong>Terbilang:</strong> <span style="font-style: italic; color: #475569;"># {{ App\Http\Controllers\ProjectController::formatTerbilang($payment->amount) }} Rupiah #</span>
            </div>

            <div class="signature">
                <p style="font-size: 12px; color: #64748b; margin-bottom: 10px;">Banyuwangi, {{ date('d M Y') }}</p>
                <div class="sig-line"></div>
                <p class="sig-name">Wirodev Administration</p>
                <p style="font-size: 10px; color: #94a3b8;">Finance Department</p>
            </div>
            
            <div style="clear: both;"></div>
</body>
</html>