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
                    <textarea name="settings[terms_conditions]" id="terms_conditions" rows="6" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm font-sans text-xs">{{ $settings['terms_conditions'] ?? "" }}</textarea>
                </div>

                <div>
                    <h3 class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-4 border-b pb-2">Quotation Notes (Penawaran)</h3>
                    <label for="quotation_notes" class="block text-sm font-medium text-gray-700 mb-1">Catatan standar yang muncul di bagian bawah penawaran harga.</label>
                    <textarea name="settings[quotation_notes]" id="quotation_notes" rows="6" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm font-sans text-xs">{{ $settings['quotation_notes'] ?? "" }}</textarea>
                    <p class="mt-2 text-[10px] text-gray-400">Contoh: Masa berlaku penawaran, kebijakan DP, dan lingkup maintenance.</p>
                </div>
            </div>

            <div class="pt-6 border-t border-gray-100 flex justify-end">
                <button type="submit" class="inline-flex items-center px-6 py-3 bg-primary border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 transition ease-in-out duration-150">
                    Simpan Konfigurasi
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
