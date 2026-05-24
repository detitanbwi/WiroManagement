@extends('layouts.app')

@section('title', 'System Settings')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">System Settings</h1>
        <p class="text-gray-500 text-sm">Konfigurasi dokumen dan informasi perusahaan.</p>
    </div>

    <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
        <form action="{{ route('settings.update') }}" method="POST" id="settings-form" class="p-6 space-y-8">
            @csrf
            
            <!-- Section: Document Terms -->
            <div class="space-y-6">
                <div>
                    <h3 class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-4 border-b pb-2">Terms & Conditions (Invoice)</h3>
                    <label for="terms_conditions" class="block text-sm font-medium text-gray-700 mb-1">Garis besar syarat pembayaran dan ketentuan teknis invoice.</label>
                    <div class="bg-white">
                        <textarea name="settings[terms_conditions]" id="editor-invoice-terms" style="height: 150px;">{!! $settings['terms_conditions'] ?? "" !!}</textarea>
                    </div>
                </div>

                <div>
                    <h3 class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-4 border-b pb-2">Quotation Notes (Penawaran)</h3>
                    <label for="quotation_notes" class="block text-sm font-medium text-gray-700 mb-1">Catatan standar yang muncul di bagian bawah penawaran harga.</label>
                    <div class="bg-white">
                        <textarea name="settings[quotation_notes]" id="editor-quotation-notes" style="height: 150px;">{!! $settings['quotation_notes'] ?? "" !!}</textarea>
                    </div>
                    <p class="mt-2 text-[10px] text-gray-400">Contoh: Masa berlaku penawaran, kebijakan DP, dan lingkup maintenance.</p>
                </div>

                <div>
                    <h3 class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-4 border-b pb-2">Syarat & Ketentuan (Quotation)</h3>
                    <label for="quotation_terms" class="block text-sm font-medium text-gray-700 mb-1">Halaman khusus Syarat & Ketentuan yang akan muncul di halaman baru pada PDF Quotation.</label>
                    <div class="bg-white">
                        <textarea name="settings[quotation_terms]" id="editor-quotation-terms" style="height: 250px;">{!! $settings['quotation_terms'] ?? "" !!}</textarea>
                    </div>
                </div>
            </div>

            <div class="pt-6 border-t border-gray-100 flex justify-end">
                <button type="submit" id="submit-btn" class="inline-flex items-center px-6 py-3 bg-primary border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 transition ease-in-out duration-150">
                    Simpan Konfigurasi
                </button>
            </div>
        </form>
    </div>
</div>

<!-- TinyMCE Styles & Scripts -->
<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.3/tinymce.min.js"></script>
<script>
    function setupTinyMCE(selector, height) {
        tinymce.init({
            selector: selector,
            plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table code help wordcount',
            toolbar: 'undo redo | blocks | ' +
            'bold italic underline | alignleft aligncenter ' +
            'alignright alignjustify | bullist numlist outdent indent | ' +
            'table image charmap | removeformat | help',
            menubar: false,
            height: height,
            setup: function (editor) {
                editor.on('change', function () {
                    tinymce.triggerSave();
                });
            }
        });
    }

    setupTinyMCE('#editor-invoice-terms', 200);
    setupTinyMCE('#editor-quotation-notes', 200);
    setupTinyMCE('#editor-quotation-terms', 350);
</script>
@endsection
