@extends('layouts.app')

@section('title', 'Daftar Client & Showcase')

@section('content')
<div class="max-w-7xl mx-auto pb-10">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Daftar Client & Showcase</h1>
            <p class="text-xs text-gray-500 mt-1">Kelola data klien, proyek, serta artikel profil usaha yang tayang di wirodev.com.</p>
        </div>
        <a href="{{ route('clients.create') }}" class="inline-flex items-center px-4 py-2.5 bg-primary border border-transparent rounded-lg font-bold text-xs text-white uppercase tracking-wider hover:bg-blue-700 active:bg-blue-900 transition shadow-sm">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Tambah Client Baru
        </a>
    </div>

    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nama / Perusahaan</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Kontak</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Showcase & Artikel Web</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Proyek</th>
                        <th scope="col" class="px-6 py-3.5 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($clients as $client)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-9 h-9 rounded-lg bg-blue-100 text-primary font-bold text-sm flex items-center justify-center flex-shrink-0">
                                    {{ substr($client->name, 0, 1) }}
                                </div>
                                <div>
                                    <div class="text-sm font-bold text-gray-900">{{ $client->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $client->company_name ?? '-' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-xs text-gray-900 font-medium">{{ $client->email ?? '-' }}</div>
                            <div class="text-xs text-gray-500">{{ $client->phone ?? '-' }}</div>
                        </td>
                        <td class="px-6 py-4">
                            @if($client->profile)
                                <div class="space-y-1.5">
                                    <div class="flex items-center gap-2">
                                        @if($client->profile->is_published)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-green-100 text-green-800">
                                                ● Live di Web
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-gray-100 text-gray-600">
                                                Draft
                                            </span>
                                        @endif

                                        @if($client->profile->slug)
                                            <a href="http://localhost/wirodev/klien/{{ $client->profile->slug }}" target="_blank" class="text-[11px] text-blue-600 hover:underline font-medium inline-flex items-center">
                                                Lihat Web ↗
                                            </a>
                                        @endif
                                    </div>
                                    <a href="{{ route('clients.edit', $client) }}?tab=profile" class="inline-flex items-center gap-1 text-xs font-bold text-primary hover:text-blue-700 bg-blue-50 hover:bg-blue-100 px-2.5 py-1 rounded-md transition">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        Edit Artikel &amp; Profil (WYSIWYG)
                                    </a>
                                </div>
                            @else
                                <a href="{{ route('clients.edit', $client) }}?tab=profile" class="inline-flex items-center gap-1 text-xs font-medium text-gray-500 hover:text-primary bg-gray-100 hover:bg-blue-50 px-2.5 py-1 rounded-md transition">
                                    + Buat Profil Artikel
                                </a>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2.5 py-1 inline-flex text-xs leading-4 font-bold rounded-md bg-blue-50 text-blue-700 border border-blue-100">
                                {{ $client->projects_count }} Proyek
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-xs font-semibold">
                            <div class="flex justify-end items-center space-x-2">
                                <a href="{{ route('clients.show', $client) }}" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 px-2 py-1 rounded">Detail</a>
                                <a href="{{ route('clients.edit', $client) }}" class="text-gray-700 hover:text-gray-900 bg-gray-100 px-2 py-1 rounded">Edit</a>
                                <form action="{{ route('clients.destroy', $client) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus client ini? semua proyek terkait akan ikut terhapus.')" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900 bg-red-50 px-2 py-1 rounded">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 whitespace-nowrap text-center text-gray-500 text-sm">
                            Belum ada client terdaftar.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
