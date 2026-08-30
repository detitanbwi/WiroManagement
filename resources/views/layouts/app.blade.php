<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Wirodayan Digital')</title>
    <!-- Tailwind CSS (via CDN for simplicity) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#003399',
                        secondary: '#4b5563',
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        [x-cloak] {
            display: none !important;
        }

        body {
            font-family: 'Inter', sans-serif;
        }
    </style>

    <!-- Alpine.js (Loaded in head with defer) -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
</head>

<body class="bg-gray-50 text-gray-800 antialiased">

    <div class="flex h-screen overflow-hidden" x-data="{ mobileMenuOpen: false }">
        <!-- Sidebar Overlay (Mobile) -->
        <div x-show="mobileMenuOpen" 
             x-cloak
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs z-40 md:hidden" 
             @click="mobileMenuOpen = false"></div>

        <!-- Sidebar -->
        <aside :class="mobileMenuOpen ? 'translate-x-0' : '-translate-x-full'" 
               class="fixed inset-y-0 left-0 z-50 w-72 md:w-64 bg-white border-r border-gray-200 transform transition-transform duration-300 ease-in-out md:relative md:translate-x-0 flex flex-col shadow-xl md:shadow-none">
            <div class="h-16 md:h-20 flex items-center justify-between border-b border-gray-200 px-5 md:px-6">
                <div class="flex items-center space-x-2.5">
                    <img src="{{ asset('logo.png') }}" alt="Wiro Logo" class="h-8 md:h-10 w-auto">
                    <span class="text-base md:text-lg font-extrabold text-primary tracking-wide">WIRO APP</span>
                </div>
                <!-- Close button for mobile -->
                <button @click="mobileMenuOpen = false" class="md:hidden text-gray-400 hover:text-gray-700 p-1.5 rounded-lg hover:bg-gray-100 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <nav class="flex-1 overflow-y-auto py-4">
                <ul class="space-y-1 px-3">
                    <li>
                        <a href="{{ route('dashboard') }}"
                            class="flex items-center px-4 py-2.5 rounded-lg group transition-all duration-200 {{ request()->routeIs('dashboard') ? 'bg-primary text-white font-semibold shadow-md' : 'text-gray-600 hover:bg-blue-50 hover:text-primary' }}">
                            <svg class="w-5 h-5 mr-3 {{ request()->routeIs('dashboard') ? 'text-white' : 'text-gray-400 group-hover:text-primary' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                            </svg>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('clients.index') }}"
                            class="flex items-center px-4 py-2.5 rounded-lg group transition-all duration-200 {{ request()->routeIs('clients.*') ? 'bg-primary text-white font-semibold shadow-md' : 'text-gray-600 hover:bg-blue-50 hover:text-primary' }}">
                            <svg class="w-5 h-5 mr-3 {{ request()->routeIs('clients.*') ? 'text-white' : 'text-gray-400 group-hover:text-primary' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            Clients
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('projects.index') }}"
                            class="flex items-center px-4 py-2.5 rounded-lg group transition-all duration-200 {{ request()->routeIs('projects.*') ? 'bg-primary text-white font-semibold shadow-md' : 'text-gray-600 hover:bg-blue-50 hover:text-primary' }}">
                            <svg class="w-5 h-5 mr-3 {{ request()->routeIs('projects.*') ? 'text-white' : 'text-gray-400 group-hover:text-primary' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                            </svg>
                            Projects
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('ai-pricing.index') }}"
                            class="flex items-center justify-between px-4 py-2.5 rounded-lg group transition-all duration-200 {{ request()->routeIs('ai-pricing.*') ? 'bg-gradient-to-r from-indigo-600 to-primary text-white font-semibold shadow-md' : 'text-gray-600 hover:bg-indigo-50 hover:text-indigo-600' }}">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-3 {{ request()->routeIs('ai-pricing.*') ? 'text-yellow-300' : 'text-indigo-500 group-hover:text-indigo-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                <span>AI Pricing</span>
                            </div>
                            <span class="text-[10px] uppercase font-black px-1.5 py-0.5 rounded {{ request()->routeIs('ai-pricing.*') ? 'bg-white/20 text-white' : 'bg-indigo-100 text-indigo-700' }}">
                                AI
                            </span>
                        </a>
                    </li>
                    <li x-data="{ open: {{ request()->routeIs('finance.*') ? 'true' : 'false' }} }">
                        <button @click="open = !open" 
                                class="w-full flex items-center justify-between px-4 py-2.5 rounded-lg group transition-all duration-200 {{ request()->routeIs('finance.*') ? 'bg-primary text-white font-semibold shadow-md' : 'text-gray-600 hover:bg-blue-50 hover:text-primary' }}">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-3 {{ request()->routeIs('finance.*') ? 'text-white' : 'text-gray-400 group-hover:text-primary' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Finance
                            </div>
                            <svg class="w-4 h-4 transform transition-transform {{ request()->routeIs('finance.*') ? 'text-white' : 'text-gray-400' }}" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <ul x-show="open" x-cloak x-transition class="mt-2 space-y-1 pl-11 pr-2 pb-2 border-l-2 border-blue-100 ml-6">
                            <li>
                                <a href="{{ route('finance.overview') }}" class="block px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('finance.overview') ? 'text-primary font-bold bg-blue-50' : 'text-gray-500 hover:text-primary hover:bg-gray-50' }}">
                                    Overview
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('finance.bank-accounts') }}" class="block px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('finance.bank-accounts') ? 'text-primary font-bold bg-blue-50' : 'text-gray-500 hover:text-primary hover:bg-gray-50' }}">
                                    Bank Account
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('finance.transactions') }}" class="block px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('finance.transactions') ? 'text-primary font-bold bg-blue-50' : 'text-gray-500 hover:text-primary hover:bg-gray-50' }}">
                                    Transaksi
                                </a>
                            </li>
                            <li>
                                <span class="block px-3 py-2 text-sm text-gray-400 cursor-not-allowed italic">
                                    Account (Soon)
                                </span>
                            </li>
                        </ul>
                    </li>
                    <li>
                        <a href="{{ route('settings.index') }}"
                            class="flex items-center px-4 py-2.5 rounded-lg group transition-all duration-200 {{ request()->routeIs('settings.*') ? 'bg-primary text-white font-semibold shadow-md' : 'text-gray-600 hover:bg-blue-50 hover:text-primary' }}">
                            <svg class="w-5 h-5 mr-3 {{ request()->routeIs('settings.*') ? 'text-white' : 'text-gray-400 group-hover:text-primary' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            Settings
                        </a>
                    </li>
                    @if(auth()->user()->role == 'superadmin')
                    <li>
                        <a href="{{ route('users.index') }}"
                            class="flex items-center px-4 py-2.5 rounded-lg group transition-all duration-200 {{ request()->routeIs('users.*') ? 'bg-primary text-white font-semibold shadow-md' : 'text-gray-600 hover:bg-blue-50 hover:text-primary' }}">
                            <svg class="w-5 h-5 mr-3 {{ request()->routeIs('users.*') ? 'text-white' : 'text-gray-400 group-hover:text-primary' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                            User Management
                        </a>
                    </li>
                    @endif
                </ul>
            </nav>

            <div class="p-4 border-t border-gray-200">
                <div class="flex flex-col space-y-3">
                    <a href="{{ route('profile') }}" class="flex items-center px-2 hover:bg-gray-50 p-1 rounded-lg transition-colors group">
                        <div class="flex-shrink-0">
                            <div class="h-8 w-8 rounded-full bg-primary flex items-center justify-center text-white font-bold text-xs uppercase shadow-sm group-hover:scale-110 transition-transform">
                                {{ substr(auth()->user()->name, 0, 1) }}
                            </div>
                        </div>
                        <div class="ml-3 overflow-hidden">
                            <p class="text-xs font-bold text-gray-800 truncate group-hover:text-primary transition-colors">{{ auth()->user()->name }}</p>
                            <p class="text-[10px] text-gray-500 truncate lowercase">{{ auth()->user()->email }}</p>
                        </div>
                    </a>
                    
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full flex items-center px-4 py-2 text-sm font-bold text-red-600 hover:bg-red-50 rounded-md transition-colors group">
                            <svg class="w-4 h-4 mr-3 text-red-400 group-hover:text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                            Keluar Sistem
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden">
            <!-- Top Header (Mobile) -->
            <header class="flex items-center justify-between px-4 py-3 bg-gradient-to-r from-primary to-blue-700 text-white shadow-md md:hidden z-30 flex-shrink-0">
                <div class="flex items-center space-x-2">
                    <button @click="mobileMenuOpen = true" class="text-white hover:text-blue-100 p-1 rounded-lg focus:outline-none transition-colors" aria-label="Open Sidebar Menu">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <span class="text-base font-extrabold tracking-wide">WIRO APP</span>
                </div>
                
                <div class="flex items-center space-x-2">
                    <a href="{{ route('profile') }}" class="w-7 h-7 rounded-full bg-white/20 flex items-center justify-center text-xs font-bold text-white border border-white/30">
                        {{ substr(auth()->user()->name ?? 'U', 0, 1) }}
                    </a>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gradient-to-br from-indigo-50/90 via-blue-50/60 to-teal-50/70 p-3 sm:p-4 md:p-6 relative">
                <!-- Notification Toast (Auto-dismiss) -->
                @if(session('success') || session('error'))
                    <div x-data="{ show: true }" 
                         x-show="show" 
                         x-init="setTimeout(() => show = false, 4000)"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 transform translate-x-8"
                         x-transition:enter-end="opacity-100 transform translate-x-0"
                         x-transition:leave="transition ease-in duration-300"
                         x-transition:leave-start="opacity-100 transform translate-x-0"
                         x-transition:leave-end="opacity-0 transform translate-x-8"
                         class="fixed top-4 right-4 sm:top-6 sm:right-6 z-[60] min-w-[280px] sm:min-w-[320px] max-w-md bg-white shadow-2xl rounded-xl border-l-4 {{ session('success') ? 'border-green-500' : 'border-red-500' }} p-3 sm:p-4 pointer-events-auto"
                         style="display: none;">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 pt-0.5">
                                @if(session('success'))
                                    <svg class="h-5 w-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                @else
                                    <svg class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                @endif
                            </div>
                            <div class="ml-3 flex-1">
                                <p class="text-xs sm:text-sm font-bold text-gray-900 leading-none">
                                    {{ session('success') ? 'Sukses!' : 'Perhatian!' }}
                                </p>
                                <p class="mt-1 text-xs sm:text-sm text-gray-500">
                                    {{ session('success') ?? session('error') }}
                                </p>
                            </div>
                            <div class="ml-3 flex-shrink-0 flex">
                                <button @click="show = false" class="inline-flex text-gray-400 hover:text-gray-500 focus:outline-none transition duration-150 ease-in-out">
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>

</html>