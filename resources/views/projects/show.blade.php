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
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-8">
        <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
            <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Total Kontrak</p>
            <p class="text-xl font-bold text-gray-900 mt-1 uppercase">Rp {{ number_format($project->contract_value, 0, ',', '.') }}</p>
            <p class="text-[10px] text-gray-400 mt-2 italic line-clamp-1">Quotation / Invoice Utama</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
            <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Extra (CR)</p>
            <p class="text-xl font-bold text-indigo-600 mt-1 uppercase">Rp {{ number_format($project->total_cr_value, 0, ',', '.') }}</p>
            <p class="text-[10px] text-gray-400 mt-2 italic">{{ $project->changeRequests()->count() }} CR Items</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
            <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Terbayar</p>
            <p class="text-xl font-bold text-green-600 mt-1 uppercase">Rp {{ number_format($project->paid_amount, 0, ',', '.') }}</p>
            <div class="w-full bg-gray-100 rounded-full h-1 mt-3">
                <div class="bg-green-500 h-1 rounded-full" style="width: {{ $project->grand_total > 0 ? ($project->paid_amount / $project->grand_total) * 100 : 0 }}%"></div>
            </div>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
            <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Tagihan Sisa</p>
            <p class="text-xl font-bold {{ $project->balance_due > 0 ? 'text-red-600' : 'text-green-600' }} mt-1 uppercase">Rp {{ number_format($project->balance_due, 0, ',', '.') }}</p>
            <p class="text-[10px] font-bold mt-2 uppercase {{ $project->balance_due > 0 ? 'text-red-500' : 'text-green-500' }}">
                {{ $project->balance_due > 0 ? 'Belum lunas' : 'Lunas' }}
            </p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
            <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Pengeluaran</p>
            <p class="text-xl font-bold text-amber-600 mt-1 uppercase">Rp {{ number_format($project->total_expenses, 0, ',', '.') }}</p>
            <p class="text-[10px] text-gray-400 mt-2 italic">Total biaya operasional</p>
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
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 text-sm">
                            @forelse($project->invoices as $invoice)
                            <tr>
                                <td class="px-6 py-4 font-medium flex items-center">
                                    {{ $invoice->invoice_number }}
                                    @if($invoice->attachment_pdf)
                                        <svg class="w-3 h-3 ml-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                    @endif
                                </td>
                                <td class="px-6 py-4">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 rounded-full text-[10px] font-bold uppercase {{ $invoice->status == 'paid' ? 'bg-green-100 text-green-700' : ($invoice->status == 'draft' ? 'bg-gray-100 text-gray-600' : 'bg-yellow-100 text-yellow-700') }}">
                                        {{ $invoice->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right flex justify-end items-center space-x-2">
                                    <a href="{{ route('invoices.show', $invoice) }}" class="text-primary font-bold hover:underline">Detail</a>
                                    <span class="text-gray-300">|</span>
                                    <form action="{{ route('invoices.destroy', $invoice) }}" method="POST" class="inline" onsubmit="return confirm('Hapus invoice ini? Semua data pembayaran terkait juga akan terhapus.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 font-bold hover:underline">Hapus</button>
                                    </form>
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
                                <td class="px-6 py-4 font-medium flex items-center">
                                    {{ $quotation->quotation_number }}
                                    @if($quotation->attachment_pdf)
                                        <svg class="w-3 h-3 ml-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                    @endif
                                </td>
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

            <!-- Section: Project Expenses -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden" x-data="{ openAddExpense: false, editExpense: null }">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-gray-50">
                    <h3 class="text-lg font-bold text-gray-800">Pengeluaran Proyek</h3>
                    <button @click="openAddExpense = true; editExpense = null" class="text-sm font-bold text-primary hover:text-blue-700">+ Tambah Pengeluaran</button>
                </div>
                <div class="p-0">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Keterangan</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jumlah</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 text-sm">
                            @php $totalExpenses = 0; @endphp
                            @forelse($project->expenses as $expense)
                            @php $totalExpenses += $expense->amount; @endphp
                            <tr>
                                <td class="px-6 py-4">{{ $expense->date->format('d/m/Y') }}</td>
                                <td class="px-6 py-4">{{ $expense->description }}</td>
                                <td class="px-6 py-4">Rp {{ number_format($expense->amount, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-right flex justify-end space-x-2">
                                    <button @click="editExpense = {{ json_encode($expense) }}; openAddExpense = true" class="text-amber-600 font-bold hover:underline">Edit</button>
                                    <span class="text-gray-300">|</span>
                                    <form action="{{ route('expenses.destroy', $expense) }}" method="POST" class="inline" onsubmit="return confirm('Hapus pengeluaran ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 font-bold hover:underline">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="px-6 py-8 text-center text-gray-400 italic">Belum ada data pengeluaran.</td></tr>
                            @endforelse
                        </tbody>
                        @if($totalExpenses > 0)
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="2" class="px-6 py-3 text-right font-bold text-gray-700">TOTAL PENGELUARAN</td>
                                <td class="px-6 py-3 font-bold text-red-600">Rp {{ number_format($totalExpenses, 0, ',', '.') }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>

                <!-- Modal Form (Add/Edit) -->
                <template x-if="openAddExpense">
                    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="openAddExpense = false"></div>
                            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                                <form :action="editExpense ? '/expenses/' + editExpense.id : '{{ route('projects.expenses.store', $project) }}'" method="POST">
                                    @csrf
                                    <template x-if="editExpense">
                                        <input type="hidden" name="_method" value="PUT">
                                    </template>
                                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                        <h3 class="text-lg leading-6 font-bold text-gray-900 mb-4" id="modal-title" x-text="editExpense ? 'Edit Pengeluaran' : 'Tambah Pengeluaran'"></h3>
                                        <div class="space-y-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Keterangan</label>
                                                <input type="text" name="description" required :value="editExpense ? editExpense.description : ''" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Jumlah (Rp)</label>
                                                <input type="number" name="amount" required :value="editExpense ? editExpense.amount : ''" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Tanggal</label>
                                                <input type="date" name="date" required :value="editExpense ? editExpense.date.split('T')[0] : '{{ date('Y-m-d') }}'" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">Simpan</button>
                                        <button type="button" @click="openAddExpense = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Batal</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </template>
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