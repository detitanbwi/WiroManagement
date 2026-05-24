@extends('layouts.app')

@section('title', 'Edit Quotation')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('projects.show', $project) }}" class="text-sm text-gray-500 hover:text-primary flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            Kembali ke Detail Proyek
        </a>
        <h1 class="text-2xl font-bold text-gray-800 mt-2">Edit Quotation</h1>
        <p class="text-gray-500">Proyek: <span class="font-bold">{{ $project->title }}</span></p>
    </div>

    <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-6">
        <form action="{{ route('quotations.update', $quotation) }}" method="POST" id="quotation-form" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="col-span-1">
                    <label for="quotation_number" class="block text-sm font-bold text-gray-700 uppercase tracking-wider mb-2">No. Quotation</label>
                    <input type="text" name="quotation_number" id="quotation_number" value="{{ old('quotation_number', $quotation->quotation_number) }}" required
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm p-3 border">
                    @error('quotation_number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="col-span-1">
                    <label for="status" class="block text-sm font-bold text-gray-700 uppercase tracking-wider mb-2">Status</label>
                    <select name="status" id="status" required
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm p-3 border">
                        <option value="draft" {{ old('status', $quotation->status) == 'draft' ? 'selected' : '' }}>DRAFT</option>
                        <option value="issued" {{ old('status', $quotation->status) == 'issued' ? 'selected' : '' }}>ISSUED (DIKIRIM)</option>
                        <option value="approved" {{ old('status', $quotation->status) == 'approved' ? 'selected' : '' }}>APPROVED (DISETUJUI)</option>
                    </select>
                    @error('status') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="col-span-2">
                    <label for="description" class="block text-sm font-bold text-gray-700 uppercase tracking-wider mb-2">Deskripsi Pekerjaan / Fitur</label>
                    <div class="bg-white">
                        <textarea name="description" id="editor-description" style="height: 250px;">{!! old('description', $quotation->description) !!}</textarea>
                    </div>
                    @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="col-span-1">
                    <label for="warranty_days" class="block text-sm font-bold text-gray-700 uppercase tracking-wider mb-2">Garansi (Hari)</label>
                    <input type="number" name="warranty_days" id="warranty_days" value="{{ old('warranty_days', $quotation->warranty_days) }}" required
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm p-3 border">
                    <p class="mt-1 text-[10px] text-gray-400">Isi 0 jika tidak ada garansi.</p>
                    @error('warranty_days') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="col-span-1">
                    <label for="due_date" class="block text-sm font-bold text-gray-700 uppercase tracking-wider mb-2">Tanggal Jatuh Tempo</label>
                    <input type="date" name="due_date" id="due_date" value="{{ old('due_date', $quotation->due_date ? $quotation->due_date->format('Y-m-d') : '') }}" required
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm p-3 border font-bold text-gray-700">
                    <p class="mt-1 text-[10px] text-gray-400 uppercase font-bold tracking-widest">Penting untuk deadline penawaran.</p>
                    @error('due_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="col-span-1">
                    <label for="working_duration" class="block text-sm font-bold text-gray-700 uppercase tracking-wider mb-2">Durasi Pengerjaan</label>
                    <input type="text" name="working_duration" id="working_duration" value="{{ old('working_duration', $quotation->working_duration) }}" required placeholder="Contoh: 14 Hari Kerja"
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm p-3 border font-bold">
                    @error('working_duration') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="col-span-2" x-data="{ 
                    rawTotal: '{{ old('total_amount', $quotation->total_amount) }}',
                    formatThousand(val) {
                        if (!val || val === '0') return '0';
                        return new Intl.NumberFormat('id-ID').format(val);
                    },
                    parseNumber(val) {
                        let num = val.replace(/\D/g, '');
                        return num ? parseInt(num) : 0;
                    }
                }">
                    <label for="display_total" class="block text-sm font-bold text-gray-700 uppercase tracking-wider mb-2">Total Nilai Penawaran (Rp)</label>
                    <div class="relative mt-1 rounded-md shadow-sm">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                            <span class="text-gray-400 font-bold sm:text-sm">Rp</span>
                        </div>
                        <input type="text" id="display_total" 
                            :value="formatThousand(rawTotal)"
                            @input="rawTotal = parseNumber($event.target.value)"
                            class="block w-full border-gray-300 rounded-md focus:ring-primary focus:border-primary pl-12 sm:text-lg p-4 border font-black text-primary" placeholder="0">
                        <input type="hidden" name="total_amount" :value="rawTotal">
                    </div>
                    @error('total_amount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="col-span-2">
                    <label for="attachment_pdf" class="block text-sm font-bold text-gray-700 uppercase tracking-wider mb-2">Attachment PDF (Opsional)</label>
                    <input type="file" name="attachment_pdf" id="attachment_pdf" accept="application/pdf"
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm p-3 border">
                    <p class="mt-1 text-[10px] text-gray-400">Pilih file PDF (Maks. 10MB) untuk mengganti lampiran lama.</p>
                    @if($quotation->attachment_pdf)
                        <div class="mt-2 flex items-center p-3 bg-blue-50 rounded-lg border border-blue-100">
                            <svg class="w-4 h-4 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                            <a href="{{ asset('storage/' . $quotation->attachment_pdf) }}" target="_blank" class="text-xs font-black text-blue-600 uppercase hover:underline">Lampiran Saat Ini (Lihat)</a>
                        </div>
                    @endif
                    @error('attachment_pdf') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-8 flex justify-end space-x-3">
                <a href="{{ route('projects.show', $project) }}" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    Batal
                </a>
                <button type="submit" class="px-6 py-2 bg-primary border border-transparent rounded-md font-bold text-sm text-white uppercase tracking-widest hover:bg-blue-700 transition shadow-md">
                    Update Quotation
                </button>
            </div>
        </form>
    </div>
</div>

<!-- TinyMCE Styles & Scripts -->
<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.3/tinymce.min.js"></script>
<script>
    tinymce.init({
        selector: '#editor-description',
        plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table code help wordcount',
        toolbar: 'undo redo | blocks | ' +
        'bold italic underline | alignleft aligncenter ' +
        'alignright alignjustify | bullist numlist outdent indent | ' +
        'table image charmap | removeformat | help',
        menubar: false,
        height: 300,
        setup: function (editor) {
            editor.on('change', function () {
                tinymce.triggerSave();
            });
        }
    });
</script>
@endsection
