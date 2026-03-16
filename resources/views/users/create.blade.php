@extends('layouts.app')

@section('title', 'Tambah User')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('users.index') }}" class="text-xs font-bold text-gray-400 hover:text-primary uppercase tracking-widest flex items-center mb-2">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            Kembali ke Daftar
        </a>
        <h1 class="text-2xl font-bold text-gray-800">Tambah User Baru</h1>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-8">
            <form action="{{ route('users.store') }}" method="POST" class="space-y-6">
                @csrf
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="col-span-1">
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Nama Lengkap</label>
                        <input type="text" name="name" value="{{ old('name') }}" required placeholder="Masukkan nama lengkap"
                            class="block w-full border-gray-200 rounded-xl focus:ring-primary focus:border-primary p-3 border text-sm">
                        @error('name') <p class="mt-1 text-xs text-red-600 font-medium italic">{{ $message }}</p> @enderror
                    </div>

                    <div class="col-span-1">
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Email Address</label>
                        <input type="email" name="email" value="{{ old('email') }}" required placeholder="email@wirodev.com"
                            class="block w-full border-gray-200 rounded-xl focus:ring-primary focus:border-primary p-3 border text-sm">
                        @error('email') <p class="mt-1 text-xs text-red-600 font-medium italic">{{ $message }}</p> @enderror
                    </div>

                    <div class="col-span-1">
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Role Access</label>
                        <select name="role" required class="block w-full border-gray-200 rounded-xl focus:ring-primary focus:border-primary p-3 border text-sm">
                            <option value="staff" {{ old('role') == 'staff' ? 'selected' : '' }}>STAFF (Operasional)</option>
                            <option value="superadmin" {{ old('role') == 'superadmin' ? 'selected' : '' }}>SUPER ADMIN (Full Access)</option>
                        </select>
                        @error('role') <p class="mt-1 text-xs text-red-600 font-medium italic">{{ $message }}</p> @enderror
                    </div>

                    <div class="col-span-1"></div>

                    <div class="col-span-1">
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Password</label>
                        <input type="password" name="password" required placeholder="Minimal 8 karakter"
                            class="block w-full border-gray-200 rounded-xl focus:ring-primary focus:border-primary p-3 border text-sm">
                        @error('password') <p class="mt-1 text-xs text-red-600 font-medium italic">{{ $message }}</p> @enderror
                    </div>

                    <div class="col-span-1">
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Konfirmasi Password</label>
                        <input type="password" name="password_confirmation" required placeholder="Ulangi password"
                            class="block w-full border-gray-200 rounded-xl focus:ring-primary focus:border-primary p-3 border text-sm">
                    </div>
                </div>

                <div class="flex justify-end pt-6">
                    <button type="submit" class="px-8 py-3 bg-primary text-white rounded-xl font-bold uppercase tracking-widest text-xs hover:bg-blue-800 transition shadow-lg shadow-blue-100 flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Daftarkan User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
