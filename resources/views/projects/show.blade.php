@extends('layouts.app')

@section('title', $project->title)

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Breadcrumb & Title -->
    <div class="mb-6 flex flex-col md:flex-row md:justify-between md:items-start space-y-4 md:space-y-0">
        <div>
            <nav class="flex text-sm text-gray-500 mb-2">
                <a href="{{ route('projects.index') }}" class="hover:text-primary">Projects</a>
                <span class="mx-2">/</span>
                <span class="text-gray-800 font-medium">Details</span>
            </nav>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900">{{ $project->title }}</h1>
            <p class="text-sm md:text-base text-gray-600 mt-1">Client: <span class="font-semibold text-gray-900">{{ $project->client->name }}</span> {{ $project->client->company_name ? "({$project->client->company_name})" : '' }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2 md:space-x-3">
            @can('projects.gantt')
            <a href="{{ route('projects.gantt', $project) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md font-semibold text-[10px] md:text-xs uppercase tracking-widest shadow-sm transition">
                Gantt Chart
            </a>
            @endcan

            @if(auth()->user()->canAccessProjectQc($project))
            <a href="{{ route('projects.qc', $project) }}" class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-md font-semibold text-[10px] md:text-xs uppercase tracking-widest shadow-sm transition">
                QA / QC Board
            </a>
            @endif

            @can('projects.edit')
            <a href="{{ route('projects.edit', $project) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-[10px] md:text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                Edit Project
            </a>
            @endcan

            @can('projects.status')
            <form action="{{ route('projects.status.update', $project) }}" method="POST" x-data x-ref="statusForm">
                @csrf
                <select name="status" @change="$refs.statusForm.submit()" class="rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary text-[10px] md:text-xs uppercase font-bold cursor-pointer bg-white">
                    @foreach(['draft', 'quotation_sent', 'approved', 'in_progress', 'completed', 'cancelled'] as $st)
                        <option value="{{ $st }}" {{ $project->status == $st ? 'selected' : '' }}>
                            {{ str_replace('_', ' ', strtoupper($st)) }}
                        </option>
                    @endforeach
                </select>
            </form>
            @else
            <span class="px-3 py-2 bg-gray-100 border border-gray-200 text-gray-700 rounded-md text-[10px] md:text-xs uppercase font-bold">
                {{ str_replace('_', ' ', strtoupper($project->status)) }}
            </span>
            @endcan
        </div>
    </div>

    <!-- Step Indicator -->
    <div class="mb-8 bg-white p-6 pb-10 rounded-lg shadow-sm border border-gray-200">
        @php
            $steps = [
                ['label' => 'Quotation', 'statuses' => ['draft', 'quotation_sent']],
                ['label' => 'Approved', 'statuses' => ['approved']],
                ['label' => 'Development', 'statuses' => ['in_progress']],
                ['label' => 'Audit', 'statuses' => []],
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
        
        <div class="relative flex items-center justify-between px-2 md:px-0">
            <!-- Progress Line Background -->
            <div class="absolute left-0 top-1/2 w-full h-0.5 bg-gray-200 -translate-y-1/2"></div>
            <!-- Progress Line Active -->
            <div class="absolute left-0 top-1/2 h-0.5 bg-primary -translate-y-1/2 transition-all duration-500" style="width: {{ ($currentStepIndex / (count($steps) - 1)) * 100 }}%"></div>
            
            @foreach($steps as $index => $step)
            <div class="relative flex flex-col items-center">
                <div class="w-6 h-6 md:w-8 md:h-8 rounded-full flex items-center justify-center z-10 transition-colors duration-500 {{ $index <= $currentStepIndex ? 'bg-primary text-white' : 'bg-white border-2 border-gray-300 text-gray-400' }}">
                    @if($index < $currentStepIndex || $project->status == 'completed')
                        <svg class="w-3 h-3 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                    @else
                        <span class="text-[10px] md:text-xs font-bold">{{ $index + 1 }}</span>
                    @endif
                </div>
                <div class="absolute -bottom-6 w-16 md:w-24 flex justify-center">
                    <span class="text-[8px] md:text-xs font-semibold {{ $index <= $currentStepIndex ? 'text-primary' : 'text-gray-400' }} text-center leading-tight">{{ $step['label'] }}</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Financial Cards -->
    @canany(['projects.view_financial', 'finance.view'])
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-8">
        <div class="p-4 rounded-lg shadow-md bg-gradient-to-br from-blue-600 to-indigo-600 text-white">
            <p class="text-[10px] text-blue-100 font-bold uppercase tracking-wider">Total Contract</p>
            <p class="text-xl font-bold text-white mt-1 uppercase">Rp {{ number_format($project->contract_value, 0, ',', '.') }}</p>
            <p class="text-[10px] text-blue-100 mt-2 italic line-clamp-1">Main Quotation / Invoice</p>
        </div>
        <div class="p-4 rounded-lg shadow-md bg-gradient-to-br from-purple-600 to-fuchsia-500 text-white">
            <p class="text-[10px] text-purple-100 font-bold uppercase tracking-wider">Extra (CR)</p>
            <p class="text-xl font-bold text-white mt-1 uppercase">Rp {{ number_format($project->total_cr_value, 0, ',', '.') }}</p>
            <p class="text-[10px] text-purple-100 mt-2 italic">{{ $project->changeRequests()->count() }} CR Items</p>
        </div>
        <div class="p-4 rounded-lg shadow-md bg-gradient-to-br from-teal-500 to-emerald-500 text-white">
            <p class="text-[10px] text-teal-100 font-bold uppercase tracking-wider">Collected (Paid)</p>
            <p class="text-xl font-bold text-white mt-1 uppercase">Rp {{ number_format($project->paid_amount, 0, ',', '.') }}</p>
            <div class="w-full bg-white/20 rounded-full h-1 mt-3">
                <div class="bg-white h-1 rounded-full" style="width: {{ $project->grand_total > 0 ? ($project->paid_amount / $project->grand_total) * 100 : 0 }}%"></div>
            </div>
        </div>
        <div class="p-4 rounded-lg shadow-md bg-gradient-to-br {{ $project->balance_due > 0 ? 'from-rose-500 to-red-500' : 'from-emerald-500 to-teal-500' }} text-white">
            <p class="text-[10px] text-white/80 font-bold uppercase tracking-wider">Balance Due</p>
            <p class="text-xl font-bold text-white mt-1 uppercase">Rp {{ number_format($project->balance_due, 0, ',', '.') }}</p>
            <p class="text-[10px] font-bold mt-2 uppercase text-white/90">
                {{ $project->balance_due > 0 ? 'Unpaid' : 'Paid' }}
            </p>
        </div>
        <div class="p-4 rounded-lg shadow-md bg-gradient-to-br from-amber-500 to-orange-400 text-white">
            <p class="text-[10px] text-amber-100 font-bold uppercase tracking-wider">Total Beban (COGS)</p>
            <p class="text-xl font-bold text-white mt-1 uppercase">Rp {{ number_format($project->cogs(false), 0, ',', '.') }}</p>
            <p class="text-[10px] text-amber-100 mt-2 italic">Upah: Rp {{ number_format($project->totalLaborCost(false), 0, ',', '.') }} | Ops: Rp {{ number_format($project->totalInfraCost(false), 0, ',', '.') }}</p>
        </div>
    </div>
    @endcanany

    <!-- Details Tabs/Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Invoices & CRs -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Section: Invoices -->
            @canany(['invoices.manage', 'finance.view'])
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-blue-600 flex justify-between items-center bg-gradient-to-r from-blue-600 to-indigo-500 text-white">
                    <h3 class="text-lg font-bold text-white shadow-sm">Invoices</h3>
                    @can('invoices.manage')
                    <a href="{{ route('projects.invoices.create', $project) }}" class="text-sm font-bold text-blue-50 hover:text-white bg-white/20 px-3 py-1.5 rounded-lg backdrop-blur-sm transition-colors">+ New Invoice</a>
                    @endcan
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invoice No.</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
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
                                    @can('invoices.manage')
                                    <a href="{{ route('invoices.show', $invoice) }}" class="text-primary font-bold hover:underline">View</a>
                                    <span class="text-gray-300">|</span>
                                    <form action="{{ route('invoices.destroy', $invoice) }}" method="POST" class="inline" onsubmit="return confirm('Delete this invoice? All associated payment records will also be deleted.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 font-bold hover:underline">Delete</button>
                                    </form>
                                    @else
                                    <span class="text-xs text-gray-400 italic">View Only</span>
                                    @endcan
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="px-6 py-8 text-center text-gray-400 italic">No invoices found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @endcanany

            <!-- Section: Quotations -->
            @canany(['quotations.manage', 'finance.view'])
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-teal-600 flex justify-between items-center bg-gradient-to-r from-teal-600 to-emerald-500 text-white">
                    <h3 class="text-lg font-bold text-white shadow-sm">Quotations</h3>
                    @can('quotations.manage')
                    <a href="{{ route('projects.quotations.create', $project) }}" class="text-sm font-bold text-teal-50 hover:text-white bg-white/20 px-3 py-1.5 rounded-lg backdrop-blur-sm transition-colors">+ New Quotation</a>
                    @endcan
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quotation No.</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
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
                                    @can('quotations.manage')
                                    <a href="{{ route('quotations.show', $quotation) }}" class="text-primary font-bold hover:underline">View</a>
                                    @if($quotation->status != 'approved')
                                        <span class="mx-1 text-gray-300">|</span>
                                        <a href="{{ route('quotations.edit', $quotation) }}" class="text-amber-600 font-bold hover:underline">Edit</a>
                                        <span class="mx-1 text-gray-300">|</span>
                                        <form action="{{ route('quotations.destroy', $quotation) }}" method="POST" class="inline" onsubmit="return confirm('Delete this quotation?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 font-bold hover:underline">Delete</button>
                                        </form>
                                    @endif
                                    @else
                                    <span class="text-xs text-gray-400 italic">View Only</span>
                                    @endcan
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="px-6 py-8 text-center text-gray-400 italic">No quotations found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @endcanany

            <!-- Section: Project Expenses -->
            @canany(['expenses.manage', 'finance.view'])
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden" x-data="{ 
                openAddExpense: false, 
                editExpense: null,
                rawAmount: 0,
                isPaid: 1,
                expenseDate: '{{ date('Y-m-d') }}',
                formatThousand(val) {
                    if (!val || val === '0') return '0';
                    return new Intl.NumberFormat('id-ID').format(val);
                },
                parseNumber(val) {
                    let num = val.toString().replace(/\D/g, '');
                    return num ? parseInt(num) : 0;
                },
                resetForm() {
                    this.editExpense = null;
                    this.rawAmount = 0;
                    this.isPaid = 1;
                    this.expenseDate = '{{ date('Y-m-d') }}';
                }
            }">
                <div class="px-6 py-4 border-b border-amber-500 flex justify-between items-center bg-gradient-to-r from-amber-500 to-orange-400 text-white">
                    <h3 class="text-lg font-bold text-white shadow-sm">Project Expenses</h3>
                    @can('expenses.manage')
                    <button @click="resetForm(); openAddExpense = true" class="text-xs font-bold text-amber-50 hover:text-white bg-white/20 px-3 py-1.5 rounded-lg backdrop-blur-sm uppercase tracking-widest transition-colors">+ Add Expense</button>
                    @endcan
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 text-sm">
                            @php $totalExpenses = 0; @endphp
                            @forelse($project->expenses as $expense)
                            @php $totalExpenses += $expense->amount; @endphp
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($expense->is_paid)
                                        <span class="px-2.5 py-1 inline-flex text-xs leading-4 font-semibold rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            Paid
                                        </span>
                                    @else
                                        <span class="px-2.5 py-1 inline-flex text-xs leading-4 font-semibold rounded-full bg-amber-100 text-amber-800 border border-amber-200">
                                            Unpaid
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-600">
                                    {{ $expense->is_paid && $expense->date ? $expense->date->format('d/m/Y') : '-' }}
                                </td>
                                <td class="px-6 py-4 font-medium">{{ $expense->description }}</td>
                                <td class="px-6 py-4 font-bold text-gray-900">Rp {{ number_format($expense->amount, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-right flex justify-end space-x-2">
                                    @can('expenses.manage')
                                    <button @click="editExpense = {{ json_encode($expense) }}; rawAmount = editExpense.amount; isPaid = editExpense.is_paid ? 1 : 0; expenseDate = (editExpense.date ? editExpense.date.split('T')[0] : '{{ date('Y-m-d') }}'); openAddExpense = true" class="text-amber-600 font-bold hover:underline">Edit</button>
                                    <span class="text-gray-300">|</span>
                                    <form action="{{ route('expenses.destroy', $expense) }}" method="POST" class="inline" onsubmit="return confirm('Delete this expense?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 font-bold hover:underline">Delete</button>
                                    </form>
                                    @else
                                    <span class="text-xs text-gray-400 italic">View Only</span>
                                    @endcan
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="px-6 py-8 text-center text-gray-400 italic">No expenses recorded.</td></tr>
                            @endforelse
                        </tbody>
                        @if($totalExpenses > 0)
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="3" class="px-6 py-3 text-right font-black text-gray-700 text-xs uppercase tracking-widest">TOTAL EXPENSES</td>
                                <td class="px-6 py-3 font-black text-red-600 text-lg">Rp {{ number_format($totalExpenses, 0, ',', '.') }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>

                <!-- Modal Form (Add/Edit) -->
                @can('expenses.manage')
                <template x-if="openAddExpense">
                    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="openAddExpense = false"></div>
                            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                                <form :action="editExpense ? '/expenses/' + editExpense.id : '{{ route('projects.expenses.store', $project) }}'" method="POST">
                                    @csrf
                                    <template x-if="editExpense">
                                        <input type="hidden" name="_method" value="PUT">
                                    </template>
                                    <div class="bg-white px-4 pt-5 pb-4 sm:p-8 sm:pb-4">
                                        <h3 class="text-xl leading-6 font-black text-gray-900 mb-6 uppercase tracking-widest" id="modal-title" x-text="editExpense ? 'Edit Expense' : 'Add Expense'"></h3>
                                        <div class="space-y-6">
                                            <div>
                                                <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Expense Description</label>
                                                <input type="text" name="description" required :value="editExpense ? editExpense.description : ''" class="block w-full border-gray-300 rounded-xl shadow-sm focus:ring-primary focus:border-primary sm:text-sm p-4 border font-medium">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Amount (Rp)</label>
                                                <div class="relative">
                                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                        <span class="text-gray-400 font-bold">Rp</span>
                                                    </div>
                                                    <input type="text" 
                                                        :value="formatThousand(rawAmount)"
                                                        @input="rawAmount = parseNumber($event.target.value)"
                                                        class="block w-full pl-12 border-gray-300 rounded-xl shadow-sm focus:ring-primary focus:border-primary sm:text-lg p-4 border font-black text-primary">
                                                    <input type="hidden" name="amount" :value="rawAmount">
                                                </div>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Payment Status</label>
                                                <div class="grid grid-cols-2 gap-3">
                                                    <label :class="isPaid == 1 ? 'border-primary bg-primary/5 text-primary ring-2 ring-primary/20' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'" class="flex items-center justify-center p-3 border rounded-xl cursor-pointer font-bold text-sm transition">
                                                        <input type="radio" name="is_paid" value="1" x-model="isPaid" class="sr-only">
                                                        <span>Paid</span>
                                                    </label>
                                                    <label :class="isPaid == 0 ? 'border-amber-500 bg-amber-50 text-amber-800 ring-2 ring-amber-500/20' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'" class="flex items-center justify-center p-3 border rounded-xl cursor-pointer font-bold text-sm transition">
                                                        <input type="radio" name="is_paid" value="0" x-model="isPaid" class="sr-only">
                                                        <span>Unpaid</span>
                                                    </label>
                                                </div>
                                            </div>
                                            <div x-show="isPaid == 1" x-transition>
                                                <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Payment Date</label>
                                                <input type="date" name="date" x-model="expenseDate" class="block w-full border-gray-300 rounded-xl shadow-sm focus:ring-primary focus:border-primary sm:text-sm p-4 border font-bold text-gray-700">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bg-gray-50 px-4 py-4 sm:px-8 sm:flex sm:flex-row-reverse gap-3">
                                        <button type="submit" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-lg px-6 py-3 bg-primary text-sm font-black text-white uppercase tracking-widest hover:bg-blue-800 transition sm:w-auto">Save Expense</button>
                                        <button type="button" @click="openAddExpense = false" class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-6 py-3 bg-white text-sm font-bold text-gray-700 uppercase tracking-widest hover:bg-gray-100 transition sm:mt-0 sm:w-auto">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </template>
                @endcan
            </div>
            @endcanany

            <!-- Section: Log Kerja & Biaya Tenaga Kerja (Project Labor Costs) -->
            @canany(['finance.work_logs.view', 'finance.work_logs.manage', 'projects.manage', 'finance.view'])
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden" x-data="{
                openAddWorkLog: false,
                editWorkLog: null,
                members: {{ json_encode($project->members->map(function($m) {
                    return [
                        'user_id' => $m->user_id,
                        'name' => $m->user->name ?? 'User',
                        'role_in_project' => $m->role_in_project ?: ($m->memberRate?->role_slug ?: 'Staff Proyek'),
                        'rate_per_hour' => (float)($m->rate_per_hour ?: 0),
                        'weekend_multiplier' => (float)($m->weekend_multiplier ?: 1.10),
                        'overtime_multiplier' => (float)($m->overtime_multiplier ?: 1.20),
                    ];
                })) }},
                masterRates: {{ json_encode($manHourRates) }},
                date_ranges: [
                    { start_date: '{{ date('Y-m-d') }}', end_date: '{{ date('Y-m-d') }}', hours_per_day: 8, is_weekend: false, is_overtime: false }
                ],
                form: {
                    user_id: '',
                    role_in_project: '',
                    skill_level: 'middle',
                    work_date: '{{ date('Y-m-d') }}',
                    end_date: '{{ date('Y-m-d') }}',
                    hours_per_day: 8.0,
                    days_count: 1,
                    hours_worked: 8.0,
                    base_rate: 0,
                    source_account_type: 'office_account',
                    is_weekend: false,
                    is_overtime: false,
                    payment_status: 'unpaid',
                    payment_date: '',
                    notes: '',
                },
                get uniqueRoles() {
                    const roles = [];
                    (this.masterRates || []).forEach(r => {
                        if (r.role_slug && !roles.includes(r.role_slug)) {
                            roles.push(r.role_slug);
                        }
                    });
                    return roles;
                },
                formatRoleName(slug) {
                    if (!slug) return '';
                    const map = {
                        'developer': 'Developer',
                        'pm': 'Project Manager (PM)',
                        'qc': 'Quality Control (QC)',
                        'designer': 'UI/UX Designer',
                        'devops': 'DevOps'
                    };
                    if (map[slug.toLowerCase()]) return map[slug.toLowerCase()];
                    return slug.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                },
                init() {
                    if (this.uniqueRoles.length > 0) {
                        this.form.role_in_project = this.uniqueRoles[0];
                    }
                    if (this.members.length > 0) {
                        this.form.user_id = this.members[0].user_id;
                        this.onMemberChange();
                    } else {
                        this.onRoleOrSkillChange();
                    }
                    this.checkDateFlags();
                },
                onMemberChange() {
                    const selected = this.members.find(m => m.user_id == this.form.user_id);
                    if (selected) {
                        const memberRole = (selected.role_in_project || '').toLowerCase().trim();
                        const matchedRate = this.masterRates.find(r => 
                            r.role_slug.toLowerCase() === memberRole || 
                            r.role_slug.toLowerCase().replace(/_/g, ' ') === memberRole
                        );
                        if (matchedRate) {
                            this.form.role_in_project = matchedRate.role_slug;
                        } else if (selected.role_in_project) {
                            this.form.role_in_project = selected.role_in_project;
                        }

                        if (selected.rate_per_hour && parseFloat(selected.rate_per_hour) > 0) {
                            this.form.base_rate = parseFloat(selected.rate_per_hour);
                        } else {
                            this.onRoleOrSkillChange();
                        }
                    }
                },
                onRoleOrSkillChange() {
                    if (!this.form.role_in_project) return;
                    const level = this.form.skill_level || 'middle';
                    const match = this.masterRates.find(r => 
                        r.role_slug.toLowerCase() === this.form.role_in_project.toLowerCase() && 
                        r.skill_level.toLowerCase() === level.toLowerCase()
                    );
                    if (match) {
                        this.form.base_rate = parseFloat(match.base_rate_per_hour || match.base_rate || 0);
                    }
                },
                addDateRange() {
                    const last = this.date_ranges[this.date_ranges.length - 1];
                    const nextDate = last ? last.end_date : '{{ date('Y-m-d') }}';
                    const nextHpd = last ? last.hours_per_day : 8;
                    this.date_ranges.push({
                        start_date: nextDate,
                        end_date: nextDate,
                        hours_per_day: nextHpd,
                        is_weekend: false,
                        is_overtime: false
                    });
                },
                removeDateRange(idx) {
                    if (this.date_ranges.length > 1) {
                        this.date_ranges.splice(idx, 1);
                    }
                },
                calculateDays(startDate, endDate) {
                    if (!startDate || !endDate) return 1;
                    const start = new Date(startDate + 'T00:00:00');
                    const end = new Date(endDate + 'T00:00:00');
                    if (isNaN(start.getTime()) || isNaN(end.getTime()) || end < start) return 1;
                    const diffTime = end.getTime() - start.getTime();
                    return Math.floor(diffTime / (1000 * 60 * 60 * 24)) + 1;
                },
                getRangeDays(range) {
                    if (!range || !range.start_date || !range.end_date) return 0;
                    return this.calculateDays(range.start_date, range.end_date);
                },
                onEditRangeChange() {
                    if (!this.editWorkLog) return;
                    if (this.form.end_date && this.form.work_date && this.form.end_date < this.form.work_date) {
                        this.form.end_date = this.form.work_date;
                    }
                    this.form.days_count = this.calculateDays(this.form.work_date, this.form.end_date);
                    const hpd = parseFloat(this.form.hours_per_day) || 0;
                    this.form.hours_worked = parseFloat((this.form.days_count * hpd).toFixed(2));
                    this.checkDateFlags();
                    this.checkHoursFlags();
                },
                get totalCalculatedDays() {
                    if (this.editWorkLog) {
                        return this.form.days_count || 1;
                    }
                    return this.date_ranges.reduce((sum, r) => sum + this.getRangeDays(r), 0);
                },
                get totalCalculatedHours() {
                    if (this.editWorkLog) {
                        const days = this.form.days_count || 1;
                        const hpd = parseFloat(this.form.hours_per_day) || 0;
                        return parseFloat((days * hpd).toFixed(2));
                    }
                    return this.date_ranges.reduce((sum, r) => {
                        const days = this.getRangeDays(r);
                        const hpd = parseFloat(r.hours_per_day) || 0;
                        return sum + (days * hpd);
                    }, 0);
                },
                checkDateFlags() {
                    if (!this.form.work_date) return;
                    const d = new Date(this.form.work_date);
                    const day = d.getDay();
                    this.form.is_weekend = (day === 0 || day === 6);
                },
                checkHoursFlags() {
                    const hpd = this.editWorkLog ? parseFloat(this.form.hours_per_day || 0) : parseFloat(this.form.hours_worked || 0);
                    this.form.is_overtime = hpd > 8;
                },
                get calculatedNominal() {
                    const rate = parseFloat(this.form.base_rate) || 0;
                    if (this.editWorkLog && (!this.date_ranges || this.date_ranges.length === 0)) {
                        const hours = parseFloat(this.form.hours_worked) || 0;
                        return Math.round(hours * rate * (this.form.is_weekend ? 1.10 : 1.0) * (this.form.is_overtime ? 1.20 : 1.0));
                    }

                    let total = 0;
                    this.date_ranges.forEach(r => {
                        const days = this.getRangeDays(r);
                        const hpd = parseFloat(r.hours_per_day) || 0;
                        if (days > 0 && hpd > 0) {
                            const weekendMult = r.is_weekend ? 1.10 : 1.0;
                            const overtimeMult = r.is_overtime ? 1.20 : 1.0;
                            total += Math.round(days * hpd * rate * weekendMult * overtimeMult);
                        }
                    });
                    return total;
                },
                formatRp(val) {
                    return new Intl.NumberFormat('id-ID').format(val || 0);
                },
                resetForm() {
                    this.editWorkLog = null;
                    this.date_ranges = [
                        { start_date: '{{ date('Y-m-d') }}', end_date: '{{ date('Y-m-d') }}', hours_per_day: 8, is_weekend: false, is_overtime: false }
                    ];
                    this.form = {
                        user_id: this.members.length > 0 ? this.members[0].user_id : '',
                        role_in_project: this.uniqueRoles.length > 0 ? this.uniqueRoles[0] : '',
                        skill_level: 'middle',
                        work_date: '{{ date('Y-m-d') }}',
                        end_date: '{{ date('Y-m-d') }}',
                        hours_per_day: 8.0,
                        days_count: 1,
                        hours_worked: 8.0,
                        base_rate: 0,
                        source_account_type: 'office_account',
                        is_weekend: false,
                        is_overtime: false,
                        payment_status: 'unpaid',
                        payment_date: '',
                        notes: '',
                    };
                    if (this.members.length > 0) {
                        this.onMemberChange();
                    } else {
                        this.onRoleOrSkillChange();
                    }
                    this.checkDateFlags();
                },
                openEdit(log) {
                    this.editWorkLog = log;
                    const workDate = log.work_date ? log.work_date.substring(0, 10) : '{{ date('Y-m-d') }}';
                    const endDate = log.end_date ? log.end_date.substring(0, 10) : workDate;
                    const days = parseInt(log.days_count) || this.calculateDays(workDate, endDate);
                    const hpd = parseFloat(log.hours_per_day) || (days > 0 ? (parseFloat(log.hours_worked) / days) : parseFloat(log.hours_worked)) || 8.0;
                    const totalHours = parseFloat(log.hours_worked) || (days * hpd);

                    this.form = {
                        user_id: log.user_id,
                        role_in_project: log.role_in_project || '',
                        skill_level: 'middle',
                        work_date: workDate,
                        end_date: endDate,
                        hours_per_day: hpd,
                        days_count: days,
                        hours_worked: totalHours,
                        base_rate: parseFloat(log.base_rate) || 0,
                        source_account_type: log.source_account_type,
                        is_weekend: !!log.is_weekend,
                        is_overtime: !!log.is_overtime,
                        payment_status: log.payment_status,
                        payment_date: log.payment_date ? log.payment_date.substring(0, 10) : '',
                        notes: log.notes || '',
                    };

                    let ranges = log.date_ranges_data;
                    if (typeof ranges === 'string') {
                        try { ranges = JSON.parse(ranges); } catch (e) { ranges = null; }
                    }
                    if (Array.isArray(ranges) && ranges.length > 0) {
                        this.date_ranges = JSON.parse(JSON.stringify(ranges)).map(r => ({
                            start_date: (r.start_date || '').substring(0, 10),
                            end_date: (r.end_date || r.start_date || '').substring(0, 10),
                            hours_per_day: parseFloat(r.hours_per_day) || 8.0,
                            is_weekend: !!r.is_weekend,
                            is_overtime: !!r.is_overtime
                        }));
                    } else {
                        this.date_ranges = [
                            {
                                start_date: workDate,
                                end_date: endDate,
                                hours_per_day: hpd,
                                is_weekend: !!log.is_weekend,
                                is_overtime: !!log.is_overtime
                            }
                        ];
                    }

                    this.checkDateFlags();
                    this.openAddWorkLog = true;
                }
            }">
                <div class="px-6 py-4 border-b border-indigo-600 flex flex-wrap justify-between items-center gap-2 bg-gradient-to-r from-indigo-700 via-indigo-600 to-blue-600 text-white">
                    <div>
                        <h3 class="text-lg font-bold text-white shadow-sm flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Log Kerja & Upah Tim
                        </h3>
                        <p class="text-xs text-indigo-100 mt-0.5">
                            Beban HPP Tenaga Kerja Langsung: <span class="font-bold text-white">Rp {{ number_format($project->totalLaborCost(false), 0, ',', '.') }}</span>
                            (Terbayar: <span class="font-bold text-emerald-200">Rp {{ number_format($project->totalLaborCost(true), 0, ',', '.') }}</span>)
                        </p>
                    </div>
                    @canany(['finance.work_logs.manage', 'projects.manage'])
                    <button type="button" @click="resetForm(); openAddWorkLog = true" class="text-xs font-bold text-indigo-50 hover:text-white bg-white/20 px-3 py-1.5 rounded-lg backdrop-blur-sm uppercase tracking-wider transition-colors cursor-pointer">
                        + Catat Log Kerja
                    </button>
                    @endcanany
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-xs">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left font-semibold text-gray-500 uppercase">Tanggal & Tim</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase">Posisi di Proyek</th>
                                <th class="px-4 py-3 text-center font-semibold text-gray-500 uppercase">Durasi</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-500 uppercase">Rate & Upah</th>
                                <th class="px-4 py-3 text-center font-semibold text-gray-500 uppercase">Status</th>
                                <th class="px-5 py-3 text-right font-semibold text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($project->workLogs as $log)
                            <tr class="hover:bg-blue-50/30 transition-colors">
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <div class="font-bold text-gray-900">
                                        @php
                                            $rangesData = is_array($log->date_ranges_data) ? $log->date_ranges_data : (is_string($log->date_ranges_data) ? json_decode($log->date_ranges_data, true) : null);
                                            $rangeCount = is_array($rangesData) ? count($rangesData) : 0;
                                            $daysCount = $log->days_count ?? ($log->end_date ? $log->work_date->diffInDays($log->end_date) + 1 : 1);
                                        @endphp

                                        @if($log->end_date && $log->end_date->ne($log->work_date))
                                            <div>{{ $log->work_date->format('d/m/Y') }} - {{ $log->end_date->format('d/m/Y') }}</div>
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-blue-50 text-blue-700 mt-0.5">
                                                @if($rangeCount > 1)
                                                    {{ $daysCount }} hari kerja ({{ $rangeCount }} periode kerja)
                                                @else
                                                    ({{ $daysCount }} hari kerja)
                                                @endif
                                            </span>
                                        @else
                                            <div>{{ $log->work_date ? $log->work_date->format('d/m/Y') : '-' }}</div>
                                            @if($daysCount > 1)
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-blue-50 text-blue-700 mt-0.5">
                                                    @if($rangeCount > 1)
                                                        {{ $daysCount }} hari kerja ({{ $rangeCount }} periode kerja)
                                                    @else
                                                        ({{ $daysCount }} hari kerja)
                                                    @endif
                                                </span>
                                            @endif
                                        @endif

                                        @if($rangeCount > 1)
                                            <div class="mt-1 space-y-0.5 text-[10px] text-gray-500 font-normal">
                                                @foreach($rangesData as $r)
                                                    @php
                                                        $rStart = !empty($r['start_date']) ? \Carbon\Carbon::parse($r['start_date'])->format('d/m') : '';
                                                        $rEnd = !empty($r['end_date']) ? \Carbon\Carbon::parse($r['end_date'])->format('d/m') : $rStart;
                                                        $rDays = $r['days_count'] ?? 1;
                                                        $rHours = $r['hours_worked'] ?? ($rDays * ($r['hours_per_day'] ?? 8));
                                                        $rTags = [];
                                                        if (!empty($r['is_weekend'])) $rTags[] = 'Weekend';
                                                        if (!empty($r['is_overtime'])) $rTags[] = 'Lembur';
                                                        $tagStr = !empty($rTags) ? ' - ' . implode(', ', $rTags) : '';
                                                    @endphp
                                                    <div>&bull; {{ $rStart }} - {{ $rEnd }} ({{ $rDays }} hari, {{ number_format($rHours, 0) }} jam{{ $tagStr }})</div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                    <div class="text-[11px] text-gray-600 font-medium mt-0.5">{{ $log->user->name ?? 'User #' . $log->user_id }}</div>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                        {{ $log->role_in_project ?: 'Staff' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap text-center">
                                    <span class="font-bold text-gray-800">{{ number_format($log->hours_worked, 1, ',', '.') }} jam</span>
                                    @if(($log->days_count ?? 1) > 1 || ($log->end_date && $log->end_date->ne($log->work_date)))
                                        <div class="text-[10px] text-gray-500 font-medium mt-0.5">
                                            ({{ number_format($log->hours_per_day ?? ($log->hours_worked / max(1, $log->days_count ?? 1)), 1, ',', '.') }} jam/hari &times; {{ $log->days_count ?? ($log->work_date->diffInDays($log->end_date) + 1) }} hari)
                                        </div>
                                    @endif
                                    <div class="flex items-center justify-center gap-1 mt-0.5">
                                        @if($log->is_weekend)
                                            <span class="px-1 py-0.2 rounded text-[9px] font-bold bg-amber-100 text-amber-800">Weekend</span>
                                        @endif
                                        @if($log->is_overtime)
                                            <span class="px-1 py-0.2 rounded text-[9px] font-bold bg-purple-100 text-purple-800">Lembur</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap text-right">
                                    <div class="text-[11px] text-gray-500 font-mono">Rp {{ number_format($log->base_rate, 0, ',', '.') }}/jam</div>
                                    <div class="font-bold text-gray-900 text-sm">Rp {{ number_format($log->calculated_nominal, 0, ',', '.') }}</div>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap text-center">
                                    @if($log->payment_status === 'paid')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            Paid
                                        </span>
                                    @elseif($log->payment_status === 'pending_approval')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-amber-100 text-amber-800 border border-amber-200">
                                            Pending
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-rose-100 text-rose-800 border border-rose-200">
                                            Unpaid
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap text-right space-x-2">
                                    @canany(['finance.work_logs.manage', 'projects.manage'])
                                    @if($log->payment_status !== 'paid')
                                    <form action="{{ route('finance.work-logs.update', $log) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="user_id" value="{{ $log->user_id }}">
                                        <input type="hidden" name="project_id" value="{{ $project->id }}">
                                        <input type="hidden" name="work_date" value="{{ $log->work_date ? $log->work_date->toDateString() : date('Y-m-d') }}">
                                        <input type="hidden" name="end_date" value="{{ $log->end_date ? $log->end_date->toDateString() : ($log->work_date ? $log->work_date->toDateString() : date('Y-m-d')) }}">
                                        <input type="hidden" name="hours_per_day" value="{{ $log->hours_per_day ?? ($log->hours_worked / max(1, $log->days_count ?? 1)) }}">
                                        <input type="hidden" name="days_count" value="{{ $log->days_count ?? 1 }}">
                                        <input type="hidden" name="hours_worked" value="{{ $log->hours_worked }}">
                                        <input type="hidden" name="role_in_project" value="{{ $log->role_in_project }}">
                                        <input type="hidden" name="source_account_type" value="{{ $log->source_account_type }}">
                                        <input type="hidden" name="base_rate" value="{{ $log->base_rate }}">
                                        <input type="hidden" name="is_weekend" value="{{ $log->is_weekend ? '1' : '0' }}">
                                        <input type="hidden" name="is_overtime" value="{{ $log->is_overtime ? '1' : '0' }}">
                                        <input type="hidden" name="payment_status" value="paid">
                                        <input type="hidden" name="payment_date" value="{{ date('Y-m-d') }}">
                                        <button type="submit" class="text-xs font-bold text-emerald-600 hover:text-emerald-800 transition cursor-pointer" title="Tandai Sudah Dibayar">
                                            Bayar
                                        </button>
                                    </form>
                                    <span class="text-gray-300">|</span>
                                    @endif
                                    <button type="button" @click="openEdit({{ json_encode($log) }})" class="text-xs font-bold text-amber-600 hover:text-amber-800 transition cursor-pointer">
                                        Edit
                                    </button>
                                    <span class="text-gray-300">|</span>
                                    <form action="{{ route('finance.work-logs.destroy', $log) }}" method="POST" class="inline" onsubmit="return confirm('Hapus log kerja ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-bold text-rose-600 hover:text-rose-800 transition cursor-pointer">
                                            Hapus
                                        </button>
                                    </form>
                                    @else
                                    <span class="text-xs text-gray-400 italic">Lihat</span>
                                    @endcanany
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-400 italic">
                                    Belum ada log kerja yang dicatat untuk proyek ini.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if($project->workLogs->count() > 0)
                        <tfoot class="bg-gray-50 border-t border-gray-200">
                            <tr>
                                <td colspan="2" class="px-5 py-3 font-bold text-gray-700 uppercase tracking-wider text-[11px]">
                                    TOTAL TENAGA KERJA ({{ $project->workLogs->count() }} LOG)
                                </td>
                                <td class="px-4 py-3 text-center font-bold text-gray-900">
                                    {{ number_format($project->workLogs->sum('hours_worked'), 1, ',', '.') }} jam
                                </td>
                                <td class="px-4 py-3 text-right font-black text-indigo-700 text-sm">
                                    Rp {{ number_format($project->totalLaborCost(false), 0, ',', '.') }}
                                </td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>

                <!-- Modal: Add / Edit Work Log in Project Detail -->
                @canany(['finance.work_logs.manage', 'projects.manage'])
                <template x-if="openAddWorkLog">
                    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="openAddWorkLog = false"></div>
                            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                                <form :action="editWorkLog ? ('/finance/work-logs/' + editWorkLog.id) : '{{ route('finance.work-logs.store') }}'" method="POST">
                                    @csrf
                                    <template x-if="editWorkLog">
                                        <input type="hidden" name="_method" value="PUT">
                                    </template>
                                    <input type="hidden" name="project_id" value="{{ $project->id }}">

                                    <div class="bg-white px-6 pt-6 pb-4 sm:p-6 sm:pb-4 space-y-4">
                                        <h3 class="text-lg font-black text-gray-900 uppercase tracking-wider" x-text="editWorkLog ? 'Edit Log Jam Kerja' : 'Catat Log Kerja & Upah Proyek'"></h3>
                                        <p class="text-xs text-gray-500">Pencatatan jam kerja otomatis menghitung upah sesuai tarif anggota pada proyek ini.</p>

                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-widest mb-1.5">Anggota Tim <span class="text-red-500">*</span></label>
                                            <select name="user_id" x-model="form.user_id" @change="onMemberChange()" required class="block w-full border-gray-300 rounded-xl shadow-sm focus:ring-primary focus:border-primary text-sm p-3 border">
                                                <option value="">-- Pilih Anggota Tim --</option>
                                                @foreach($project->members as $m)
                                                    <option value="{{ $m->user_id }}">{{ $m->user->name ?? 'User #' . $m->user_id }} ({{ $m->display_position }})</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                            <div>
                                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-widest mb-1.5">Posisi Fungsional <span class="text-red-500">*</span></label>
                                                <select name="role_in_project" x-model="form.role_in_project" @change="onRoleOrSkillChange()" required class="block w-full border-gray-300 rounded-xl shadow-sm focus:ring-primary focus:border-primary text-xs p-2.5 border font-medium">
                                                    <option value="">-- Pilih Posisi --</option>
                                                    @php
                                                        $roleOptions = $manHourRates->pluck('role_slug')->merge($project->workLogs->pluck('role_in_project'))->filter()->unique();
                                                    @endphp
                                                    @foreach($roleOptions as $slug)
                                                        @php
                                                            $roleLabel = match(strtolower($slug)) {
                                                                'developer' => 'Developer',
                                                                'pm' => 'PM (Project Manager)',
                                                                'qc' => 'QC (Quality Control)',
                                                                'designer' => 'UI/UX Designer',
                                                                'devops' => 'DevOps',
                                                                default => ucwords(str_replace(['_', '-'], ' ', $slug))
                                                            };
                                                        @endphp
                                                        <option value="{{ $slug }}">{{ $roleLabel }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-widest mb-1.5">Status Kompetensi <span class="text-red-500">*</span></label>
                                                <select name="skill_level" x-model="form.skill_level" @change="onRoleOrSkillChange()" class="block w-full border-gray-300 rounded-xl shadow-sm focus:ring-primary focus:border-primary text-xs p-2.5 border font-medium">
                                                    <option value="entry">Junior (Entry)</option>
                                                    <option value="middle">Middle</option>
                                                    <option value="senior">Senior</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-widest mb-1.5">Rate / Jam (Rp) <span class="text-red-500">*</span></label>
                                                <input type="number" step="1000" min="0" name="base_rate" x-model.number="form.base_rate" required class="block w-full border-gray-300 rounded-xl shadow-sm focus:ring-primary focus:border-primary text-xs p-2.5 border font-bold text-primary">
                                            </div>
                                        </div>

                                        <!-- Mode Edit Fallback: Single Date Range (if date_ranges empty) -->
                                        <template x-if="editWorkLog && (!date_ranges || date_ranges.length === 0)">
                                            <div class="space-y-3">
                                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                                    <div>
                                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-widest mb-1.5">Tanggal Mulai <span class="text-red-500">*</span></label>
                                                        <input type="date" name="work_date" x-model="form.work_date" @change="onEditRangeChange()" required class="block w-full border-gray-300 rounded-xl shadow-sm focus:ring-primary focus:border-primary text-xs p-2.5 border">
                                                    </div>
                                                    <div>
                                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-widest mb-1.5">Tanggal Selesai <span class="text-red-500">*</span></label>
                                                        <input type="date" name="end_date" x-model="form.end_date" :min="form.work_date" @change="onEditRangeChange()" required class="block w-full border-gray-300 rounded-xl shadow-sm focus:ring-primary focus:border-primary text-xs p-2.5 border">
                                                    </div>
                                                    <div>
                                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-widest mb-1.5">Jam per Hari <span class="text-red-500">*</span></label>
                                                        <input type="number" step="0.5" min="0.5" max="24" name="hours_per_day" x-model.number="form.hours_per_day" @input="onEditRangeChange()" required class="block w-full border-gray-300 rounded-xl shadow-sm focus:ring-primary focus:border-primary text-xs p-2.5 border font-bold text-center">
                                                    </div>
                                                </div>
                                                <input type="hidden" name="days_count" :value="form.days_count">
                                                <input type="hidden" name="hours_worked" :value="totalCalculatedHours">
                                                <div class="text-[11px] text-indigo-700 bg-indigo-50 px-3 py-1.5 rounded-lg border border-indigo-100 flex items-center justify-between">
                                                    <span>Rentang Kerja: <strong x-text="form.days_count + ' hari'"></strong></span>
                                                    <span>Total Durasi: <strong x-text="totalCalculatedHours.toFixed(1) + ' jam'"></strong></span>
                                                </div>
                                            </div>
                                        </template>

                                        <!-- Date Ranges Repeater (Create Mode & Edit Mode with date_ranges) -->
                                        <template x-if="!editWorkLog || (date_ranges && date_ranges.length > 0)">
                                            <div class="space-y-3">
                                                <div class="flex items-center justify-between">
                                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-widest">
                                                        Rentang Tanggal & Jam Kerja <span class="text-red-500">*</span>
                                                    </label>
                                                    <span class="text-[11px] text-gray-500">Bisa menambahkan lebih dari 1 rentang</span>
                                                </div>

                                                <div class="space-y-2.5 max-h-64 overflow-y-auto pr-1">
                                                    <template x-for="(range, idx) in date_ranges" :key="idx">
                                                        <div class="p-3 bg-gray-50 border border-gray-200 rounded-xl space-y-2">
                                                            <div class="flex items-center justify-between text-xs font-bold text-gray-700">
                                                                <span class="flex items-center gap-1.5 text-primary">
                                                                    <span class="w-2 h-2 rounded-full bg-primary inline-block"></span>
                                                                    <span x-text="'Rentang #' + (idx + 1)"></span>
                                                                </span>
                                                                <span class="text-indigo-600 font-semibold" x-text="getRangeDays(range) + ' hari (' + (getRangeDays(range) * (parseFloat(range.hours_per_day) || 0)).toFixed(1) + ' jam)'"></span>
                                                            </div>
                                                            <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 items-center">
                                                                <div class="sm:col-span-4">
                                                                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Tanggal Mulai <span class="text-red-500">*</span></label>
                                                                    <input type="date" :name="'date_ranges[' + idx + '][start_date]'" x-model="range.start_date" required class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-primary focus:border-primary text-xs p-2 border">
                                                                </div>
                                                                <div class="sm:col-span-4">
                                                                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Tanggal Selesai <span class="text-red-500">*</span></label>
                                                                    <input type="date" :name="'date_ranges[' + idx + '][end_date]'" :min="range.start_date" x-model="range.end_date" required class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-primary focus:border-primary text-xs p-2 border">
                                                                </div>
                                                                <div class="sm:col-span-3">
                                                                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Jam / Hari <span class="text-red-500">*</span></label>
                                                                    <input type="number" step="0.5" min="0.5" max="24" :name="'date_ranges[' + idx + '][hours_per_day]'" x-model.number="range.hours_per_day" required class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-primary focus:border-primary text-xs p-2 border font-bold text-center">
                                                                </div>
                                                                <div class="sm:col-span-1 flex items-end justify-center pt-2 sm:pt-4">
                                                                    <button type="button" x-show="date_ranges.length > 1" @click="removeDateRange(idx)" class="p-2 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition cursor-pointer" title="Hapus rentang ini">
                                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                            <div class="flex items-center gap-5 pt-1.5 border-t border-gray-200/60 mt-2">
                                                                <label class="flex items-center cursor-pointer">
                                                                    <input type="checkbox" :name="'date_ranges[' + idx + '][is_weekend]'" x-model="range.is_weekend" value="1" class="w-3.5 h-3.5 text-primary rounded border-gray-300">
                                                                    <span class="ml-1.5 text-[11px] font-semibold text-gray-700">Akhir Pekan (1.1x)</span>
                                                                </label>
                                                                <label class="flex items-center cursor-pointer">
                                                                    <input type="checkbox" :name="'date_ranges[' + idx + '][is_overtime]'" x-model="range.is_overtime" value="1" class="w-3.5 h-3.5 text-primary rounded border-gray-300">
                                                                    <span class="ml-1.5 text-[11px] font-semibold text-gray-700">Lembur (1.2x)</span>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </template>
                                                </div>

                                                <button type="button" @click="addDateRange()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-primary bg-blue-50 hover:bg-blue-100 rounded-lg border border-blue-200 transition cursor-pointer">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                                    + Tambah Rentang Tanggal
                                                </button>
                                            </div>
                                        </template>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-widest mb-1.5">Sumber Dana <span class="text-red-500">*</span></label>
                                                <select name="source_account_type" x-model="form.source_account_type" required class="block w-full border-gray-300 rounded-xl shadow-sm focus:ring-primary focus:border-primary text-sm p-3 border">
                                                    <option value="office_account">Kas Kantor</option>
                                                    <option value="personal_reimbursement">Reimburse Pribadi</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-widest mb-1.5">Status Pembayaran <span class="text-red-500">*</span></label>
                                                <select name="payment_status" x-model="form.payment_status" required class="block w-full border-gray-300 rounded-xl shadow-sm focus:ring-primary focus:border-primary text-sm p-3 border">
                                                    <option value="unpaid">Unpaid (Belum Bayar)</option>
                                                    <option value="pending_approval">Pending Approval</option>
                                                    <option value="paid">Paid (Terbayar)</option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Total Jam & Upah Preview -->
                                        <div class="p-4 bg-indigo-50 border border-indigo-200 rounded-xl space-y-2">
                                            <div class="flex items-center justify-between text-xs">
                                                <span class="font-bold uppercase text-indigo-700 tracking-wider">Estimasi Total Hari & Durasi</span>
                                                <span class="font-bold text-gray-800">
                                                    <span x-text="totalCalculatedDays"></span> Hari Kerja (<span x-text="totalCalculatedHours.toFixed(1)"></span> Jam)
                                                </span>
                                            </div>
                                            <div class="flex items-center justify-between border-t border-indigo-100 pt-2">
                                                <div>
                                                    <span class="text-[11px] font-bold uppercase text-indigo-700 tracking-wider">Estimasi Upah Tervalidasi</span>
                                                    <p class="text-[10px] text-gray-500">Total Jam &times; Tarif &times; Multiplier</p>
                                                </div>
                                                <div class="text-right">
                                                    <span class="text-xl font-black text-indigo-900">Rp <span x-text="formatRp(calculatedNominal)"></span></span>
                                                </div>
                                            </div>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-widest mb-1.5">Catatan / Aktivitas (Opsional)</label>
                                            <textarea name="notes" rows="2" x-model="form.notes" class="block w-full border-gray-300 rounded-xl shadow-sm focus:ring-primary focus:border-primary text-xs p-3 border" placeholder="Uraian pekerjaan teknis..."></textarea>
                                        </div>
                                    </div>

                                    <div class="bg-gray-50 px-6 py-4 flex flex-row-reverse gap-2">
                                        <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-blue-700 text-white rounded-xl font-bold text-xs uppercase tracking-wider shadow-md transition cursor-pointer">
                                            Simpan Log Upah
                                        </button>
                                        <button type="button" @click="openAddWorkLog = false" class="px-4 py-2.5 bg-white border border-gray-300 text-gray-700 hover:bg-gray-100 rounded-xl font-bold text-xs uppercase tracking-wider transition cursor-pointer">
                                            Batal
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </template>
                @endcanany
            </div>
            @endcanany
        </div>

        <!-- Sidebar Info -->
        <div class="space-y-8">
            <!-- Client Card -->
            <div class="bg-white rounded-lg shadow-sm border border-indigo-100 overflow-hidden">
                <div class="bg-gradient-to-r from-indigo-600 to-blue-500 px-6 py-4 border-b border-indigo-600 text-white">
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider shadow-sm">Client Information</h3>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <p class="text-xs text-gray-500">PIC Name</p>
                        <p class="font-bold text-gray-800">{{ $project->client->name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Company</p>
                        <p class="font-bold text-gray-800">{{ $project->client->company_name ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Contact</p>
                        <p class="text-sm text-gray-800">{{ $project->client->email }}</p>
                        <p class="text-sm text-gray-800">{{ $project->client->phone }}</p>
                    </div>
                    @can('clients.view')
                    <div class="pt-4 border-t border-gray-100">
                        <a href="{{ route('clients.show', $project->client) }}" class="text-xs font-bold text-primary uppercase hover:underline">View All Client Projects &rarr;</a>
                    </div>
                    @endcan
                </div>
            </div>

            <!-- Technical Access & Deployment Card -->
            <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden" x-data="{
                showPass: false,
                copiedField: null,
                copyToClipboard(text, fieldName) {
                    if (!text) return;
                    navigator.clipboard.writeText(text).then(() => {
                        this.copiedField = fieldName;
                        setTimeout(() => { this.copiedField = null; }, 2000);
                    });
                }
            }">
                <div class="bg-gradient-to-r from-slate-800 to-slate-700 px-6 py-4 border-b border-slate-800 text-white flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                        <h3 class="text-sm font-bold text-white uppercase tracking-wider shadow-sm">Technical Access &amp; Deployment</h3>
                    </div>
                    @if($project->is_credentials_visible_to_client)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-emerald-500/20 text-emerald-200 border border-emerald-400/30">
                            Visible to Client
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-slate-600 text-slate-300 border border-slate-500/40">
                            Internal Only
                        </span>
                    @endif
                </div>

                <div class="p-6 space-y-5">
                    <!-- Quick Action Deployment Buttons -->
                    @if($project->demo_url || $project->repository_url || $project->production_url)
                        <div class="space-y-2">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Deployment &amp; Code Links</span>
                            <div class="grid grid-cols-1 gap-2">
                                @if($project->demo_url)
                                    <a href="{{ $project->demo_url }}" target="_blank" rel="noopener noreferrer" class="flex items-center justify-between px-3 py-2 bg-amber-50 hover:bg-amber-100/80 text-amber-900 border border-amber-200 rounded-lg text-xs font-semibold transition group">
                                        <span class="flex items-center gap-2">
                                            <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                                            Buka Demo (Staging)
                                        </span>
                                        <svg class="w-3.5 h-3.5 text-amber-600 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    </a>
                                @endif

                                @if($project->repository_url)
                                    <a href="{{ $project->repository_url }}" target="_blank" rel="noopener noreferrer" class="flex items-center justify-between px-3 py-2 bg-gray-50 hover:bg-gray-100 text-gray-800 border border-gray-200 rounded-lg text-xs font-semibold transition group">
                                        <span class="flex items-center gap-2">
                                            <svg class="w-3.5 h-3.5 text-gray-700" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.53 1.032 1.53 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"/></svg>
                                            GitHub Repo
                                        </span>
                                        <svg class="w-3.5 h-3.5 text-gray-500 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    </a>
                                @endif

                                @if($project->production_url)
                                    <a href="{{ $project->production_url }}" target="_blank" rel="noopener noreferrer" class="flex items-center justify-between px-3 py-2 bg-emerald-50 hover:bg-emerald-100/80 text-emerald-900 border border-emerald-200 rounded-lg text-xs font-semibold transition group">
                                        <span class="flex items-center gap-2">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                            Live Site
                                        </span>
                                        <svg class="w-3.5 h-3.5 text-emerald-600 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Hosting Credentials Card -->
                    <div class="border-t border-gray-100 pt-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Kredensial Hosting</span>
                            @if(!empty($project->hosting_credentials['password']))
                                <button type="button" @click="showPass = !showPass" class="text-[11px] font-bold text-primary hover:text-blue-700 flex items-center gap-1 cursor-pointer">
                                    <span x-text="showPass ? 'Sembunyikan' : 'Lihat Sandi'"></span>
                                </button>
                            @endif
                        </div>

                        <div class="bg-gray-50 p-3.5 rounded-xl border border-gray-200/80 space-y-2.5 text-xs">
                            <!-- Provider -->
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500 text-[11px]">Provider:</span>
                                <span class="font-bold text-gray-800">{{ $project->hosting_provider ?: '-' }}</span>
                            </div>

                            <!-- Host / IP -->
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500 text-[11px]">Host / IP:</span>
                                <div class="flex items-center gap-1.5">
                                    <span class="font-mono text-gray-800 font-semibold">{{ $project->hosting_credentials['host'] ?? '-' }}</span>
                                    @if(!empty($project->hosting_credentials['host']))
                                        <button type="button" @click="copyToClipboard('{{ $project->hosting_credentials['host'] }}', 'host')" class="text-gray-400 hover:text-primary cursor-pointer p-0.5 rounded hover:bg-gray-200 transition" title="Salin Host">
                                            <svg x-show="copiedField !== 'host'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                            <span x-show="copiedField === 'host'" class="text-[10px] text-emerald-600 font-bold">✓</span>
                                        </button>
                                    @endif
                                </div>
                            </div>

                            <!-- Port -->
                            @if(!empty($project->hosting_credentials['port']))
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500 text-[11px]">Port:</span>
                                <span class="font-mono text-gray-800 font-semibold">{{ $project->hosting_credentials['port'] }}</span>
                            </div>
                            @endif

                            <!-- Username -->
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500 text-[11px]">Username:</span>
                                <div class="flex items-center gap-1.5">
                                    <span class="font-mono text-gray-800 font-semibold">{{ $project->hosting_credentials['username'] ?? '-' }}</span>
                                    @if(!empty($project->hosting_credentials['username']))
                                        <button type="button" @click="copyToClipboard('{{ $project->hosting_credentials['username'] }}', 'username')" class="text-gray-400 hover:text-primary cursor-pointer p-0.5 rounded hover:bg-gray-200 transition" title="Salin Username">
                                            <svg x-show="copiedField !== 'username'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                            <span x-show="copiedField === 'username'" class="text-[10px] text-emerald-600 font-bold">✓</span>
                                        </button>
                                    @endif
                                </div>
                            </div>

                            <!-- Password -->
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500 text-[11px]">Password:</span>
                                <div class="flex items-center gap-1.5">
                                    @if(!empty($project->hosting_credentials['password']))
                                        <span x-show="!showPass" class="font-mono text-gray-500 font-semibold tracking-wider">••••••••</span>
                                        <span x-show="showPass" class="font-mono text-gray-800 font-semibold">{{ $project->hosting_credentials['password'] }}</span>
                                        <button type="button" @click="copyToClipboard('{{ $project->hosting_credentials['password'] }}', 'password')" class="text-gray-400 hover:text-primary cursor-pointer p-0.5 rounded hover:bg-gray-200 transition" title="Salin Password">
                                            <svg x-show="copiedField !== 'password'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                            <span x-show="copiedField === 'password'" class="text-[10px] text-emerald-600 font-bold">✓</span>
                                        </button>
                                    @else
                                        <span class="text-gray-400 italic text-[11px]">-</span>
                                    @endif
                                </div>
                            </div>

                            <!-- Notes -->
                            @if(!empty($project->hosting_credentials['notes']))
                            <div class="pt-2 border-t border-gray-200/60">
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block mb-1">Catatan Akses</span>
                                <p class="text-gray-600 text-[11px] whitespace-pre-line leading-relaxed bg-white p-2 rounded border border-gray-200/60">{{ $project->hosting_credentials['notes'] }}</p>
                            </div>
                            @endif
                        </div>
                    </div>

                    @can('projects.edit')
                    <div class="pt-2 flex justify-end">
                        <a href="{{ route('projects.edit', $project) }}" class="text-xs font-bold text-primary hover:text-blue-700 hover:underline flex items-center gap-1">
                            <span>Edit Kredensial &amp; URL</span> &rarr;
                        </a>
                    </div>
                    @endcan
                </div>
            </div>

            <!-- Master Rate Card Datalist for Position & Rate Benchmarks -->
            <datalist id="project_master_roles_list">
                @foreach($manHourRates as $mr)
                    <option value="{{ $mr->role_slug }}">{{ $mr->role_slug }} ({{ $mr->skill_level }}) - Rp {{ number_format($mr->base_rate_per_hour, 0, ',', '.') }}/jam</option>
                @endforeach
            </datalist>

            <!-- Project Team Card (Project-Scoped Multi-Role & Functional Position/Rate) -->
            <div class="bg-white rounded-lg shadow-sm border border-blue-100 overflow-hidden" x-data="{
                openAddModal: false,
                openEditModal: false,
                editMember: null,
                editActionUrl: '',
                editRoles: [],
                assignableRoles: {{ json_encode($assignableRoles->map(fn($r) => ['slug' => $r->slug, 'name' => $r->name, 'badge_classes' => $r->badge_classes])) }},
                masterRates: {{ json_encode($manHourRates->map(fn($r) => [
                    'role_slug' => $r->role_slug,
                    'skill_level' => $r->skill_level,
                    'base_rate' => (float)$r->base_rate_per_hour,
                    'weekend_multiplier' => (float)$r->weekend_multiplier,
                    'overtime_multiplier' => (float)$r->overtime_multiplier,
                ])) }},
                addForm: {
                    user_id: '',
                    role_in_project: '',
                    rate_per_hour: 0,
                    weekend_multiplier: 1.10,
                    overtime_multiplier: 1.20,
                    roles: ['staff'],
                },
                editForm: {
                    id: '',
                    name: '',
                    role_in_project: '',
                    rate_per_hour: 0,
                    weekend_multiplier: 1.10,
                    overtime_multiplier: 1.20,
                    roles: [],
                },
                onAddRoleChange() {
                    const typed = this.addForm.role_in_project.trim().toLowerCase();
                    const match = this.masterRates.find(r => r.role_slug.toLowerCase() === typed || r.role_slug.toLowerCase().replace(/_/g, ' ') === typed);
                    if (match) {
                        this.addForm.rate_per_hour = match.base_rate;
                        this.addForm.weekend_multiplier = match.weekend_multiplier;
                        this.addForm.overtime_multiplier = match.overtime_multiplier;
                    }
                },
                onEditRoleChange() {
                    const typed = this.editForm.role_in_project.trim().toLowerCase();
                    const match = this.masterRates.find(r => r.role_slug.toLowerCase() === typed || r.role_slug.toLowerCase().replace(/_/g, ' ') === typed);
                    if (match && (!this.editForm.rate_per_hour || this.editForm.rate_per_hour == 0)) {
                        this.editForm.rate_per_hour = match.base_rate;
                        this.editForm.weekend_multiplier = match.weekend_multiplier;
                        this.editForm.overtime_multiplier = match.overtime_multiplier;
                    }
                },
                startEdit(data) {
                    this.editMember = data;
                    this.editActionUrl = '/projects/{{ $project->id }}/members/' + data.id;
                    this.editRoles = [...data.roles];
                    this.editForm = {
                        id: data.id,
                        name: data.name,
                        role_in_project: data.role_in_project || '',
                        rate_per_hour: data.rate_per_hour || 0,
                        weekend_multiplier: data.weekend_multiplier || 1.10,
                        overtime_multiplier: data.overtime_multiplier || 1.20,
                        roles: [...data.roles],
                    };
                    this.openEditModal = true;
                },
                toggleAddRole(slug) {
                    let idx = this.addForm.roles.indexOf(slug);
                    if (idx > -1) {
                        if (this.addForm.roles.length > 1) {
                            this.addForm.roles.splice(idx, 1);
                        }
                    } else {
                        this.addForm.roles.push(slug);
                    }
                },
                toggleEditRole(slug) {
                    let idx = this.editRoles.indexOf(slug);
                    if (idx > -1) {
                        if (this.editRoles.length > 1) {
                            this.editRoles.splice(idx, 1);
                        }
                    } else {
                        this.editRoles.push(slug);
                    }
                    this.editForm.roles = [...this.editRoles];
                }
            }">
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 border-b border-blue-600 text-white flex justify-between items-center">
                    <div>
                        <h3 class="text-sm font-bold text-white uppercase tracking-wider shadow-sm">Tim Proyek</h3>
                        <p class="text-[11px] text-blue-100 mt-0.5">{{ $project->members->count() }} Anggota Ditugaskan</p>
                    </div>
                    @can('projects.manage')
                    <button @click="openAddModal = true" class="text-xs font-bold text-blue-50 hover:text-white bg-white/20 px-3 py-1.5 rounded-lg backdrop-blur-sm transition-colors cursor-pointer">
                        + Tugaskan
                    </button>
                    @endcan
                </div>

                <div class="p-6 divide-y divide-gray-100 space-y-4">
                    @forelse($project->members as $member)
                    <div class="pt-4 first:pt-0 flex items-start justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <div class="h-9 w-9 rounded-full bg-gradient-to-tr from-indigo-600 to-blue-500 text-white flex items-center justify-center font-bold text-xs shadow-sm flex-shrink-0">
                                {{ strtoupper(substr($member->user->name ?? 'U', 0, 1)) }}
                            </div>
                            <div>
                                <p class="text-sm font-bold text-gray-800">{{ $member->user->name ?? 'User' }}</p>
                                <p class="text-xs text-gray-500 font-mono">{{ $member->user->email ?? '-' }}</p>
                                
                                <!-- Project Position & Agreed Rate -->
                                <div class="flex flex-wrap items-center gap-1.5 mt-1.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                        <svg class="w-3 h-3 mr-1 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                        {{ $member->display_position }}
                                    </span>

                                    @if($member->rate_per_hour > 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 font-mono">
                                        Rp {{ number_format($member->rate_per_hour, 0, ',', '.') }}/jam
                                    </span>
                                    @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-gray-50 text-gray-500 border border-gray-200">
                                        Rate Standar
                                    </span>
                                    @endif
                                </div>

                                <!-- RBAC system roles in this project -->
                                <div class="flex flex-wrap gap-1 mt-1.5">
                                    @forelse($member->role_badges as $badge)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-semibold uppercase border {{ $badge['classes'] }}">
                                            {{ $badge['name'] }}
                                        </span>
                                    @empty
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-semibold uppercase bg-gray-100 text-gray-600 border border-gray-200">
                                            Staf
                                        </span>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        @can('projects.manage')
                        <div class="flex items-center space-x-2 flex-shrink-0">
                            <button type="button" @click="startEdit({{ json_encode([
                                'id' => $member->id,
                                'name' => $member->user->name ?? 'User',
                                'role_in_project' => $member->role_in_project ?? '',
                                'rate_per_hour' => $member->rate_per_hour ?? 0,
                                'weekend_multiplier' => $member->weekend_multiplier ?? 1.10,
                                'overtime_multiplier' => $member->overtime_multiplier ?? 1.20,
                                'roles' => $member->roles->pluck('slug')->toArray()
                            ]) }})" class="text-xs font-bold text-indigo-600 hover:text-indigo-900 transition cursor-pointer" title="Ubah Posisi & Rate">
                                Edit
                            </button>
                            <span class="text-gray-300">|</span>
                            <form action="{{ route('projects.members.destroy', [$project, $member]) }}" method="POST" class="inline" onsubmit="return confirm('Hapus {{ $member->user->name }} dari tim proyek ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs font-bold text-rose-600 hover:text-rose-900 transition cursor-pointer" title="Hapus dari Tim">
                                    Hapus
                                </button>
                            </form>
                        </div>
                        @endcan
                    </div>
                    @empty
                    <div class="py-6 text-center text-gray-400">
                        <svg class="w-10 h-10 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        <p class="text-xs">Belum ada anggota tim yang ditugaskan.</p>
                        @can('projects.manage')
                        <button type="button" @click="openAddModal = true" class="mt-2 text-xs font-bold text-primary hover:underline cursor-pointer">+ Tugaskan Anggota Sekarang</button>
                        @endcan
                    </div>
                    @endforelse
                </div>

                <!-- Modal: Add Team Member & Set Rate -->
                @can('projects.manage')
                <template x-if="openAddModal">
                    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="openAddModal = false"></div>
                            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full">
                                <form action="{{ route('projects.members.store', $project) }}" method="POST">
                                    @csrf
                                    <div class="bg-white px-6 pt-6 pb-4 sm:p-6 sm:pb-4 space-y-4">
                                        <h3 class="text-lg font-black text-gray-900 uppercase tracking-wider">Tugaskan Anggota & Atur Tarif</h3>
                                        <p class="text-xs text-gray-500">Tentukan posisi fungsional, tarif jam kerja kesepakatan, dan hak akses RBAC proyek.</p>

                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-widest mb-1.5">Pilih Pengguna <span class="text-red-500">*</span></label>
                                            <select name="user_id" x-model="addForm.user_id" required class="block w-full border-gray-300 rounded-xl shadow-sm focus:ring-primary focus:border-primary text-sm p-3 border">
                                                <option value="">-- Pilih Staf --</option>
                                                @foreach($availableUsers as $u)
                                                    @if(!$project->hasMember($u))
                                                        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                                                    @endif
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-widest mb-1.5">Posisi Proyek</label>
                                                <input type="text" name="role_in_project" x-model="addForm.role_in_project" @input="onAddRoleChange()" list="project_master_roles_list" class="block w-full border-gray-300 rounded-xl shadow-sm focus:ring-primary focus:border-primary text-sm p-3 border font-medium" placeholder="Programmer, System Analyst...">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-widest mb-1.5">Tarif / Jam (Rp)</label>
                                                <input type="number" step="1000" min="0" name="rate_per_hour" x-model.number="addForm.rate_per_hour" class="block w-full border-gray-300 rounded-xl shadow-sm focus:ring-primary focus:border-primary text-sm p-3 border font-bold text-primary" placeholder="85000">
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-widest mb-1.5">Multiplier Akhir Pekan</label>
                                                <input type="number" step="0.05" min="1.0" max="3.0" name="weekend_multiplier" x-model.number="addForm.weekend_multiplier" class="block w-full border-gray-300 rounded-xl shadow-sm focus:ring-primary focus:border-primary text-xs p-2.5 border">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-widest mb-1.5">Multiplier Lembur</label>
                                                <input type="number" step="0.05" min="1.0" max="3.0" name="overtime_multiplier" x-model.number="addForm.overtime_multiplier" class="block w-full border-gray-300 rounded-xl shadow-sm focus:ring-primary focus:border-primary text-xs p-2.5 border">
                                            </div>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-widest mb-2">Hak Akses Sistem Proyek (Dual-Layer RBAC)</label>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-52 overflow-y-auto p-1">
                                                @foreach($assignableRoles as $role)
                                                <label class="flex items-center p-2.5 rounded-xl border border-gray-200 hover:bg-gray-50 cursor-pointer transition">
                                                    <input type="checkbox" name="roles[]" value="{{ $role->slug }}" {{ $role->slug === 'staff' ? 'checked' : '' }} class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                                                    <span class="ml-2 text-xs font-bold text-gray-800">{{ $role->name }}</span>
                                                    <span class="ml-auto text-[9px] font-bold uppercase px-1.5 py-0.5 rounded border {{ $role->badge_classes }}">{{ $role->slug }}</span>
                                                </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>

                                    <div class="bg-gray-50 px-6 py-4 flex flex-row-reverse gap-2">
                                        <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-blue-700 text-white rounded-xl font-bold text-xs uppercase tracking-wider shadow-md transition cursor-pointer">
                                            Simpan Penugasan & Tarif
                                        </button>
                                        <button type="button" @click="openAddModal = false" class="px-4 py-2.5 bg-white border border-gray-300 text-gray-700 hover:bg-gray-100 rounded-xl font-bold text-xs uppercase tracking-wider transition cursor-pointer">
                                            Batal
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Modal: Edit Team Member Position, Rate & Roles -->
                <template x-if="openEditModal">
                    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="openEditModal = false"></div>
                            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full">
                                <form :action="editActionUrl" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="bg-white px-6 pt-6 pb-4 sm:p-6 sm:pb-4 space-y-4">
                                        <h3 class="text-lg font-black text-gray-900 uppercase tracking-wider">Ubah Posisi, Tarif & Peran</h3>
                                        <p class="text-xs text-gray-500">Sesuaikan posisi fungsional, tarif jam kerja, dan peran sistem untuk <strong class="text-gray-800" x-text="editForm.name"></strong> pada proyek ini.</p>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-widest mb-1.5">Posisi Proyek</label>
                                                <input type="text" name="role_in_project" x-model="editForm.role_in_project" @input="onEditRoleChange()" list="project_master_roles_list" class="block w-full border-gray-300 rounded-xl shadow-sm focus:ring-primary focus:border-primary text-sm p-3 border font-medium" placeholder="Programmer, SA...">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-widest mb-1.5">Tarif / Jam (Rp)</label>
                                                <input type="number" step="1000" min="0" name="rate_per_hour" x-model.number="editForm.rate_per_hour" class="block w-full border-gray-300 rounded-xl shadow-sm focus:ring-primary focus:border-primary text-sm p-3 border font-bold text-primary">
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-widest mb-1.5">Multiplier Akhir Pekan</label>
                                                <input type="number" step="0.05" min="1.0" max="3.0" name="weekend_multiplier" x-model.number="editForm.weekend_multiplier" class="block w-full border-gray-300 rounded-xl shadow-sm focus:ring-primary focus:border-primary text-xs p-2.5 border">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-widest mb-1.5">Multiplier Lembur</label>
                                                <input type="number" step="0.05" min="1.0" max="3.0" name="overtime_multiplier" x-model.number="editForm.overtime_multiplier" class="block w-full border-gray-300 rounded-xl shadow-sm focus:ring-primary focus:border-primary text-xs p-2.5 border">
                                            </div>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-widest mb-2">Hak Akses Sistem Proyek</label>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-52 overflow-y-auto p-1">
                                                <template x-for="role in assignableRoles" :key="role.slug">
                                                    <label class="flex items-center p-2.5 rounded-xl border border-gray-200 hover:bg-gray-50 cursor-pointer transition"
                                                           :class="editRoles.includes(role.slug) ? 'border-primary bg-blue-50/40 ring-1 ring-primary' : ''">
                                                        <input type="checkbox" name="roles[]" :value="role.slug"
                                                               :checked="editRoles.includes(role.slug)"
                                                               @change="toggleEditRole(role.slug)"
                                                               class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                                                        <span class="ml-2 text-xs font-bold text-gray-800" x-text="role.name"></span>
                                                        <span class="ml-auto text-[9px] font-bold uppercase px-1.5 py-0.5 rounded border border-gray-200" x-text="role.slug"></span>
                                                    </label>
                                                </template>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="bg-gray-50 px-6 py-4 flex flex-row-reverse gap-2">
                                        <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-blue-700 text-white rounded-xl font-bold text-xs uppercase tracking-wider shadow-md transition cursor-pointer">
                                            Perbarui Posisi & Tarif
                                        </button>
                                        <button type="button" @click="openEditModal = false" class="px-4 py-2.5 bg-white border border-gray-300 text-gray-700 hover:bg-gray-100 rounded-xl font-bold text-xs uppercase tracking-wider transition cursor-pointer">
                                            Batal
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </template>
                @endcan
            </div>

            <!-- Timeline/Notes -->
            <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
                <div class="bg-gradient-to-r from-slate-600 to-gray-500 px-6 py-4 border-b border-slate-600 text-white">
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider shadow-sm">Operational Notes</h3>
                </div>
                <div class="p-6 space-y-4">
                    <div class="text-sm text-gray-600 italic">
                        "Ensure all deployments undergo engineer approval before changing status to Completed."
                    </div>
                    <div class="flex items-center text-xs text-gray-400">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Last updated: {{ $project->updated_at->diffForHumans() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection