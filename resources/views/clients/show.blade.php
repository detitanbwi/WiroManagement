@extends('layouts.app')

@section('title', 'Detail Klien: ' . $client->name)

@section('content')
<div class="max-w-7xl mx-auto pb-10 space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ route('clients.index') }}" class="text-gray-400 hover:text-gray-600 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <h1 class="text-2xl font-bold text-gray-800">{{ $client->name }}</h1>
            </div>
            <p class="text-xs text-gray-500 mt-1">{{ $client->company_name ?? 'Klien Perorangan' }} • Terdaftar sejak {{ $client->created_at->format('d M Y') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('clients.edit', $client) }}" class="px-3.5 py-2 bg-white border border-gray-300 rounded-lg text-xs font-bold text-gray-700 hover:bg-gray-50 shadow-sm transition">
                Edit Profil
            </a>
            <a href="{{ route('projects.create') }}?client_id={{ $client->id }}" class="px-3.5 py-2 bg-primary border border-transparent rounded-lg text-xs font-bold text-white hover:bg-blue-700 shadow-sm transition">
                + Tambah Proyek Klien
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="p-4 rounded-xl bg-green-50 border border-green-200 text-green-800 text-xs font-medium flex items-center justify-between">
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <span>{{ session('success') }}</span>
        </div>
    </div>
    @endif

    @if(session('error'))
    <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-xs font-medium flex items-center gap-2">
        <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        <span>{{ session('error') }}</span>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Kolom Kiri: Informasi Klien --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-4">
            <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wider border-b pb-3">Informasi Klien</h2>
            
            <div class="space-y-3 text-xs">
                <div>
                    <span class="text-gray-400 block mb-0.5">Nama PIC</span>
                    <span class="font-bold text-gray-800 text-sm">{{ $client->name }}</span>
                </div>
                <div>
                    <span class="text-gray-400 block mb-0.5">Perusahaan</span>
                    <span class="font-semibold text-gray-700">{{ $client->company_name ?? '-' }}</span>
                </div>
                <div>
                    <span class="text-gray-400 block mb-0.5">Email</span>
                    <span class="font-medium text-gray-700">{{ $client->email ?? '-' }}</span>
                </div>
                <div>
                    <span class="text-gray-400 block mb-0.5">Nomor Telepon</span>
                    <span class="font-medium text-gray-700">{{ $client->phone ?? '-' }}</span>
                </div>
                <div>
                    <span class="text-gray-400 block mb-0.5">Alamat</span>
                    <span class="font-normal text-gray-600">{{ $client->address ?? '-' }}</span>
                </div>
            </div>
        </div>

        {{-- Kolom Kanan: Akses Portal Wiromitra (Otomatisasi Kredensial) --}}
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-5">
            <div class="flex items-center justify-between border-b pb-3">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-md bg-blue-600 text-white flex items-center justify-center font-bold text-xs">W</div>
                    <h2 class="text-sm font-bold text-gray-900">Akses Portal Klien (Wiromitra)</h2>
                </div>
                @php
                    $portalUser = $client->users->where('role', 'client')->first();
                    $activeInvite = $client->invitations->whereNull('accepted_at')->first();
                @endphp
                @if($portalUser)
                    <span class="px-2.5 py-1 text-[11px] font-bold rounded-full bg-green-100 text-green-800 border border-green-200">
                        ● Akun Aktif
                    </span>
                @elseif($activeInvite)
                    <span class="px-2.5 py-1 text-[11px] font-bold rounded-full bg-amber-100 text-amber-800 border border-amber-200">
                        ● Menunggu Aktivasi Password
                    </span>
                @else
                    <span class="px-2.5 py-1 text-[11px] font-bold rounded-full bg-gray-100 text-gray-600 border border-gray-200">
                        Belum Terdaftar
                    </span>
                @endif
            </div>

            <div class="bg-gray-50 rounded-lg p-4 text-xs text-gray-600 space-y-2 border border-gray-100">
                <p>Wiromitra memungkinkan klien melihat progres seluruh proyek yang ditugaskan kepada mereka dalam satu dashboard eksekutif.</p>
                <div class="flex items-center gap-4 text-xs font-medium text-gray-700 pt-1">
                    <span>URL Portal: <a href="http://localhost:4200" target="_blank" class="text-primary font-bold hover:underline">http://localhost:4200 ↗</a></span>
                </div>
            </div>

            {{-- Action Buttons untuk Otomatisasi Kredensial --}}
            <div class="flex flex-wrap items-center gap-3 pt-2">
                <form action="{{ route('clients.portal-invite', $client) }}" method="POST">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg shadow-sm transition">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        Buat Link Undangan Aman (Onboarding)
                    </button>
                </form>

                <form action="{{ route('clients.portal-instant-credentials', $client) }}" method="POST" onsubmit="return confirm('Ini akan men-generate password baru untuk klien. Lanjutkan?')">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-lg transition border border-gray-300">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                        Generate Kredensial Instan
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Daftar Proyek Klien (Multi-Project) --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <div>
                <h3 class="font-bold text-sm text-gray-900">Daftar Proyek Klien Ini ({{ $client->projects->count() }})</h3>
                <p class="text-xs text-gray-500">Seluruh proyek ini akan otomatis dirangkum dalam dashboard Wiromitra klien.</p>
            </div>
            <a href="{{ route('projects.create') }}?client_id={{ $client->id }}" class="text-xs text-primary font-bold hover:underline">
                + Proyek Baru
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50 text-xs font-bold text-gray-500 uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-3 text-left">Kode &amp; Judul Proyek</th>
                        <th class="px-6 py-3 text-left">Status</th>
                        <th class="px-6 py-3 text-left">Progress</th>
                        <th class="px-6 py-3 text-left">Timeline</th>
                        <th class="px-6 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 text-xs">
                    @forelse($client->projects as $project)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <span class="font-mono text-[10px] text-gray-400 block">{{ $project->project_code ?? 'PRJ-' . str_pad($project->id, 3, '0', STR_PAD_LEFT) }}</span>
                            <span class="font-bold text-gray-900 text-sm">{{ $project->title }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-blue-100 text-blue-800">
                                {{ strtoupper(str_replace('_', ' ', $project->status)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="w-36">
                                <div class="flex justify-between text-[11px] font-semibold text-gray-700 mb-1">
                                    <span>{{ $project->progress_percentage }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-1.5 overflow-hidden">
                                    <div class="bg-primary h-1.5 rounded-full" style="width: {{ $project->progress_percentage }}%"></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-500">
                            {{ $project->start_date ? $project->start_date->format('d M Y') : '-' }} s/d {{ $project->end_date ? $project->end_date->format('d M Y') : '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right font-medium">
                            <a href="{{ route('projects.show', $project) }}" class="text-primary hover:underline font-bold">Lihat Proyek ↗</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                            Belum ada proyek untuk klien ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
