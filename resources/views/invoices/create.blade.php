@extends('layouts.app')

@section('title', 'Buat Invoice Baru')

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('projects.show', $project) }}" class="text-sm text-gray-500 hover:text-primary flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            Kembali ke Detail Proyek
        </a>
        <h1 class="text-2xl font-bold text-gray-800 mt-2">Buat Invoice Baru</h1>
        <p class="text-gray-500">Proyek: <span class="font-bold">{{ $project->title }}</span></p>
    </div>

    <form action="{{ route('projects.invoices.store', $project) }}" method="POST" x-data="invoiceForm()" enctype="multipart/form-data">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Form -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-6">
                    <h3 class="font-bold text-gray-800 mb-4 border-b pb-2">Rincian Pekerjaan</h3>
                    
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase">Deskripsi</th>
                                <th class="px-4 py-2 text-center text-xs font-bold text-gray-500 uppercase w-20">Qty</th>
                                <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase w-40">Harga (Rp)</th>
                                <th class="px-4 py-2 text-right text-xs font-bold text-gray-500 uppercase w-40">Total</th>
                                <th class="px-2 py-2 w-10"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <template x-for="(item, index) in items" :key="index">
                                <tr>
                                    <td class="px-2 py-3">
                                        <input type="text" :name="'items['+index+'][description]'" x-model="item.description" required class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                                    </td>
                                    <td class="px-2 py-3">
                                        <input type="number" :name="'items['+index+'][qty]'" x-model.number="item.qty" @input="calculateTotal()" required class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm text-center">
                                    </td>
                                    <td class="px-2 py-3">
                                        <input type="number" :name="'items['+index+'][price]'" x-model.number="item.price" @input="calculateTotal()" required class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm font-medium">
                                        Rp <span x-text="numberFormat(item.qty * item.price)"></span>
                                    </td>
                                    <td class="px-2 py-3 text-center">
                                        <button type="button" @click="removeItem(index)" class="text-red-600 hover:text-red-900">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    
                    <div class="mt-4">
                        <button type="button" @click="addItem()" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            + Tambah Baris
                        </button>
                    </div>
                </div>

                <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-6">
                    <label for="notes" class="block text-sm font-medium text-gray-700">Catatan Invoice (Opsional)</label>
                    <textarea name="notes" id="notes" rows="3" placeholder="Misal: Nomor rekening, instruksi pembayaran, dll." class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm"></textarea>
                </div>

                <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-6 text-sm">
                    <label for="attachment_pdf" class="block font-bold text-gray-700 uppercase tracking-wider mb-2">Attachment PDF (Opsional)</label>
                    <input type="file" name="attachment_pdf" id="attachment_pdf" accept="application/pdf"
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm p-3 border">
                    <p class="mt-1 text-[10px] text-gray-400">Pilih file PDF (Maks. 10MB) jika ada lampiran tambahan.</p>
                </div>
            </div>

            <!-- Sidebar Info -->
            <div class="space-y-6">
                <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-6">
                    <h3 class="font-bold text-gray-800 mb-4 border-b pb-2">Informasi Invoice</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase">No. Invoice</label>
                            @php
                                $today = date('Ymd');
                                $count = \App\Models\Invoice::whereDate('created_at', date('Y-m-d'))->count() + 1;
                                $serial = str_pad($count, 2, '0', STR_PAD_LEFT);
                                $defaultInvoiceNumber = "INV/WIRODEV/{$today}/{$serial}";
                            @endphp
                            <input type="text" name="invoice_number" required value="{{ $defaultInvoiceNumber }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase">Tanggal Terbit</label>
                            <input type="date" name="issued_date" required value="{{ date('Y-m-d') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase">Jatuh Tempo</label>
                            <input type="date" name="due_date" required value="{{ date('Y-m-d', strtotime('+7 days')) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                        </div>
                    </div>
                </div>

                <div class="bg-primary shadow-sm rounded-lg p-6 text-white">
                    <h3 class="font-bold mb-4 border-b border-blue-400 pb-2">Ringkasan Biaya</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span>Subtotal</span>
                            <span class="font-bold">Rp <span x-text="numberFormat(subtotal)"></span></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span>Pajak (+)</span>
                            <input type="number" name="tax" x-model.number="tax" @input="calculateTotal()" class="w-24 bg-blue-800 border-none rounded text-right text-sm py-1 font-bold focus:ring-0">
                        </div>
                        <div class="flex justify-between items-center">
                            <span>Diskon (-)</span>
                            <input type="number" name="discount" x-model.number="discount" @input="calculateTotal()" class="w-24 bg-blue-800 border-none rounded text-right text-sm py-1 font-bold focus:ring-0">
                        </div>
                        <div class="border-t border-blue-400 mt-2 pt-2 flex justify-between text-lg font-black">
                            <span>TOTAL</span>
                            <span>Rp <span x-text="numberFormat(total)"></span></span>
                        </div>
                    </div>
                    
                    <button type="submit" class="w-full mt-6 bg-white text-primary font-bold py-3 rounded-md hover:bg-gray-100 transition shadow-lg">
                        TERBITKAN INVOICE
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    function invoiceForm() {
        return {
            items: [{
                description: 'Layanan Pengembangan Software',
                qty: 1,
                price: 0
            }],
            tax: 0,
            discount: 0,
            subtotal: 0,
            total: 0,
            addItem() {
                this.items.push({
                    description: '',
                    qty: 1,
                    price: 0
                });
            },
            removeItem(index) {
                if (this.items.length > 1) {
                    this.items.splice(index, 1);
                    this.calculateTotal();
                }
            },
            calculateTotal() {
                this.subtotal = this.items.reduce((sum, item) => sum + (item.qty * item.price), 0);
                this.total = this.subtotal + this.tax - this.discount;
            },
            numberFormat(val) {
                return new Intl.NumberFormat('id-ID').format(val);
            }
        }
    }
</script>
@endsection
