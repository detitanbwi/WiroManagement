@extends('layouts.app')

@section('title', 'Manajemen Peran & Hak Akses (RBAC)')

@section('content')
<div class="max-w-6xl mx-auto">
    <!-- Header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('users.index') }}" class="text-xs font-bold text-gray-400 hover:text-primary uppercase tracking-wider">
                    Manajemen Pengguna
                </a>
                <span class="text-gray-300">&bull;</span>
                <span class="text-xs font-bold text-primary uppercase tracking-wider">Peran & Izin</span>
            </div>
            <h1 class="text-2xl font-black text-gray-800 tracking-tight">Peran & Matriks Hak Akses (RBAC)</h1>
            <p class="text-gray-500 text-sm mt-0.5">Kelola peran dinamis dan konfigurasikan izin (*granular permissions*) untuk setiap peran sistem.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('users.index') }}" class="inline-flex items-center px-4 py-2.5 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 rounded-xl font-bold uppercase tracking-wider text-xs transition">
                <svg class="w-4 h-4 mr-1.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                Daftar Pengguna
            </a>
            <a href="{{ route('roles.create') }}" class="inline-flex items-center px-5 py-2.5 bg-primary hover:bg-blue-800 text-white rounded-xl font-bold uppercase tracking-wider text-xs shadow-lg shadow-blue-100 transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                Tambah Peran Baru
            </a>
        </div>
    </div>

    <!-- Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-8">
        @foreach($roles as $role)
        <div class="bg-white rounded-2xl border border-gray-200 shadow-xs hover:shadow-md transition flex flex-col justify-between overflow-hidden">
            <div class="p-6">
                <div class="flex items-start justify-between gap-3 mb-3">
                    <div class="flex items-center gap-2.5">
                        <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-black uppercase tracking-wider border shadow-xs {{ $role->badge_classes }}">
                            {{ $role->name }}
                        </span>
                        @if($role->is_system)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-gray-100 text-gray-600 border border-gray-200" title="Peran inti sistem bawaan">
                                Sistem
                            </span>
                        @endif
                    </div>
                    <span class="text-xs font-mono text-gray-400 bg-gray-50 px-2 py-0.5 rounded border border-gray-100">
                        {{ $role->slug }}
                    </span>
                </div>

                <p class="text-xs text-gray-600 leading-relaxed mb-4 min-h-[32px]">
                    {{ $role->description ?: 'Tidak ada deskripsi peran.' }}
                </p>

                <!-- Statistics & Badges -->
                <div class="grid grid-cols-2 gap-2 p-3 rounded-xl bg-gray-50/80 border border-gray-100 text-center mb-4">
                    <div>
                        <div class="text-xs text-gray-400 font-bold uppercase tracking-wider">Pengguna</div>
                        <div class="text-base font-black text-gray-800">{{ $role->users_count }} Akun</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-400 font-bold uppercase tracking-wider">Hak Akses</div>
                        <div class="text-base font-black text-primary">
                            @if($role->slug === 'superadmin')
                                Semua (Full)
                            @else
                                {{ $role->permissions_count }} Izin
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Preview Active Permissions -->
                <div>
                    <div class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-2">Cakupan Izin Utama:</div>
                    <div class="flex flex-wrap gap-1">
                        @if($role->slug === 'superadmin')
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                Bypass Mutlak Seluruh Modul & Fitur
                            </span>
                        @elseif($role->permissions->isEmpty())
                            <span class="text-xs text-gray-400 italic">Belum ada izin yang dikonfigurasi.</span>
                        @else
                            @foreach($role->permissions->take(6) as $perm)
                                <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                    {{ $perm->label }}
                                </span>
                            @endforeach
                            @if($role->permissions->count() > 6)
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 text-gray-600">
                                    +{{ $role->permissions->count() - 6 }} lainnya
                                </span>
                            @endif
                        @endif
                    </div>
                </div>
            </div>

            <!-- Card Actions Footer -->
            <div class="px-6 py-3.5 bg-gray-50/50 border-t border-gray-100 flex items-center justify-between">
                <a href="{{ route('roles.edit', $role) }}" class="inline-flex items-center text-xs font-bold text-primary hover:text-blue-800 transition">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    Konfigurasi Izin & Edit
                </a>

                @if(!$role->is_system && $role->users_count === 0)
                <form action="{{ route('roles.destroy', $role) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus peran {{ $role->name }}?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-xs font-bold text-rose-500 hover:text-rose-700 transition">
                        Hapus
                    </button>
                </form>
                @elseif(!$role->is_system)
                    <span class="text-[10px] font-medium text-gray-400" title="Tidak dapat dihapus karena masih digunakan oleh pengguna">Digunakan</span>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection
