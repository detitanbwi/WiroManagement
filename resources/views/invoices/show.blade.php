@extends('layouts.app')

@section('title', 'Invoice ' . $invoice->invoice_number)

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <a href="{{ route('projects.show', $invoice->project) }}" class="text-sm text-gray-500 hover:text-primary flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                Kembali ke Proyek
            </a>
            <h1 class="text-2xl font-bold text-gray-800 mt-2">Invoice - {{ $invoice->invoice_number }}</h1>
        </div>
        <div class="flex space-x-2">
            <a href="{{ route('documents.invoice.pdf', $invoice) }}" target="_blank" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md font-bold text-xs uppercase hover:bg-indigo-700 shadow-sm">
                Cetak PDF
            </a>
            @if($invoice->status == 'draft')
                <a href="{{ route('invoices.edit', $invoice) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-bold text-xs text-gray-700 uppercase hover:bg-gray-50">
                    Edit
                </a>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Invoice Details -->
            <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
                <div class="p-8">
                    <div class="flex justify-between items-start mb-12">
                        <div>
                            <h2 class="text-xl font-black text-primary">WIRODEV</h2>
                            <p class="text-xs text-secondary">Software & Creative House</p>
                        </div>
                        <div class="text-right">
                            <h3 class="text-2xl font-bold uppercase {{ $invoice->status == 'paid' ? 'text-green-600' : 'text-gray-900' }}">
                                {{ $invoice->status }}
                            </h3>
                            <p class="text-sm text-gray-500">Invoice Date: {{ $invoice->issued_date->format('d M Y') }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-8 mb-12">
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Ditagihkan Kepada:</p>
                            <p class="font-bold text-gray-900 text-lg">{{ $invoice->project->client->name }}</p>
                            <p class="text-gray-600">{{ $invoice->project->client->company_name }}</p>
                            <p class="text-gray-600 whitespace-pre-line">{{ $invoice->project->client->address }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Jatuh Tempo:</p>
                            <p class="font-bold text-red-600 text-lg">{{ $invoice->due_date->format('d M Y') }}</p>
                        </div>
                    </div>

                    <table class="min-w-full divide-y divide-gray-200 mb-8">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Deskripsi Pekerjaan</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Qty</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Harga</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($invoice->items as $item)
                            <tr>
                                <td class="px-4 py-4 text-sm text-gray-900">{{ $item->description }}</td>
                                <td class="px-4 py-4 text-sm text-center">{{ $item->qty }}</td>
                                <td class="px-4 py-4 text-sm text-right">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                                <td class="px-4 py-4 text-sm text-right font-bold">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="flex justify-end pt-8 border-t border-gray-100">
                        <div class="w-64 space-y-3">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Subtotal</span>
                                <span class="font-bold">Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Pajak / Lainnya (+)</span>
                                <span class="font-bold">Rp {{ number_format($invoice->tax, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Diskon (-)</span>
                                <span class="font-bold text-red-600">Rp {{ number_format($invoice->discount, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between text-xl border-t border-gray-200 pt-3">
                                <span class="font-black text-gray-800">TOTAL</span>
                                <span class="font-black text-primary">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes Section -->
            @if($invoice->notes)
            <div class="bg-yellow-50 rounded-lg border border-yellow-100 p-6">
                <h4 class="text-xs font-bold text-yellow-800 uppercase tracking-widest mb-2">Catatan Tambahan:</h4>
                <p class="text-sm text-yellow-900 whitespace-pre-line">{{ $invoice->notes }}</p>
            </div>
            @endif

            @if($invoice->attachment_pdf)
            <div class="p-6 bg-white rounded-lg border border-gray-200 flex items-center justify-between shadow-sm">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center text-red-600 mr-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-gray-800">Lampiran Tagihan</p>
                        <p class="text-xs text-gray-500">File PDF Lampiran Invoice</p>
                    </div>
                </div>
                <a href="{{ asset('storage/' . $invoice->attachment_pdf) }}" target="_blank" class="px-4 py-2 bg-white border border-gray-300 rounded-md text-xs font-bold text-gray-700 hover:bg-gray-50 flex items-center shadow-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    DOWNLOAD
                </a>
            </div>
            @endif
        </div>

        <!-- Sidebar Actions: Payment -->
        <div class="space-y-8">
            <!-- Payment Summary -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">Ringkasan Pembayaran</h3>
                <div class="space-y-4">
                    <div class="flex justify-between text-sm">
                        <span>Total Tagihan</span>
                        <span class="font-bold text-gray-900">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span>Sudah Dibayar</span>
                        <span class="font-bold text-green-600">Rp {{ number_format($invoice->paid_amount, 0, ',', '.') }}</span>
                    </div>
                    <div class="border-t border-gray-100 pt-4 flex justify-between text-lg">
                        <span class="font-medium">Sisa Tagihan</span>
                        <span class="font-black {{ $invoice->balance_due > 0 ? 'text-red-600' : 'text-green-600' }}">
                            Rp {{ number_format($invoice->balance_due, 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Record Payment Form -->
            @if($invoice->balance_due > 0)
            <div class="bg-gray-50 rounded-lg shadow-sm border border-gray-200 p-6" x-data="{ 
                amount: {{ $invoice->balance_due }},
                formatRupiah(val) {
                    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
                }
            }">
                <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider mb-4">Catat Pembayaran Baru</h3>
                <form action="{{ route('payments.store', $invoice) }}" method="POST">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase">Jumlah Bayar (Rp)</label>
                            <input type="number" name="amount" x-model.number="amount" required max="{{ $invoice->balance_due }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm font-bold text-primary">
                            <p class="mt-1 text-xs font-bold text-green-600" x-text="formatRupiah(amount)"></p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase">Tanggal Bayar</label>
                            <input type="date" name="payment_date" required value="{{ date('Y-m-d') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase">Metode</label>
                            <select name="payment_method" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Cash">Cash</option>
                                <option value="Cheque">Cheque</option>
                            </select>
                        </div>
                        <button type="submit" class="w-full bg-green-600 text-white font-bold py-3 rounded-md hover:bg-green-700 transition shadow-sm">
                            SIMPAN PEMBAYARAN
                        </button>
                    </div>
                </form>
            </div>
            @endif

            <!-- History Payments -->
            @if($invoice->payments->count() > 0)
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">Riwayat Pembayaran</h3>
                <div class="space-y-3">
                    @foreach($invoice->payments as $payment)
                    <div class="flex justify-between items-center text-sm border-b border-gray-50 pb-2">
                        <div>
                            <p class="font-bold text-gray-800">Rp {{ number_format($payment->amount, 0, ',', '.') }}</p>
                            <p class="text-[10px] text-gray-400 uppercase">{{ $payment->payment_date->format('d/m/Y') }} via {{ $payment->payment_method }}</p>
                        </div>
                        <a href="{{ route('documents.receipt.pdf', $payment) }}" target="_blank" class="text-xs text-indigo-600 hover:underline font-bold">Kuitansi &rarr;</a>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
