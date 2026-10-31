@extends('layouts.app')

@section('title', 'New Project')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('projects.index') }}" class="text-sm text-gray-500 hover:text-primary flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            Back to Projects
        </a>
        <h1 class="text-2xl font-bold text-gray-800 mt-2">New Project</h1>
    </div>

    <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-6">
        <form action="{{ route('projects.store') }}" method="POST">
            @csrf
            <div class="space-y-6">
                <div>
                    <label for="client_id" class="block text-sm font-bold text-gray-700 uppercase tracking-wider mb-2">Select Client</label>
                    <select name="client_id" id="client_id" required class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm p-3 border">
                        <option value="">-- Select Client --</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>
                                {{ $client->name }} {{ $client->company_name ? "({$client->company_name})" : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('client_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    <div class="mt-2">
                        <a href="{{ route('clients.create') }}" class="text-xs text-indigo-600 hover:text-indigo-900">+ Add new client if not listed</a>
                    </div>
                </div>

                <div>
                    <label for="title" class="block text-sm font-bold text-gray-700 uppercase tracking-wider mb-2">Project Title</label>
                    <input type="text" name="title" id="title" value="{{ old('title') }}" required placeholder="e.g. E-Commerce Website Development" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm p-3 border">
                    @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="status" class="block text-sm font-bold text-gray-700 uppercase tracking-wider mb-2">Initial Status</label>
                        <select name="status" id="status" required class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm p-3 border">
                            <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="quotation_sent" {{ old('status') == 'quotation_sent' ? 'selected' : '' }}>Quotation Sent</option>
                            <option value="approved" {{ old('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="in_progress" {{ old('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        </select>
                        @error('status') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="start_date" class="block text-sm font-bold text-gray-700 uppercase tracking-wider mb-2">Start Date</label>
                        <input type="date" name="start_date" id="start_date" value="{{ old('start_date') }}" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm p-3 border">
                        @error('start_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <!-- Informasi Teknis & Kredensial Server -->
                <div class="border-t border-gray-100 pt-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Informasi Teknis &amp; Kredensial Server</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Tautan deployment dan kredensial hosting terenkripsi (AES-256).</p>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            Enkripsi Otomatis
                        </span>
                    </div>

                    <div class="bg-gray-50/70 p-5 rounded-xl border border-gray-200 space-y-4">
                        <!-- URLs -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="demo_url" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Demo / Staging URL</label>
                                <input type="url" name="demo_url" id="demo_url" value="{{ old('demo_url') }}" placeholder="https://staging.contoh.com" class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-primary focus:border-primary text-sm p-2.5 border bg-white">
                                @error('demo_url') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="repository_url" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Repository URL</label>
                                <input type="url" name="repository_url" id="repository_url" value="{{ old('repository_url') }}" placeholder="https://github.com/org/repo" class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-primary focus:border-primary text-sm p-2.5 border bg-white">
                                @error('repository_url') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="production_url" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Production URL</label>
                                <input type="url" name="production_url" id="production_url" value="{{ old('production_url') }}" placeholder="https://contoh.com" class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-primary focus:border-primary text-sm p-2.5 border bg-white">
                                @error('production_url') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <!-- Hosting Provider, Host & Port -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2 border-t border-gray-200/80">
                            <div>
                                <label for="hosting_provider" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Hosting Provider</label>
                                <input type="text" name="hosting_provider" id="hosting_provider" value="{{ old('hosting_provider') }}" placeholder="misal: Niagahoster, AWS, cPanel" class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-primary focus:border-primary text-sm p-2.5 border bg-white">
                                @error('hosting_provider') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="hosting_host" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Host / IP Server</label>
                                <input type="text" name="hosting_credentials[host]" id="hosting_host" value="{{ old('hosting_credentials.host') }}" placeholder="misal: 192.168.1.1 atau cpanel.domain.com" class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-primary focus:border-primary text-sm p-2.5 border bg-white">
                                @error('hosting_credentials.host') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="hosting_port" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Port</label>
                                <input type="text" name="hosting_credentials[port]" id="hosting_port" value="{{ old('hosting_credentials.port') }}" placeholder="misal: 22 / 2083" class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-primary focus:border-primary text-sm p-2.5 border bg-white">
                                @error('hosting_credentials.port') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <!-- Username & Password -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="hosting_username" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Username / Akun</label>
                                <input type="text" name="hosting_credentials[username]" id="hosting_username" value="{{ old('hosting_credentials.username') }}" placeholder="Username login" class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-primary focus:border-primary text-sm p-2.5 border bg-white">
                                @error('hosting_credentials.username') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div x-data="{ showPass: false }">
                                <label for="hosting_password" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Password</label>
                                <div class="relative">
                                    <input :type="showPass ? 'text' : 'password'" name="hosting_credentials[password]" id="hosting_password" value="{{ old('hosting_credentials.password') }}" placeholder="Password server" class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-primary focus:border-primary text-sm p-2.5 pr-10 border bg-white">
                                    <button type="button" @click="showPass = !showPass" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 cursor-pointer">
                                        <svg x-show="!showPass" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        <svg x-show="showPass" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                                    </button>
                                </div>
                                @error('hosting_credentials.password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <!-- Notes -->
                        <div>
                            <label for="hosting_notes" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Catatan Tambahan Akses / Server</label>
                            <textarea name="hosting_credentials[notes]" id="hosting_notes" rows="2" placeholder="Catatan konfigurasi SSH, directori path, URL login panel, dsb." class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-primary focus:border-primary text-sm p-2.5 border bg-white">{{ old('hosting_credentials.notes') }}</textarea>
                            @error('hosting_credentials.notes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <!-- Visibility Checkbox Switch -->
                        <div class="pt-3 border-t border-gray-200/80">
                            <label class="relative flex items-start gap-3 cursor-pointer select-none">
                                <input type="checkbox" name="is_credentials_visible_to_client" value="1" {{ old('is_credentials_visible_to_client') ? 'checked' : '' }} class="mt-0.5 h-4 w-4 text-primary rounded border-gray-300 focus:ring-primary">
                                <div>
                                    <span class="block text-xs font-bold text-gray-800">Izinkan Klien Melihat Kredensial Ini di WiroMitra</span>
                                    <span class="block text-[11px] text-gray-500">Jika dicentang, klien dapat melihat kredensial hosting ini pada dashboard portal WiroMitra mereka. Jika tidak dicentang, kredensial ini hanya dapat diakses secara internal oleh tim.</span>
                                </div>
                            </label>
                            @error('is_credentials_visible_to_client') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <!-- Team Members Assignment (Project-Scoped Multi-Role) -->
                <div class="border-t border-gray-100 pt-6" x-data="{
                    availableUsers: {{ json_encode($users->map(fn($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email])) }},
                    assignableRoles: {{ json_encode($roles->map(fn($r) => ['slug' => $r->slug, 'name' => $r->name])) }},
                    members: [],
                    addMember() {
                        let defaultRole = this.assignableRoles.length > 0 ? this.assignableRoles[0].slug : 'staff';
                        this.members.push({
                            user_id: '',
                            roles: [defaultRole]
                        });
                    },
                    removeMember(index) {
                        this.members.splice(index, 1);
                    },
                    toggleRole(memberIndex, roleSlug) {
                        let idx = this.members[memberIndex].roles.indexOf(roleSlug);
                        if (idx > -1) {
                            if (this.members[memberIndex].roles.length > 1) {
                                this.members[memberIndex].roles.splice(idx, 1);
                            }
                        } else {
                            this.members[memberIndex].roles.push(roleSlug);
                        }
                    }
                }">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Penugasan Tim Proyek (Opsional)</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Tugaskan anggota tim dengan peran pada proyek ini (multi-role didukung).</p>
                        </div>
                        <button type="button" @click="addMember()" class="inline-flex items-center px-3 py-1.5 bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 rounded-lg text-xs font-bold transition">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            + Tambah Anggota Tim
                        </button>
                    </div>

                    <!-- Empty state -->
                    <template x-if="members.length === 0">
                        <div class="p-5 rounded-xl border border-dashed border-gray-200 text-center bg-gray-50/50">
                            <p class="text-xs text-gray-400">Belum ada anggota tim yang ditambahkan. Anda juga dapat menugaskan tim nanti di halaman detail proyek.</p>
                        </div>
                    </template>

                    <!-- Member rows -->
                    <div class="space-y-3">
                        <template x-for="(member, index) in members" :key="index">
                            <div class="p-4 rounded-xl border border-gray-200 bg-gray-50/50 space-y-3">
                                <div class="flex items-center justify-between gap-4">
                                    <div class="flex-1">
                                        <label class="block text-xs font-bold text-gray-600 mb-1">Pilih Staf</label>
                                        <select :name="'members[' + index + '][user_id]'" x-model="member.user_id" required class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-primary focus:border-primary text-sm p-2.5 border bg-white">
                                            <option value="">-- Pilih Staf --</option>
                                            <template x-for="user in availableUsers" :key="user.id">
                                                <option :value="user.id" x-text="user.name + ' (' + user.email + ')'"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <button type="button" @click="removeMember(index)" class="mt-5 text-gray-400 hover:text-red-600 p-1.5 rounded-lg hover:bg-red-50 transition" title="Hapus anggota">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-1.5">Peran dalam Proyek Ini (Dapat memilih lebih dari satu)</label>
                                    <div class="flex flex-wrap gap-2">
                                        <template x-for="role in assignableRoles" :key="role.slug">
                                            <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs font-bold cursor-pointer transition select-none"
                                                   :class="member.roles.includes(role.slug) ? 'bg-primary text-white border-primary shadow-xs' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'">
                                                <input type="checkbox" :name="'members[' + index + '][roles][]'" :value="role.slug"
                                                       :checked="member.roles.includes(role.slug)"
                                                       @change="toggleRole(index, role.slug)"
                                                       class="sr-only">
                                                <span x-text="role.name"></span>
                                            </label>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <div class="mt-8 flex justify-end">
                <button type="submit" class="inline-flex items-center px-6 py-3 bg-primary border border-transparent rounded-lg font-bold text-xs text-white uppercase tracking-wider hover:bg-blue-700 active:bg-blue-900 transition-all shadow-md">
                    Create Project
                </button>
            </div>
        </form>
    </div>
</div>
@endsection