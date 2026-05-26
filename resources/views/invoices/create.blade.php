@extends('layouts.app')

@section('title', 'Buat Invoice Baru')

@section('content')
<div class="max-w-7xl mx-auto">
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
                    <h3 class="font-bold text-gray-800 mb-4 border-b pb-2 uppercase tracking-widest text-xs">Rincian Pekerjaan</h3>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="hidden md:table-header-group bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-wider">Deskripsi</th>
                                    <th class="px-4 py-3 text-center text-[10px] font-bold text-gray-500 uppercase tracking-wider w-16">Qty</th>
                                    <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-wider w-40">Harga (Rp)</th>
                                    <th class="px-4 py-3 text-right text-[10px] font-bold text-gray-500 uppercase tracking-wider w-32">Total</th>
                                    <th class="px-2 py-3 w-10"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="(item, index) in items" :key="index">
                                    <tr class="flex flex-col md:table-row border-b md:border-b-0 py-4 md:py-0 space-y-3 md:space-y-0">
                                        <!-- Deskripsi -->
                                        <td class="px-2 md:py-3 block md:table-cell">
                                            <label class="md:hidden block text-[10px] font-bold text-gray-400 uppercase mb-1">Deskripsi Pekerjaan</label>
                                            <textarea :name="'items['+index+'][description]'" x-model="item.description" rows="2" required class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm p-3 border resize-y"></textarea>
                                        </td>
                                        
                                        <div class="flex md:contents space-x-3">
                                            <!-- Qty -->
                                            <td class="px-2 md:py-3 block md:table-cell flex-1 md:flex-none">
                                                <label class="md:hidden block text-[10px] font-bold text-gray-400 uppercase mb-1">Qty</label>
                                                <input type="number" :name="'items['+index+'][qty]'" x-model.number="item.qty" @input="calculateTotal()" required class="block w-full md:w-16 border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm text-center p-3 border">
                                            </td>
                                            
                                            <!-- Harga -->
                                            <td class="px-2 md:py-3 block md:table-cell flex-[2] md:flex-none">
                                                <label class="md:hidden block text-[10px] font-bold text-gray-400 uppercase mb-1">Harga Satuan</label>
                                                <div class="relative">
                                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                        <span class="text-gray-400 text-xs font-bold">Rp</span>
                                                    </div>
                                                    <input type="text" 
                                                        :value="formatThousand(item.price)"
                                                        @input="item.price = parseNumber($event.target.value); calculateTotal()"
                                                        class="block w-full pl-9 border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm p-3 border font-bold text-gray-700">
                                                    <input type="hidden" :name="'items['+index+'][price]'" :value="item.price">
                                                </div>
                                            </td>
                                        </div>

                                        <!-- Total -->
                                        <td class="px-4 md:py-3 block md:table-cell text-right md:text-right">
                                            <div class="flex justify-between md:block items-center">
                                                <label class="md:hidden block text-[10px] font-bold text-gray-400 uppercase">Subtotal Item</label>
                                                <span class="text-sm font-bold text-gray-900">
                                                    Rp <span x-text="numberFormat(item.qty * item.price)"></span>
                                                </span>
                                            </div>
                                        </td>

                                        <!-- Action -->
                                        <td class="px-2 md:py-3 block md:table-cell text-right md:text-center pt-2 md:pt-0">
                                            <button type="button" @click="removeItem(index)" class="inline-flex items-center text-red-400 hover:text-red-600 transition text-xs font-bold md:p-0">
                                                <svg class="w-5 h-5 mr-1 md:mr-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                <span class="md:hidden">Hapus Baris</span>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4">
                        <button type="button" @click="addItem()" class="inline-flex items-center px-4 py-2 border border-dashed border-gray-300 shadow-sm text-xs font-bold uppercase tracking-widest rounded-md text-gray-500 bg-white hover:bg-gray-50 transition w-full justify-center">
                            + Tambah Baris Pekerjaan
                        </button>
                    </div>
                </div>

                <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-6">
                    <label for="notes" class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Catatan Invoice (Opsional)</label>
                    <textarea name="notes" id="notes" rows="3" placeholder="Misal: Nomor rekening, instruksi pembayaran, dll." class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm p-3 border"></textarea>
                </div>

                <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-6">
                    <label for="attachment_pdf" class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Attachment PDF (Opsional)</label>
                    <input type="file" name="attachment_pdf" id="attachment_pdf" accept="application/pdf"
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm p-3 border">
                    <p class="mt-1 text-[10px] text-gray-400 font-medium">Pilih file PDF (Maks. 10MB) jika ada lampiran tambahan.</p>
                </div>
            </div>

            <!-- Sidebar Info -->
            <div class="space-y-6">
                <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-6">
                    <h3 class="font-bold text-gray-800 mb-4 border-b pb-2 uppercase tracking-widest text-xs">Informasi Invoice</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">No. Invoice</label>
                            @php
                                $projectYear = $project->created_at ? $project->created_at->format('Y') : date('Y');
                                $projectSeq = \App\Models\Project::whereYear('created_at', $projectYear)->where('id', '<=', $project->id)->count();
                                $projectRef = str_pad($projectSeq, 3, '0', STR_PAD_LEFT);
                                $invSeq = \App\Models\Invoice::where('project_id', $project->id)->count() + 1;
                                $invRef = str_pad($invSeq, 2, '0', STR_PAD_LEFT);
                                $defaultInvoiceNumber = "INV/WIRODEV/" . $projectYear . "/" . $projectRef . "/" . $invRef;
                            @endphp
                            <input type="text" name="invoice_number" required value="{{ $defaultInvoiceNumber }}" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm p-3 border font-bold">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Tanggal Terbit</label>
                            <input type="date" name="issued_date" required value="{{ date('Y-m-d') }}" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm p-3 border font-bold text-gray-700">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Jatuh Tempo</label>
                            <input type="date" name="due_date" required value="{{ date('Y-m-d', strtotime('+7 days')) }}" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm p-3 border font-bold text-red-600">
                        </div>
                    </div>
                </div>

                <div class="bg-white shadow-xl rounded-2xl border border-primary p-6 overflow-hidden relative">
                    <div class="absolute top-0 right-0 p-3">
                        <svg class="w-12 h-12 text-primary opacity-5" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"></path><path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"></path></svg>
                    </div>
                    <h3 class="font-bold text-gray-800 mb-6 border-b pb-2 uppercase tracking-widest text-xs">Ringkasan Biaya</h3>
                    <div class="space-y-4">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500 font-bold uppercase tracking-tighter">Subtotal</span>
                            <span class="font-bold text-gray-800">Rp <span x-text="numberFormat(subtotal)"></span></span>
                        </div>
                        
                        <div class="space-y-1">
                            <label class="block text-[10px] font-black text-gray-400 uppercase">Pajak / Biaya Lain (+)</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-400 text-xs font-bold">Rp</span>
                                </div>
                                <input type="text" 
                                    :value="formatThousand(tax)"
                                    @input="tax = parseNumber($event.target.value); calculateTotal()"
                                    class="block w-full pl-9 border-gray-200 rounded-lg bg-gray-50 sm:text-sm py-2 font-bold text-gray-700 focus:ring-primary focus:border-primary border">
                                <input type="hidden" name="tax" :value="tax">
                            </div>
                        </div>

                        <div class="space-y-1">
                            <label class="block text-[10px] font-black text-gray-400 uppercase">Diskon (-)</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-400 text-xs font-bold">Rp</span>
                                </div>
                                <input type="text" 
                                    :value="formatThousand(discount)"
                                    @input="discount = parseNumber($event.target.value); calculateTotal()"
                                    class="block w-full pl-9 border-gray-200 rounded-lg bg-gray-50 sm:text-sm py-2 font-bold text-red-600 focus:ring-primary focus:border-primary border">
                                <input type="hidden" name="discount" :value="discount">
                            </div>
                        </div>

                        <div class="border-t-2 border-dashed border-gray-100 mt-4 pt-4 flex justify-between items-center">
                            <span class="text-xs font-black text-gray-800 uppercase">Grand Total</span>
                            <span class="text-xl font-black text-primary">Rp <span x-text="numberFormat(total)"></span></span>
                        </div>
                    </div>
                    
                    <button type="submit" class="w-full mt-8 bg-primary text-white font-bold py-4 rounded-xl hover:bg-blue-800 transition shadow-lg shadow-blue-100 uppercase tracking-widest text-xs flex items-center justify-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Terbitkan Invoice
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
            formatThousand(val) {
                if (val === 0 || val === '0') return '0';
                if (!val) return '';
                return new Intl.NumberFormat('id-ID').format(val);
            },
            parseNumber(val) {
                return Number(val.replace(/\D/g, '')) || 0;
            },
            numberFormat(val) {
                return new Intl.NumberFormat('id-ID').format(val);
            }
        }
    }
</script>
@endsection
