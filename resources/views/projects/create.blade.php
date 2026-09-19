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