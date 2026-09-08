@extends('layouts.app')

@section('title', 'Tambah Peran Baru')

@section('content')
<div class="max-w-5xl mx-auto" x-data="{
    toggleGroup(groupClass, checked) {
        document.querySelectorAll('.' + groupClass).forEach(el => el.checked = checked);
    },
    toggleAll(checked) {
        document.querySelectorAll('.perm-checkbox').forEach(el => el.checked = checked);
    }
}">
    <div class="mb-6">
        <a href="{{ route('roles.index') }}" class="text-xs font-bold text-gray-400 hover:text-primary uppercase tracking-widest flex items-center mb-2">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            Kembali ke Daftar Peran
        </a>
        <h1 class="text-2xl font-black text-gray-800 tracking-tight">Tambah Peran Baru & Atur Hak Akses</h1>
        <p class="text-gray-500 text-sm mt-0.5">Definisikan nama peran baru dan pilih hak akses (*permissions*) yang diizinkan untuk peran ini.</p>
    </div>

    <form action="{{ route('roles.store') }}" method="POST" class="space-y-8">
        @csrf

        <!-- Informasi Dasar Peran -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 sm:p-8">
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Informasi Identitas Peran</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-2">Nama Peran <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required placeholder="Contoh: Technical Lead, Auditor, Support"
                        class="block w-full border-gray-200 rounded-xl focus:ring-primary focus:border-primary p-3 border text-sm">
                    @error('name') <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-2">Slug Peran <span class="text-gray-400 font-normal">(Opsional, otomatis dibuat jika kosong)</span></label>
                    <input type="text" name="slug" value="{{ old('slug') }}" placeholder="contoh: tech-lead"
                        class="block w-full border-gray-200 rounded-xl focus:ring-primary focus:border-primary p-3 border text-sm font-mono">
                    @error('slug') <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-gray-700 mb-2">Deskripsi Tanggung Jawab Peran</label>
                    <input type="text" name="description" value="{{ old('description') }}" placeholder="Jelaskan ruang lingkup wewenang peran ini secara singkat"
                        class="block w-full border-gray-200 rounded-xl focus:ring-primary focus:border-primary p-3 border text-sm">
                    @error('description') <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p> @enderror
                </div>
            </div>

            <!-- Pilihan Warna Badge -->
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-2.5">Tema Warna Badge Peran</label>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    @foreach($colors as $colorKey => $colorData)
                    <label class="flex items-center p-3 rounded-xl border border-gray-200 hover:border-blue-300 cursor-pointer transition has-checked:border-primary has-checked:bg-blue-50/50 has-checked:ring-1 has-checked:ring-primary">
                        <input type="radio" name="color" value="{{ $colorKey }}" {{ old('color', 'blue') === $colorKey ? 'checked' : '' }} class="w-4 h-4 text-primary border-gray-300 focus:ring-primary">
                        <div class="ml-2.5">
                            <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase border {{ $colorData['class'] }}">
                                {{ ucfirst($colorKey) }}
                            </span>
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Matriks Hak Akses (Permission Matrix) -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 sm:p-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6 pb-4 border-b border-gray-100">
                <div>
                    <h3 class="text-base font-black text-gray-900 tracking-tight">Matriks Hak Akses (*Permission Matrix*)</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Centang modul dan fitur yang boleh diakses atau dijalankan oleh peran ini.</p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="toggleAll(true)" class="px-3 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-bold transition">
                        Pilih Semua
                    </button>
                    <button type="button" @click="toggleAll(false)" class="px-3 py-1.5 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600 text-xs font-bold transition">
                        Kosongkan
                    </button>
                </div>
            </div>

            <div class="space-y-6">
                @foreach($permissionGroups as $groupName => $groupPermissions)
                @php $groupSlug = Str::slug($groupName); @endphp
                <div class="border border-gray-200 rounded-2xl p-5 bg-gray-50/40 hover:bg-gray-50 transition">
                    <!-- Group Header -->
                    <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-200">
                        <div class="flex items-center gap-2.5">
                            <div class="w-2.5 h-2.5 rounded-full bg-primary"></div>
                            <h4 class="text-sm font-black text-gray-900 uppercase tracking-wider">{{ $groupName }}</h4>
                            <span class="text-xs text-gray-400 font-semibold">({{ $groupPermissions->count() }} Izin)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="toggleGroup('group-{{ $groupSlug }}', true)" class="text-[11px] font-bold text-primary hover:underline">
                                Centang Grup
                            </button>
                            <span class="text-gray-300">&bull;</span>
                            <button type="button" @click="toggleGroup('group-{{ $groupSlug }}', false)" class="text-[11px] font-bold text-gray-500 hover:underline">
                                Batal
                            </button>
                        </div>
                    </div>

                    <!-- Permission Items -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        @foreach($groupPermissions as $permission)
                        <label class="flex items-start p-3 rounded-xl border border-gray-200 bg-white hover:border-blue-300 cursor-pointer transition has-checked:border-primary has-checked:bg-blue-50/40">
                            <div class="flex items-center h-5">
                                <input type="checkbox" name="permissions[]" value="{{ $permission->name }}"
                                    {{ is_array(old('permissions')) && in_array($permission->name, old('permissions')) ? 'checked' : '' }}
                                    class="perm-checkbox group-{{ $groupSlug }} w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                            </div>
                            <div class="ml-3 flex-1">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-gray-900">{{ $permission->label }}</span>
                                    <span class="text-[10px] font-mono text-gray-400">{{ $permission->name }}</span>
                                </div>
                                <p class="text-[11px] text-gray-500 mt-0.5 leading-snug">{{ $permission->description }}</p>
                            </div>
                        </label>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex items-center justify-end gap-3 pt-4">
            <a href="{{ route('roles.index') }}" class="px-5 py-2.5 rounded-xl border border-gray-200 text-xs font-bold text-gray-600 hover:bg-gray-50 transition uppercase tracking-wider">
                Batal
            </a>
            <button type="submit" class="px-7 py-2.5 bg-primary text-white rounded-xl font-bold uppercase tracking-wider text-xs hover:bg-blue-800 transition shadow-lg shadow-blue-100 flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                Simpan Peran & Hak Akses
            </button>
        </div>
    </form>
</div>
@endsection
