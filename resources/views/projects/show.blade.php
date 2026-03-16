@extends('layouts.app')

@section('title', $project->title)

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Breadcrumb & Title -->
    <div class="mb-6 flex justify-between items-start">
        <div>
            <nav class="flex text-sm text-gray-500 mb-2">
                <a href="{{ route('projects.index') }}" class="hover:text-primary">Proyek</a>
                <span class="mx-2">/</span>
                <span class="text-gray-800 font-medium">Detail</span>
            </nav>
            <h1 class="text-3xl font-bold text-gray-900">{{ $project->title }}</h1>
            <p class="text-gray-600 mt-1">Client: <span class="font-semibold">{{ $project->client->name }}</span> {{ $project->client->company_name ? "({$project->client->company_name})" : '' }}</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('projects.edit', $project) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                Edit Proyek
            </a>
            <form action="{{ route('projects.status.update', $project) }}" method="POST" x-data x-ref="statusForm">
                @csrf
                <select name="status" @change="$refs.statusForm.submit()" class="rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary text-xs uppercase font-bold cursor-pointer">
                    @foreach(['draft', 'quotation_sent', 'approved', 'in_progress', 'completed', 'cancelled'] as $st)
                        <option value="{{ $st }}" {{ $project->status == $st ? 'selected' : '' }}>
                            {{ str_replace('_', ' ', strtoupper($st)) }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    <!-- Step Indicator -->
    <div class="mb-8 bg-white p-6 rounded-lg shadow-sm border border-gray-200">
        @php
            $steps = [
                ['label' => 'Quotation', 'statuses' => ['draft', 'quotation_sent']],
                ['label' => 'Approved', 'statuses' => ['approved']],
                ['label' => 'Development', 'statuses' => ['in_progress']],
                ['label' => 'Audit', 'statuses' => []], // Placeholder logic
                ['label' => 'Completed', 'statuses' => ['completed']]
            ];
            
            $currentStepIndex = 0;
            foreach($steps as $index => $step) {
                if(in_array($project->status, $step['statuses'])) {
                    $currentStepIndex = $index;
                }
            }
            if($project->status == 'completed') $currentStepIndex = 4;
            if($project->status == 'in_progress') $currentStepIndex = 2;
            if($project->status == 'approved') $currentStepIndex = 1;
        @endphp
        
        <div class="relative flex items-center justify-between">
            <!-- Progress Line Background -->
            <div class="absolute left-0 top-1/2 w-full h-0.5 bg-gray-200 -translate-y-1/2"></div>
            <!-- Progress Line Active -->
            <div class="absolute left-0 top-1/2 h-0.5 bg-primary -translate-y-1/2 transition-all duration-500" style="width: {{ ($currentStepIndex / (count($steps) - 1)) * 100 }}%"></div>
            
            @foreach($steps as $index => $step)
            <div class="relative flex flex-col items-center">
                <div class="w-8 h-8 rounded-full flex items-center justify-center z-10 transition-colors duration-500 {{ $index <= $currentStepIndex ? 'bg-primary text-white' : 'bg-white border-2 border-gray-300 text-gray-400' }}">
                    @if($index < $currentStepIndex || $project->status == 'completed')
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                    @else
                        <span class="text-xs font-bold">{{ $index + 1 }}</span>
                    @endif
                </div>
                <div class="absolute -bottom-6 whitespace-nowrap">
                    <span class="text-xs font-semibold {{ $index <= $currentStepIndex ? 'text-primary' : 'text-gray-400' }}">{{ $step['label'] }}</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Financial Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
            <p class="text-sm text-gray-500 font-medium">Total Nilai Kontrak</p>
            <p class="text-2xl font-bold text-gray-900 mt-1 uppercase">Rp {{ number_format($project->contract_value, 0, ',', '.') }}</p>
            <p class="text-xs text-gray-400 mt-2">Dari quotation yang disetujui</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
            <p class="text-sm text-gray-500 font-medium">Extra (CR)</p>
            <p class="text-2xl font-bold text-indigo-600 mt-1 uppercase">Rp {{ number_format($project->total_cr_value, 0, ',', '.') }}</p>
            <p class="text-xs text-gray-400 mt-2">{{ $project->changeRequests()->count() }} Change Requests</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
            <p class="text-sm text-gray-500 font-medium">Terbayar</p>
            <p class="text-2xl font-bold text-green-600 mt-1 uppercase">Rp {{ number_format($project->paid_amount, 0, ',', '.') }}</p>
            <div class="w-full bg-gray-200 rounded-full h-1.5 mt-3">
                <div class="bg-green-500 h-1.5 rounded-full" style="width: {{ $project->grand_total > 0 ? ($project->paid_amount / $project->grand_total) * 100 : 0 }}%"></div>
            </div>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
            <p class="text-sm text-gray-500 font-medium">Sisa Tagihan</p>
            <p class="text-2xl font-bold {{ $project->balance_due > 0 ? 'text-red-600' : 'text-green-600' }} mt-1 uppercase">Rp {{ number_format($project->balance_due, 0, ',', '.') }}</p>
            <p class="text-xs font-bold mt-2 uppercase {{ $project->balance_due > 0 ? 'text-red-500' : 'text-green-500' }}">
                {{ $project->balance_due > 0 ? 'Belum lunas' : 'Lunas' }}
            </p>
        </div>
    </div>

    <!-- Details Tabs/Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Invoices & CRs -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Section: Invoices -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-gray-50">
                    <h3 class="text-lg font-bold text-gray-800">Tagihan (Invoices)</h3>
                    <a href="{{ route('projects.invoices.create', $project) }}" class="text-sm font-bold text-primary hover:text-blue-700">+ Buat Invoice</a>
                </div>
                <div class="p-0">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No. Invoice</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipe</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 text-sm">
                            @forelse($project->invoices as $invoice)
                            <tr>
                                <td class="px-6 py-4 font-medium">{{ $invoice->invoice_number }}</td>
                                <td class="px-6 py-4 capitalize">{{ $invoice->type }}</td>
                                <td class="px-6 py-4">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 rounded-full text-[10px] font-bold uppercase {{ $invoice->status == 'paid' ? 'bg-green-100 text-green-700' : ($invoice->status == 'draft' ? 'bg-gray-100 text-gray-600' : 'bg-yellow-100 text-yellow-700') }}">
                                        {{ $invoice->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('invoices.show', $invoice) }}" class="text-primary font-bold hover:underline">Detail</a>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="px-6 py-8 text-center text-gray-400 italic">Belum ada invoice.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Section: Quotations -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-gray-50">
                    <h3 class="text-lg font-bold text-gray-800">Penawaran (Quotations)</h3>
                    <a href="{{ route('projects.quotations.create', $project) }}" class="text-sm font-bold text-primary hover:text-blue-700">+ Buat Quotation</a>
                </div>
                <div class="p-0">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No. Quotation</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 text-sm">
                            @forelse($project->quotations as $quotation)
                            <tr>
                                <td class="px-6 py-4 font-medium">{{ $quotation->quotation_number }}</td>
                                <td class="px-6 py-4">Rp {{ number_format($quotation->total_amount, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 uppercase font-bold text-[10px]">{{ $quotation->status }}</td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('quotations.show', $quotation) }}" class="text-primary font-bold hover:underline">View</a>
                                    @if($quotation->status != 'approved')
                                        <span class="mx-1 text-gray-300">|</span>
                                        <a href="{{ route('quotations.edit', $quotation) }}" class="text-amber-600 font-bold hover:underline">Edit</a>
                                        <span class="mx-1 text-gray-300">|</span>
                                        <form action="{{ route('quotations.destroy', $quotation) }}" method="POST" class="inline" onsubmit="return confirm('Hapus penawaran ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 font-bold hover:underline">Hapus</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="px-6 py-8 text-center text-gray-400 italic">Belum ada quotation.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Sidebar Info -->
        <div class="space-y-8">
            <!-- Client Card -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">Informasi Client</h3>
                <div class="space-y-4">
                    <div>
                        <p class="text-xs text-gray-500">Nama PIC</p>
                        <p class="font-bold text-gray-800">{{ $project->client->name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Perusahaan</p>
                        <p class="font-bold text-gray-800">{{ $project->client->company_name ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Kontak</p>
                        <p class="text-sm text-gray-800">{{ $project->client->email }}</p>
                        <p class="text-sm text-gray-800">{{ $project->client->phone }}</p>
                    </div>
                    <div class="pt-4 border-t border-gray-100">
                        <a href="{{ route('clients.show', $project->client) }}" class="text-xs font-bold text-primary uppercase hover:underline">Lihat Semua Proyek Client &rarr;</a>
                    </div>
                </div>
            </div>

            <!-- Timeline/Notes -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">Catatan Operasional</h3>
                <div class="space-y-4">
                    <div class="text-sm text-gray-600 italic">
                        "Pastikan semua deployment melalui approval engineer sebelum status diubah ke Completed."
                    </div>
                    <div class="flex items-center text-xs text-gray-400">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Terakhir diupdate: {{ $project->updated_at->diffForHumans() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection