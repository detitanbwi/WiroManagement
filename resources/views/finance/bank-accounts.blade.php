@extends('layouts.app')

@section('title', 'Bank Accounts - Wiro App')

@section('content')
<div class="space-y-6" x-data="{ 
    showModal: false, 
    isEdit: false, 
    currentAccount: { id: '', name: '', type: 'personal', balance: 0 },
    openCreate() {
        this.isEdit = false;
        this.currentAccount = { id: '', name: '', type: 'personal', balance: 0 };
        this.showModal = true;
    },
    openEdit(account) {
        this.isEdit = true;
        this.currentAccount = { ...account };
        this.showModal = true;
    }
}">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Bank Accounts</h1>
            <p class="text-sm text-gray-500">Kelola rekening dan dompet untuk pencatatan pengeluaran.</p>
        </div>
        <button @click="openCreate()" class="flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-opacity-90 transition-all font-medium shadow-sm">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Tambah Akun
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        @foreach($accounts as $account)
        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-all relative group">
            <div class="absolute top-4 right-4 flex space-x-1 opacity-0 group-hover:opacity-100 transition-opacity">
                <button @click="openEdit({{ json_encode($account) }})" class="p-1 text-blue-600 hover:bg-blue-50 rounded">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-5M16.5 3.5a2.121 2.121 0 113 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                </button>
                <form action="{{ route('finance.bank-accounts.destroy', $account->id) }}" method="POST" onsubmit="return confirm('Hapus akun ini?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="p-1 text-red-600 hover:bg-red-50 rounded">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </button>
                </form>
            </div>
            <div class="flex items-start justify-between">
                <div>
                    <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-full {{ $account->type == 'personal' ? 'bg-orange-100 text-orange-600' : 'bg-teal-100 text-teal-600' }}">
                        {{ $account->type }}
                    </span>
                    <h3 class="text-lg font-bold text-gray-800 mt-2">{{ $account->name }}</h3>
                </div>
                <div class="p-2 bg-gray-50 rounded-lg">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <p class="text-xs text-gray-400">Current Balance</p>
                <p class="text-xl font-bold text-primary">Rp {{ number_format($account->balance, 0, ',', '.') }}</p>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Modal Form -->
    <div x-show="showModal" 
         class="fixed inset-0 z-[100] overflow-y-auto" 
         style="display: none;"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="showModal = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white rounded-2xl shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form :action="isEdit ? `/finance/bank-accounts/${currentAccount.id}` : '{{ route('finance.bank-accounts.store') }}'" method="POST">
                    @csrf
                    <template x-if="isEdit">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div class="px-6 py-6 bg-white">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-bold text-gray-900" x-text="isEdit ? 'Edit Akun Bank' : 'Tambah Akun Bank'"></h3>
                            <button type="button" @click="showModal = false" class="text-gray-400 hover:text-gray-500">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Nama Akun / Bank</label>
                                <input type="text" name="name" x-model="currentAccount.name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary" placeholder="Contoh: BCA Personal, Cash Company">
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Tipe</label>
                                <select name="type" x-model="currentAccount.type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                    <option value="personal">Personal</option>
                                    <option value="company">Company</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Saldo Awal</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 font-bold">Rp</span>
                                    <input type="number" name="balance" x-model="currentAccount.balance" required class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary" placeholder="0">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 flex flex-row-reverse space-x-2 space-x-reverse">
                        <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg font-bold hover:bg-opacity-90 shadow-sm" x-text="isEdit ? 'Simpan Perubahan' : 'Tambah Akun'"></button>
                        <button type="button" @click="showModal = false" class="px-6 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg font-bold hover:bg-gray-50">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
