@extends('layouts.app')

@section('title', 'Master Rate Card & Project Member Rates - Wirodayan Digital')

@section('content')
<div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto" x-data="{ tab: 'master', showMasterModal: false, showMemberModal: false, editingMaster: false, editingMember: false, masterForm: {}, memberForm: {} }">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between pb-6 border-b border-gray-200 mb-6 gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('finance.work-logs.index') }}" class="text-sm font-semibold text-primary hover:underline flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Kembali ke Log Kerja
                </a>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mt-2 flex items-center gap-2">
                <svg class="w-7 h-7 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
                Rate Card Master & Custom Member
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                Konfigurasi standar biaya man-hour per peran/keahlian dan penyesuaian khusus per anggota proyek.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <button x-show="tab === 'master'" @click="editingMaster = false; masterForm = { role_slug: 'developer', skill_level: 'middle', base_rate_per_hour: 85000, weekend_multiplier: 1.10, overtime_multiplier: 1.20, description: '' }; showMasterModal = true;" type="button" class="inline-flex items-center px-4 py-2 bg-primary text-white font-medium rounded-lg text-sm hover:bg-blue-800 shadow-sm transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Tambah Master Rate
            </button>
            <button x-show="tab === 'member'" @click="editingMember = false; memberForm = { project_id: '{{ $projects->first()?->id ?? '' }}', user_id: '{{ $users->first()?->id ?? '' }}', role_slug: '', rate_per_hour: 100000, weekend_multiplier: 1.10, overtime_multiplier: 1.20, notes: '' }; showMemberModal = true;" type="button" class="inline-flex items-center px-4 py-2 bg-primary text-white font-medium rounded-lg text-sm hover:bg-blue-800 shadow-sm transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Tambah Rate Khusus Member
            </button>
        </div>
    </div>

    <!-- Alert Success -->
    @if(session('success'))
    <div class="mb-6 p-4 rounded-lg bg-green-50 border border-green-200 text-green-800 flex items-center justify-between">
        <div class="flex items-center">
            <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="text-sm font-medium">{{ session('success') }}</span>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-green-600 hover:text-green-800">&times;</button>
    </div>
    @endif

    <!-- Tabs Navigation -->
    <div class="border-b border-gray-200 mb-6">
        <nav class="-mb-px flex space-x-6">
            <button @click="tab = 'master'" :class="tab === 'master' ? 'border-primary text-primary font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 font-medium'" class="py-3 px-1 border-b-2 text-sm transition-colors">
                1. Master Rate Card (Role & Skill Level)
            </button>
            <button @click="tab = 'member'" :class="tab === 'member' ? 'border-primary text-primary font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 font-medium'" class="py-3 px-1 border-b-2 text-sm transition-colors">
                2. Project Member Override (Kustom Per Proyek)
            </button>
        </nav>
    </div>

    <!-- TAB 1: Master Rate Card -->
    <div x-show="tab === 'master'" class="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-gray-600 uppercase text-[10px] font-semibold tracking-wider">
                        <th class="py-3 px-4">Role / Posisi</th>
                        <th class="py-3 px-4">Level Keahlian</th>
                        <th class="py-3 px-4 text-right">Base Rate / Jam</th>
                        <th class="py-3 px-4 text-center">Weekend Multiplier</th>
                        <th class="py-3 px-4 text-center">Overtime Multiplier</th>
                        <th class="py-3 px-4">Deskripsi</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($masterRates as $rate)
                    <tr class="hover:bg-blue-50/40 transition-colors">
                        <td class="py-3 px-4 font-bold text-gray-900">
                            {{ strtoupper($rate->role_slug) }}
                        </td>
                        <td class="py-3 px-4">
                            @if($rate->skill_level === 'senior')
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-100 text-purple-800">SENIOR / LEAD</span>
                            @elseif($rate->skill_level === 'middle')
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800">MIDDLE</span>
                            @else
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 text-gray-700">ENTRY LEVEL</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-right font-black text-gray-900 text-sm">
                            Rp {{ number_format($rate->base_rate_per_hour, 0, ',', '.') }}
                        </td>
                        <td class="py-3 px-4 text-center">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                {{ $rate->weekend_multiplier }}x
                            </span>
                        </td>
                        <td class="py-3 px-4 text-center">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                {{ $rate->overtime_multiplier }}x
                            </span>
                        </td>
                        <td class="py-3 px-4 text-gray-500 max-w-xs truncate">
                            {{ $rate->description ?: '-' }}
                        </td>
                        <td class="py-3 px-4 text-center">
                            <div class="flex items-center justify-center gap-1">
                                <button type="button" @click="editingMaster = true; masterForm = {{ json_encode($rate) }}; showMasterModal = true;" class="p-1 text-gray-500 hover:text-primary rounded hover:bg-gray-100">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <form method="POST" action="{{ route('finance.rates.master.destroy', $rate) }}" onsubmit="return confirm('Hapus master rate ini?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1 text-gray-500 hover:text-red-600 rounded hover:bg-gray-100">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-gray-500">Belum ada master rate card.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 2: Scoped Project Member Rate Card -->
    <div x-show="tab === 'member'" x-cloak class="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-gray-600 uppercase text-[10px] font-semibold tracking-wider">
                        <th class="py-3 px-4">Proyek</th>
                        <th class="py-3 px-4">Anggota Tim</th>
                        <th class="py-3 px-4 text-right">Rate Khusus / Jam</th>
                        <th class="py-3 px-4 text-center">Weekend Multiplier</th>
                        <th class="py-3 px-4 text-center">Overtime Multiplier</th>
                        <th class="py-3 px-4">Catatan</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($memberRates as $mRate)
                    <tr class="hover:bg-blue-50/40 transition-colors">
                        <td class="py-3 px-4 font-bold text-gray-900">
                            {{ $mRate->project?->title ?? 'Proyek #' . $mRate->project_id }}
                        </td>
                        <td class="py-3 px-4">
                            <div class="font-semibold text-gray-800">{{ $mRate->user?->name ?? 'User #' . $mRate->user_id }}</div>
                            <div class="text-[10px] text-gray-500">{{ $mRate->role_slug ?: 'Member Rate' }}</div>
                        </td>
                        <td class="py-3 px-4 text-right font-black text-gray-900 text-sm">
                            Rp {{ number_format($mRate->rate_per_hour, 0, ',', '.') }}
                        </td>
                        <td class="py-3 px-4 text-center">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                {{ $mRate->weekend_multiplier }}x
                            </span>
                        </td>
                        <td class="py-3 px-4 text-center">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                {{ $mRate->overtime_multiplier }}x
                            </span>
                        </td>
                        <td class="py-3 px-4 text-gray-500 max-w-xs truncate">
                            {{ $mRate->notes ?: '-' }}
                        </td>
                        <td class="py-3 px-4 text-center">
                            <div class="flex items-center justify-center gap-1">
                                <button type="button" @click="editingMember = true; memberForm = {{ json_encode($mRate) }}; showMemberModal = true;" class="p-1 text-gray-500 hover:text-primary rounded hover:bg-gray-100">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <form method="POST" action="{{ route('finance.rates.members.destroy', $mRate) }}" onsubmit="return confirm('Hapus rate override member ini?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1 text-gray-500 hover:text-red-600 rounded hover:bg-gray-100">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-gray-500">Belum ada rate khusus per anggota proyek. Sistem menggunakan Master Rate Card.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Master Rate -->
    <div x-show="showMasterModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showMasterModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                <form :action="editingMaster ? `/finance/rates/master/${masterForm.id}` : '{{ route('finance.rates.master.store') }}'" method="POST">
                    @csrf
                    <template x-if="editingMaster">
                        <input type="hidden" name="_method" value="PUT">
                    </template>
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                        <h3 class="text-base font-bold text-gray-900" x-text="editingMaster ? 'Edit Master Rate' : 'Tambah Master Rate Card'"></h3>
                        <button type="button" @click="showMasterModal = false" class="text-gray-400 hover:text-gray-600">&times;</button>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Posisi / Role <span class="text-red-500">*</span></label>
                                <input type="text" list="role_slug_list" name="role_slug" x-model="masterForm.role_slug" required placeholder="e.g. Programmer, Analyst" class="w-full text-xs rounded-lg border-gray-300">
                                <datalist id="role_slug_list">
                                    <option value="Programmer"></option>
                                    <option value="Developer"></option>
                                    <option value="System Analyst"></option>
                                    <option value="Project Manager"></option>
                                    <option value="Quality Control"></option>
                                    <option value="UI/UX Designer"></option>
                                    <option value="DevOps Engineer"></option>
                                    <option value="Technical Writer"></option>
                                </datalist>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Level Keahlian <span class="text-red-500">*</span></label>
                                <input type="text" list="skill_level_list" name="skill_level" x-model="masterForm.skill_level" required placeholder="e.g. Junior, Mid, Senior, Lead" class="w-full text-xs rounded-lg border-gray-300">
                                <datalist id="skill_level_list">
                                    <option value="Junior"></option>
                                    <option value="Mid"></option>
                                    <option value="Senior"></option>
                                    <option value="Lead"></option>
                                    <option value="Specialist"></option>
                                    <option value="Principal"></option>
                                </datalist>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Base Rate / Jam (Rp) <span class="text-red-500">*</span></label>
                            <input type="number" step="1000" min="0" name="base_rate_per_hour" x-model="masterForm.base_rate_per_hour" required class="w-full text-xs rounded-lg border-gray-300">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Weekend Mult.</label>
                                <input type="number" step="0.05" min="1.0" name="weekend_multiplier" x-model="masterForm.weekend_multiplier" required class="w-full text-xs rounded-lg border-gray-300">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Overtime Mult.</label>
                                <input type="number" step="0.05" min="1.0" name="overtime_multiplier" x-model="masterForm.overtime_multiplier" required class="w-full text-xs rounded-lg border-gray-300">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Deskripsi / Kualifikasi</label>
                            <input type="text" name="description" x-model="masterForm.description" placeholder="Catatan kualifikasi benchmark..." class="w-full text-xs rounded-lg border-gray-300">
                        </div>
                    </div>
                    <div class="bg-gray-50 px-6 py-3 border-t border-gray-200 flex justify-end gap-2">
                        <button type="button" @click="showMasterModal = false" class="px-4 py-2 border border-gray-300 rounded-lg text-xs font-medium text-gray-700 hover:bg-white">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-semibold hover:bg-blue-800">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Member Rate Override -->
    <div x-show="showMemberModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showMemberModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                <form :action="editingMember ? `/finance/rates/members/${memberForm.id}` : '{{ route('finance.rates.members.store') }}'" method="POST">
                    @csrf
                    <template x-if="editingMember">
                        <input type="hidden" name="_method" value="PUT">
                    </template>
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                        <h3 class="text-base font-bold text-gray-900" x-text="editingMember ? 'Edit Rate Khusus Member' : 'Tambah Rate Override Anggota Proyek'"></h3>
                        <button type="button" @click="showMemberModal = false" class="text-gray-400 hover:text-gray-600">&times;</button>
                    </div>
                    <div class="p-6 space-y-4">
                        <template x-if="!editingMember">
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Proyek <span class="text-red-500">*</span></label>
                                    <select name="project_id" x-model="memberForm.project_id" required class="w-full text-xs rounded-lg border-gray-300">
                                        @foreach($projects as $p)
                                        <option value="{{ $p->id }}">{{ $p->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Anggota Tim <span class="text-red-500">*</span></label>
                                    <select name="user_id" x-model="memberForm.user_id" required class="w-full text-xs rounded-lg border-gray-300">
                                        @foreach($users as $u)
                                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </template>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Rate Khusus / Jam (Rp) <span class="text-red-500">*</span></label>
                            <input type="number" step="1000" min="0" name="rate_per_hour" x-model="memberForm.rate_per_hour" required class="w-full text-xs rounded-lg border-gray-300">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Weekend Mult.</label>
                                <input type="number" step="0.05" min="1.0" max="5.0" name="weekend_multiplier" x-model="memberForm.weekend_multiplier" class="w-full text-xs rounded-lg border-gray-300">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Overtime Mult.</label>
                                <input type="number" step="0.05" min="1.0" max="5.0" name="overtime_multiplier" x-model="memberForm.overtime_multiplier" class="w-full text-xs rounded-lg border-gray-300">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Catatan / Keterangan</label>
                            <input type="text" name="notes" x-model="memberForm.notes" placeholder="e.g. Negosiasi khusus Lead Architect..." class="w-full text-xs rounded-lg border-gray-300">
                        </div>
                    </div>
                    <div class="bg-gray-50 px-6 py-3 border-t border-gray-200 flex justify-end gap-2">
                        <button type="button" @click="showMemberModal = false" class="px-4 py-2 border border-gray-300 rounded-lg text-xs font-medium text-gray-700 hover:bg-white">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-semibold hover:bg-blue-800">Simpan Override</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
