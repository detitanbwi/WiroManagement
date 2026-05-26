@extends('layouts.app')

@section('title', 'Edit Invoice ' . $invoice->invoice_number)

@section('content')
<div class="max-w-4xl mx-auto" x-data="{ 
    items: {{ json_encode($invoice->items) }},
    tax: {{ $invoice->tax ?? 0 }},
    discount: {{ $invoice->discount ?? 0 }},
    formatThousand(val) {
        if (!val || val === '0') return '0';
        return new Intl.NumberFormat('id-ID').format(val);
    },
    parseNumber(val) {
        if (typeof val === 'number') return val;
        let num = val.replace(/\D/g, '');
        return num ? parseInt(num) : 0;
    },
    addItem() {
        this.items.push({ description: '', qty: 1, price: 0 });
    },
    removeItem(index) {
        this.items.splice(index, 1);
    },
    get subtotal() {
        return this.items.reduce((sum, item) => sum + (item.qty * item.price), 0);
    },
    get total() {
        return this.subtotal + this.tax - this.discount;
    }
}">
    <div class="mb-6">
        <a href="{{ route('invoices.show', $invoice) }}" class="text-sm text-gray-500 hover:text-primary flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            Kembali ke Detail Invoice
        </a>
        <h1 class="text-2xl font-bold text-gray-800 mt-2 uppercase tracking-widest">Edit Invoice - {{ $invoice->invoice_number }}</h1>
        <p class="text-gray-500">Proyek: <span class="font-bold text-gray-700">{{ $invoice->project->title }}</span></p>
    </div>

    <form action="{{ route('invoices.update', $invoice) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')
        
        <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">No. Invoice</label>
                    <input type="text" name="invoice_number" value="{{ old('invoice_number', $invoice->invoice_number) }}" required
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm p-3 border font-bold text-gray-700">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Tanggal Terbit</label>
                    <input type="date" name="issued_date" value="{{ old('issued_date', $invoice->issued_date->format('Y-m-d')) }}" required
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm p-3 border font-bold text-gray-700">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Tanggal Jatuh Tempo (Due Date)</label>
                    <input type="date" name="due_date" value="{{ old('due_date', $invoice->due_date->format('Y-m-d')) }}" required
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm p-3 border font-bold text-red-600">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Status</label>
                    <select name="status" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm p-3 border font-bold">
                        <option value="draft" {{ old('status', $invoice->status) == 'draft' ? 'selected' : '' }}>DRAFT</option>
                        <option value="issued" {{ old('status', $invoice->status) == 'issued' ? 'selected' : '' }}>ISSUED</option>
                        <option value="paid" {{ old('status', $invoice->status) == 'paid' ? 'selected' : '' }}>PAID (LUNAS)</option>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <h3 class="text-xs font-black text-gray-800 uppercase tracking-[0.2em] mb-4 pb-2 border-b">Item Pekerjaan</h3>
                <div class="space-y-4">
                    <template x-for="(item, index) in items" :key="index">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-start bg-gray-50 p-4 rounded-lg border border-gray-100">
                            <div class="col-span-1 md:col-span-6">
                                <label class="text-[10px] font-bold text-gray-400 uppercase mb-1 block">Deskripsi Pekerjaan</label>
                                <textarea :name="'items['+index+'][description]'" x-model="item.description" rows="2" required
                                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm p-3 border resize-y"></textarea>
                            </div>
                            <div class="flex md:contents space-x-3">
                                <div class="flex-1 md:col-span-1">
                                    <label class="text-[10px] font-bold text-gray-400 uppercase mb-1 block">Qty</label>
                                    <input type="number" :name="'items['+index+'][qty]'" x-model.number="item.qty" required
                                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm p-3 border text-center">
                                </div>
                                <div class="flex-[2] md:col-span-4">
                                    <label class="text-[10px] font-bold text-gray-400 uppercase mb-1 block">Harga (Rp)</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="text-gray-400 text-xs font-bold">Rp</span>
                                        </div>
                                        <input type="text" 
                                            :value="formatThousand(item.price)"
                                            @input="item.price = parseNumber($event.target.value)"
                                            class="block w-full pl-9 border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm p-3 border font-bold">
                                        <input type="hidden" :name="'items['+index+'][price]'" :value="item.price">
                                    </div>
                                </div>
                            </div>
                            <div class="col-span-1 md:col-span-1 flex justify-end md:justify-center">
                                <button type="button" @click="removeItem(index)" class="mt-0 md:mt-6 text-red-500 hover:text-red-700 flex items-center text-[10px] font-bold uppercase md:block">
                                    <svg class="w-5 h-5 md:mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    <span class="md:hidden ml-1">Hapus</span>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
                <button type="button" @click="addItem()" class="mt-4 inline-flex items-center text-xs font-bold text-primary hover:text-blue-700 uppercase tracking-widest">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Tambah Baris Baru
                </button>
            </div>

            <div class="mt-8 border-t border-gray-100 pt-8 flex justify-end">
                <div class="w-full md:w-80 space-y-4">
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-500 font-bold uppercase tracking-widest text-xs">Subtotal</span>
                        <span class="font-bold text-gray-900" x-text="'Rp ' + formatThousand(subtotal)"></span>
                    </div>
                    
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1 text-right">Pajak / Lainnya (+)</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-400 text-xs font-bold">Rp</span>
                            </div>
                            <input type="text" 
                                :value="formatThousand(tax)"
                                @input="tax = parseNumber($event.target.value)"
                                class="block w-full pl-9 border-gray-200 rounded-md text-right sm:text-sm p-2 border font-bold">
                            <input type="hidden" name="tax" :value="tax">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1 text-right">Diskon (-)</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-400 text-xs font-bold">Rp</span>
                            </div>
                            <input type="text" 
                                :value="formatThousand(discount)"
                                @input="discount = parseNumber($event.target.value)"
                                class="block w-full pl-9 border-gray-200 rounded-md text-right sm:text-sm p-2 border font-bold text-red-600">
                            <input type="hidden" name="discount" :value="discount">
                        </div>
                    </div>

                    <div class="flex justify-between items-center pt-4 border-t border-gray-200">
                        <span class="text-lg font-black text-gray-800 uppercase tracking-widest">Total</span>
                        <span class="text-2xl font-black text-primary" x-text="'Rp ' + formatThousand(total)"></span>
                        <input type="hidden" name="total_amount" :value="total">
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-8">
            <h3 class="text-xs font-black text-gray-800 uppercase tracking-[0.2em] mb-4 pb-2 border-b">Informasi Tambahan</h3>
            <div class="space-y-6">
                <div>
                    <label for="notes" class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Catatan Invoice</label>
                    <textarea name="notes" id="notes" rows="3" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm p-3 border">{{ old('notes', $invoice->notes) }}</textarea>
                </div>
                <div>
                    <label for="attachment_pdf" class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Lampiran Dokumen (PDF)</label>
                    <input type="file" name="attachment_pdf" id="attachment_pdf" accept="application/pdf"
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm p-3 border">
                    <p class="mt-1 text-[10px] text-gray-400 font-bold uppercase">Biarkan kosong jika tidak ingin mengganti lampiran.</p>
                    @if($invoice->attachment_pdf)
                        <div class="mt-2 inline-flex items-center p-2 bg-blue-50 rounded border border-blue-100">
                            <a href="{{ asset('storage/' . $invoice->attachment_pdf) }}" target="_blank" class="text-[10px] font-black text-blue-600 uppercase hover:underline">Lampiran Saat Ini (Lihat)</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex justify-end space-x-3">
            <a href="{{ route('invoices.show', $invoice) }}" class="px-6 py-3 border border-gray-300 rounded-md shadow-sm text-xs font-black text-gray-700 bg-white hover:bg-gray-50 uppercase tracking-widest">
                Batal
            </a>
            <button type="submit" class="px-10 py-3 bg-primary border border-transparent rounded-md font-black text-xs text-white uppercase tracking-[0.2em] hover:bg-blue-800 transition shadow-lg">
                Simpan Perubahan
            </button>
        </div>
    </form>
</div>
@endsection
