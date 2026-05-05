@extends('layouts.app')

@section('title', 'Edit Client')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('clients.index') }}" class="text-sm text-gray-500 hover:text-primary flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            Kembali ke Daftar
        </a>
        <h1 class="text-2xl font-bold text-gray-800 mt-2">Edit Client</h1>
    </div>

    <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-6">
        <form action="{{ route('clients.update', $client) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label for="name" class="block text-sm font-bold text-gray-700 uppercase tracking-wider mb-2">Nama Lengkap (PIC)</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $client->name) }}" required class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm p-3 border">
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="company_name" class="block text-sm font-bold text-gray-700 uppercase tracking-wider mb-2">Nama Perusahaan / Organisasi</label>
                    <input type="text" name="company_name" id="company_name" value="{{ old('company_name', $client->company_name) }}" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm p-3 border">
                    @error('company_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-bold text-gray-700 uppercase tracking-wider mb-2">Email Utama</label>
                    <input type="email" name="email" id="email" value="{{ old('email', $client->email) }}" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm p-3 border">
                    @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="phone" class="block text-sm font-bold text-gray-700 uppercase tracking-wider mb-2">Nomor Telepon / WA</label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone', $client->phone) }}" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm p-3 border">
                    @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="address" class="block text-sm font-bold text-gray-700 uppercase tracking-wider mb-2">Alamat Lengkap</label>
                    <textarea name="address" id="address" rows="3" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm p-3 border">{{ old('address', $client->address) }}</textarea>
                    @error('address') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-8 flex justify-end">
                <button type="submit" class="inline-flex items-center px-6 py-3 bg-primary border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 transition ease-in-out duration-150">
                    Update Data Client
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
