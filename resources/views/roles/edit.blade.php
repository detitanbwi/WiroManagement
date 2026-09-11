@extends('layouts.app')

@section('title', 'Configure Role & Permissions: ' . $role->name)

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
            Back to Roles
        </a>
        <div class="flex items-center gap-3">
            <h1 class="text-2xl font-black text-gray-800 tracking-tight">Configure Permissions: {{ $role->name }}</h1>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded text-[11px] font-bold uppercase border {{ $role->badge_classes }}">
                {{ $role->slug }}
            </span>
            @if($role->is_system)
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-gray-100 text-gray-600 border border-gray-200">
                    System Role
                </span>
            @endif
        </div>
        <p class="text-gray-500 text-sm mt-0.5">Customize granular permissions for this role. Changes will immediately affect all users assigned to this role.</p>
    </div>

    <form action="{{ route('roles.update', $role) }}" method="POST" class="space-y-8">
        @csrf
        @method('PUT')

        <!-- Basic Role Information -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 sm:p-8">
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Role Identity &amp; Display</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-2">Role Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $role->name) }}" required {{ $role->is_system && in_array($role->slug, ['superadmin', 'client']) ? 'readonly' : '' }}
                        class="block w-full border-gray-200 rounded-xl focus:ring-primary focus:border-primary p-3 border text-sm {{ $role->is_system && in_array($role->slug, ['superadmin', 'client']) ? 'bg-gray-50 cursor-not-allowed' : '' }}">
                    @error('name') <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-2">System Slug (Readonly)</label>
                    <input type="text" value="{{ $role->slug }}" readonly
                        class="block w-full border-gray-200 rounded-xl bg-gray-50 cursor-not-allowed p-3 border text-sm font-mono text-gray-500">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-gray-700 mb-2">Role Responsibilities Description</label>
                    <input type="text" name="description" value="{{ old('description', $role->description) }}" placeholder="Role authority and scope description"
                        class="block w-full border-gray-200 rounded-xl focus:ring-primary focus:border-primary p-3 border text-sm">
                    @error('description') <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p> @enderror
                </div>
            </div>

            <!-- Role Badge Color Theme -->
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-2.5">Role Badge Color Theme</label>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    @foreach($colors as $colorKey => $colorData)
                    <label class="flex items-center p-3 rounded-xl border border-gray-200 hover:border-blue-300 cursor-pointer transition has-checked:border-primary has-checked:bg-blue-50/50 has-checked:ring-1 has-checked:ring-primary">
                        <input type="radio" name="color" value="{{ $colorKey }}" {{ old('color', $role->color) === $colorKey ? 'checked' : '' }} class="w-4 h-4 text-primary border-gray-300 focus:ring-primary">
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

        <!-- Permission Matrix -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 sm:p-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6 pb-4 border-b border-gray-100">
                <div>
                    <h3 class="text-base font-black text-gray-900 tracking-tight">Permission Matrix</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Configure permissions for this role. Check the permissions to grant.</p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="toggleAll(true)" class="px-3 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-bold transition">
                        Select All
                    </button>
                    <button type="button" @click="toggleAll(false)" class="px-3 py-1.5 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600 text-xs font-bold transition">
                        Clear All
                    </button>
                </div>
            </div>

            @if($role->slug === 'superadmin')
            <div class="mb-6 p-4 rounded-xl bg-purple-50 border border-purple-200 text-purple-800 text-xs leading-relaxed flex items-center">
                <svg class="w-5 h-5 text-purple-600 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span><strong>Note:</strong> Super Admin role automatically bypasses all system permission checks. The checkboxes below reflect full capability documentation.</span>
            </div>
            @endif

            <div class="space-y-6">
                @foreach($permissionGroups as $groupName => $groupPermissions)
                @php $groupSlug = Str::slug($groupName); @endphp
                <div class="border border-gray-200 rounded-2xl p-5 bg-gray-50/40 hover:bg-gray-50 transition">
                    <!-- Group Header -->
                    <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-200">
                        <div class="flex items-center gap-2.5">
                            <div class="w-2.5 h-2.5 rounded-full bg-primary"></div>
                            <h4 class="text-sm font-black text-gray-900 uppercase tracking-wider">{{ $groupName }}</h4>
                            <span class="text-xs text-gray-400 font-semibold">({{ $groupPermissions->count() }} Permissions)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="toggleGroup('group-{{ $groupSlug }}', true)" class="text-[11px] font-bold text-primary hover:underline">
                                Select Group
                            </button>
                            <span class="text-gray-300">&bull;</span>
                            <button type="button" @click="toggleGroup('group-{{ $groupSlug }}', false)" class="text-[11px] font-bold text-gray-500 hover:underline">
                                Deselect
                            </button>
                        </div>
                    </div>

                    <!-- Permission Items -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        @foreach($groupPermissions as $permission)
                        @php
                            $isChecked = is_array(old('permissions')) 
                                ? in_array($permission->name, old('permissions')) 
                                : in_array($permission->name, $currentPermissions);
                        @endphp
                        <label class="flex items-start p-3 rounded-xl border border-gray-200 bg-white hover:border-blue-300 cursor-pointer transition has-checked:border-primary has-checked:bg-blue-50/40">
                            <div class="flex items-center h-5">
                                <input type="checkbox" name="permissions[]" value="{{ $permission->name }}"
                                    {{ $isChecked ? 'checked' : '' }}
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
                Cancel
            </a>
            <button type="submit" class="px-7 py-2.5 bg-primary text-white rounded-xl font-bold uppercase tracking-wider text-xs hover:bg-blue-800 transition shadow-lg shadow-blue-100 flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                Save Role Configuration
            </button>
        </div>
    </form>
</div>
@endsection
