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
            @canany(['projects.qc', 'qc.view'])
            <a href="{{ route('projects.qc', $project) }}" class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-md font-semibold text-[10px] md:text-xs uppercase tracking-widest shadow-sm transition">
                QA / QC Board
            </a>
            @endcanany

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
            <p class="text-[10px] text-amber-100 font-bold uppercase tracking-wider">Expenses</p>
            <p class="text-xl font-bold text-white mt-1 uppercase">Rp {{ number_format($project->total_expenses, 0, ',', '.') }}</p>
            <p class="text-[10px] text-amber-100 mt-2 italic">Total operational costs</p>
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