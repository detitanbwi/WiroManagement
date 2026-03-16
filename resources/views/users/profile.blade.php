@extends('layouts.app')

@section('title', 'Profil Saya')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Profil Saya</h1>
        <p class="text-gray-500 text-sm">Kelola informasi akun dan kata sandi Anda.</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-8">
            <form action="{{ route('profile.update') }}" method="POST" class="space-y-6">
                @csrf
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="col-span-1">
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Nama Lengkap</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                            class="block w-full border-gray-200 rounded-xl focus:ring-primary focus:border-primary p-3 border">
                        @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="col-span-1">
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Email Address</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                            class="block w-full border-gray-200 rounded-xl focus:ring-primary focus:border-primary p-3 border">
                        @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="col-span-2 pt-4 border-t border-gray-100">
                        <p class="text-xs font-bold text-primary uppercase mb-4">Ubah Kata Sandi (Kosongkan jika tidak ingin mengubah)</p>
                    </div>

                    <div class="col-span-1">
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Password Baru</label>
                        <input type="password" name="password"
                            class="block w-full border-gray-200 rounded-xl focus:ring-primary focus:border-primary p-3 border">
                        @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="col-span-1">
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Konfirmasi Password</label>
                        <input type="password" name="password_confirmation"
                            class="block w-full border-gray-200 rounded-xl focus:ring-primary focus:border-primary p-3 border">
                    </div>
                </div>

                <div class="flex justify-end pt-6">
                    <button type="submit" class="px-8 py-3 bg-primary text-white rounded-xl font-bold uppercase tracking-widest text-xs hover:bg-blue-800 transition shadow-lg shadow-blue-100">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
