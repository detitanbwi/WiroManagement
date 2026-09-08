@extends('layouts.app')

@section('title', 'Manajemen Pengguna & Multi-Role')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-gray-800 tracking-tight">Manajemen Pengguna & Peran (RBAC)</h1>
            <p class="text-gray-500 text-sm mt-0.5">Kelola akun staf, atur hak akses multi-peran, dan pantau status keaktifan pengguna sistem.</p>
        </div>
        @can('users.create')
        <a href="{{ route('users.create') }}" class="inline-flex items-center px-5 py-2.5 bg-primary hover:bg-blue-800 text-white rounded-xl font-bold uppercase tracking-wider text-xs shadow-lg shadow-blue-100 transition">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            Tambah Pengguna
        </a>
        @endcan
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50/80">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Nama & Email</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Peran (Roles)</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Aktivitas</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-400 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($users as $user)
                    <tr class="hover:bg-gray-50/80 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="h-10 w-10 rounded-full bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center text-white font-black text-sm shadow-sm flex-shrink-0">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div class="ml-3.5">
                                    <div class="flex items-center gap-1.5">
                                        <p class="text-sm font-bold text-gray-900">{{ $user->name }}</p>
                                        @if($user->id === auth()->id())
                                            <span class="text-[10px] font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full border border-blue-100">Anda</span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-gray-500 font-mono">{{ $user->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap gap-1.5 max-w-xs">
                                @forelse($user->role_badges as $badge)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold uppercase border shadow-xs {{ $badge['classes'] }}">
                                        {{ $badge['name'] }}
                                    </span>
                                @empty
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold uppercase bg-gray-100 text-gray-600 border border-gray-200">
                                        {{ $user->role ?? 'Tanpa Role' }}
                                    </span>
                                @endforelse
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @can('users.edit')
                                @if($user->id !== auth()->id())
                                    <form action="{{ route('users.toggle-status', $user) }}" method="POST" class="inline-block">
                                        @csrf
                                        <button type="submit" title="Klik untuk ubah status" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold transition {{ $user->is_active ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200' : 'bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200' }}">
                                            <span class="w-1.5 h-1.5 rounded-full mr-1.5 {{ $user->is_active ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                            {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </button>
                                    </form>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full mr-1.5 bg-emerald-500"></span>
                                        Aktif
                                    </span>
                                @endif
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold {{ $user->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                                    <span class="w-1.5 h-1.5 rounded-full mr-1.5 {{ $user->is_active ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                    {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            @endcan
                        </td>
                        <td class="px-6 py-4 text-xs text-gray-500">
                            <div><span class="text-gray-400">Dibuat:</span> {{ $user->created_at->format('d M Y') }}</div>
                            @if($user->last_login_at)
                                <div class="text-[11px] text-gray-400 mt-0.5"><span class="text-gray-400">Login:</span> {{ $user->last_login_at->diffForHumans() }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                @can('users.edit')
                                <a href="{{ route('users.edit', $user) }}" class="p-1.5 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-lg transition" title="Edit Pengguna">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </a>
                                @endcan

                                @can('users.delete')
                                @if($user->id !== auth()->id())
                                <form action="{{ route('users.destroy', $user) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun {{ $user->name }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition" title="Hapus Pengguna">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </form>
                                @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
