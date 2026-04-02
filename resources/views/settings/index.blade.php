@extends('layouts.app')

@section('title', 'System Settings')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">System Settings</h1>
        <p class="text-gray-500 text-sm">Konfigurasi dokumen dan informasi perusahaan.</p>
    </div>

    <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
        <form action="{{ route('settings.update') }}" method="POST" class="p-6 space-y-8">
            @csrf
            
            <!-- Section: Document Terms -->
            <div class="space-y-6">
                <div>
                    <h3 class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-4 border-b pb-2">Terms & Conditions (Invoice)</h3>
                    <label for="terms_conditions" class="block text-sm font-medium text-gray-700 mb-1">Garis besar syarat pembayaran dan ketentuan teknis invoice.</label>
                    <div class="bg-white">
                        <div id="editor-invoice-terms" style="height: 150px;">
                            {!! $settings['terms_conditions'] ?? "" !!}
                        </div>
                    </div>
                    <textarea name="settings[terms_conditions]" id="invoice_terms_hidden" class="hidden"></textarea>
                </div>

                <div>
                    <h3 class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-4 border-b pb-2">Quotation Notes (Penawaran)</h3>
                    <label for="quotation_notes" class="block text-sm font-medium text-gray-700 mb-1">Catatan standar yang muncul di bagian bawah penawaran harga.</label>
                    <div class="bg-white">
                        <div id="editor-quotation-notes" style="height: 150px;">
                            {!! $settings['quotation_notes'] ?? "" !!}
                        </div>
                    </div>
                    <textarea name="settings[quotation_notes]" id="quotation_notes_hidden" class="hidden"></textarea>
                    <p class="mt-2 text-[10px] text-gray-400">Contoh: Masa berlaku penawaran, kebijakan DP, dan lingkup maintenance.</p>
                </div>

                <div>
                    <h3 class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-4 border-b pb-2">Syarat & Ketentuan (Quotation)</h3>
                    <label for="quotation_terms" class="block text-sm font-medium text-gray-700 mb-1">Halaman khusus Syarat & Ketentuan yang akan muncul di halaman baru pada PDF Quotation.</label>
                    <div class="bg-white">
                        <div id="editor-quotation-terms" style="height: 250px;">
                            {!! $settings['quotation_terms'] ?? "" !!}
                        </div>
                    </div>
                    <textarea name="settings[quotation_terms]" id="quotation_terms_hidden" class="hidden"></textarea>
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

<!-- Quill Styles & Scripts -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<script>
    function setupQuill(id, hiddenId) {
        var quill = new Quill(id, {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    ['clean']
                ]
            }
        });
        return quill;
    }

    var qInvoiceTerms = setupQuill('#editor-invoice-terms', '#invoice_terms_hidden');
    var qQuoNotes = setupQuill('#editor-quotation-notes', '#quotation_notes_hidden');
    var qQuoTerms = setupQuill('#editor-quotation-terms', '#quotation_terms_hidden');

    var form = document.querySelector('form');
    form.onsubmit = function() {
        document.querySelector('#invoice_terms_hidden').value = qInvoiceTerms.root.innerHTML;
        document.querySelector('#quotation_notes_hidden').value = qQuoNotes.root.innerHTML;
        document.querySelector('#quotation_terms_hidden').value = qQuoTerms.root.innerHTML;
        return true;
    };
</script>
@endsection
