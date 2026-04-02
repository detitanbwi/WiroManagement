@extends('layouts.app')

@section('title', 'Detail Quotation')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6 flex justify-between items-end">
        <div>
            <a href="{{ route('projects.show', $quotation->project) }}" class="text-sm text-gray-500 hover:text-primary flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                Kembali ke Proyek
            </a>
            <h1 class="text-2xl font-bold text-gray-800 mt-2">Quotation: {{ $quotation->quotation_number }}</h1>
        </div>
        <div class="flex space-x-2">
            @if($quotation->status == 'draft')
                <a href="{{ route('quotations.edit', $quotation) }}" class="inline-flex items-center px-4 py-2 bg-amber-500 border border-transparent rounded-md font-bold text-xs text-white uppercase tracking-widest hover:bg-amber-600 transition shadow-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    EDIT DRAFT
                </a>
            @endif

            @if($quotation->status == 'approved')
                <form action="{{ route('quotations.convert', $quotation) }}" method="POST" onsubmit="return confirm('Konversi penawaran ini menjadi Invoice?')">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-bold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none transition ease-in-out duration-150 shadow-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                        KONVERSI KE INVOICE
                    </button>
                </form>
            @endif
            <a href="{{ route('documents.quotation.pdf', $quotation) }}" target="_blank" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-bold text-xs text-white uppercase tracking-widest hover:bg-red-700 active:bg-red-900 focus:outline-none focus:border-red-900 focus:ring ring-red-300 transition ease-in-out duration-150 shadow-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                CETAK PDF
            </a>
        </div>
    </div>

    <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-8">
        <div class="flex justify-between items-start mb-8">
            <div>
                <h2 class="text-2xl font-bold text-primary italic tracking-tight">WIRODEV</h2>
                <p class="text-xs text-gray-500">Software House & Digital Agency</p>
            </div>
            <div class="text-right">
                <span class="px-3 py-1 rounded-full text-xs font-bold uppercase {{ $quotation->status == 'approved' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                    {{ $quotation->status }}
                </span>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-8 mb-8 border-t pt-6">
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase mb-1">Diterbitkan Untuk:</p>
                <p class="font-bold text-gray-800">{{ $quotation->project->client->name }}</p>
                <p class="text-sm text-gray-600">{{ $quotation->project->client->company_name }}</p>
            </div>
            <div class="text-right">
                <p class="text-xs font-bold text-gray-400 uppercase mb-1">Detail Penawaran:</p>
                <p class="text-sm text-gray-600">No: <span class="font-bold text-gray-800">{{ $quotation->quotation_number }}</span></p>
                <p class="text-sm text-gray-600">Tanggal: <span class="font-bold text-gray-800">{{ $quotation->created_at->format('d F Y') }}</span></p>
            </div>
        </div>

        <table class="w-full mb-8">
            <thead class="bg-gray-50 border-y border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500">DESKRIPSI PROYEK</th>
                    <th class="px-4 py-3 text-right text-xs font-bold text-gray-500">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                <tr class="border-b border-gray-100">
                    <td class="px-4 py-6">
                        <p class="font-bold text-gray-800">{{ $quotation->project->title }}</p>
                        <div class="text-sm text-gray-700 mt-2 space-y-1">
                            {!! $quotation->description ?? 'Penawaran harga resmi untuk pengembangan proyek yang tertera.' !!}
                        </div>
                    </td>
                    <td class="px-4 py-6 text-right font-bold text-gray-800 text-lg">
                        Rp {{ number_format($quotation->total_amount, 0, ',', '.') }}
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="flex justify-end">
            <div class="w-64 text-center">
                <p class="text-xs text-gray-500 mb-16">Mengetahui,</p>
                <p class="font-bold text-gray-800 underline">Administration</p>
                <p class="text-[10px] text-gray-400 uppercase">Wirodev Finance</p>
            </div>
        </div>
    </div>
</div>
@endsection
