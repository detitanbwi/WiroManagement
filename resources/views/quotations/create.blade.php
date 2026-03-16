@extends('layouts.app')

@section('title', 'Buat Quotation Baru')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('projects.show', $project) }}" class="text-sm text-gray-500 hover:text-primary flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            Kembali ke Detail Proyek
        </a>
        <h1 class="text-2xl font-bold text-gray-800 mt-2">Buat Quotation Baru</h1>
        <p class="text-gray-500">Proyek: <span class="font-bold">{{ $project->title }}</span></p>
    </div>

    <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-6">
        <form action="{{ route('projects.quotations.store', $project) }}" method="POST">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="col-span-1">
                    <label for="quotation_number" class="block text-sm font-bold text-gray-700 uppercase tracking-wider mb-2">No. Quotation</label>
                    @php
                        $count = \App\Models\Quotation::count() + 1;
                        $ref = str_pad($count, 3, '0', STR_PAD_LEFT);
                        $defaultNo = "QUO/WIRODEV/" . date('Y') . "/" . $ref;
                    @endphp
                    <input type="text" name="quotation_number" id="quotation_number" value="{{ old('quotation_number', $defaultNo) }}" required
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm p-3 border">
                    @error('quotation_number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="col-span-1">
                    <label for="status" class="block text-sm font-bold text-gray-700 uppercase tracking-wider mb-2">Status</label>
                    <select name="status" id="status" required
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm p-3 border">
                        <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>DRAFT</option>
                        <option value="issued" {{ old('status') == 'issued' ? 'selected' : '' }}>ISSUED (DIKIRIM)</option>
                        <option value="approved" {{ old('status') == 'approved' ? 'selected' : '' }}>APPROVED (DISETUJUI)</option>
                    </select>
                    @error('status') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="col-span-2">
                    <label for="description" class="block text-sm font-bold text-gray-700 uppercase tracking-wider mb-2">Deskripsi Pekerjaan / Fitur</label>
                    <textarea name="description" id="description" rows="5" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm p-3 border" placeholder="Jelaskan rincian pekerjaan atau fitur yang ditawarkan...">{{ old('description', $project->description) }}</textarea>
                    @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="col-span-1">
                    <label for="warranty_days" class="block text-sm font-bold text-gray-700 uppercase tracking-wider mb-2">Garansi (Hari)</label>
                    <input type="number" name="warranty_days" id="warranty_days" value="{{ old('warranty_days', 0) }}" required
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm p-3 border">
                    <p class="mt-1 text-[10px] text-gray-400">Isi 0 jika tidak ada garansi.</p>
                    @error('warranty_days') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="col-span-1">
                    <label for="working_duration" class="block text-sm font-bold text-gray-700 uppercase tracking-wider mb-2">Durasi Pengerjaan</label>
                    <input type="text" name="working_duration" id="working_duration" value="{{ old('working_duration') }}" required placeholder="Contoh: 14 Hari Kerja"
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm p-3 border">
                    @error('working_duration') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="col-span-2" x-data="{ 
                    rawTotal: '{{ old('total_amount') }}',
                    get formattedTotal() {
                        if (!this.rawTotal) return '';
                        return new Intl.NumberFormat('id-ID').format(this.rawTotal);
                    },
                    updateTotal(val) {
                        this.rawTotal = val.replace(/\D/g, '');
                    }
                }">
                    <label for="display_total" class="block text-sm font-bold text-gray-700 uppercase tracking-wider mb-2">Total Nilai Penawaran (Rp)</label>
                    <div class="relative mt-1 rounded-md shadow-sm">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <span class="text-gray-500 sm:text-sm">Rp</span>
                        </div>
                        <input type="text" id="display_total" 
                            :value="formattedTotal"
                            @input="updateTotal($event.target.value)"
                            class="block w-full border-gray-300 rounded-md focus:ring-primary focus:border-primary pl-10 sm:text-sm p-3 border" placeholder="0">
                        <input type="hidden" name="total_amount" :value="rawTotal">
                    </div>
                    @error('total_amount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-8 flex justify-end space-x-3">
                <a href="{{ route('projects.show', $project) }}" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    Batal
                </a>
                <button type="submit" class="px-6 py-2 bg-primary border border-transparent rounded-md font-bold text-sm text-white uppercase tracking-widest hover:bg-blue-700 transition">
                    Simpan Quotation
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
