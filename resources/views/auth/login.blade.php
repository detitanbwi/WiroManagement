<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | WiroManagement</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .glass { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); }
        .bg-gradient { background: radial-gradient(circle at top left, #2563eb, #1e40af, #1e3a8a, #0f172a); }
    </style>
</head>
<body class="bg-gradient min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <!-- Logo / Brand -->
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-24 h-24 bg-white rounded-3xl shadow-2xl mb-6 transform -rotate-6 hover:rotate-0 transition-transform duration-500 p-4">
                <img src="{{ asset('logo.png') }}" alt="Wirodayan Logo" class="w-full h-auto object-contain">
            </div>
            <h1 class="text-3xl font-extrabold text-white tracking-tight">WiroManagement</h1>
            <p class="text-blue-200 mt-2 text-sm opacity-80">Portal Administrasi Client & Proyek</p>
        </div>

        <div class="glass border border-white/30 rounded-[2.5rem] shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-700">
            <div class="p-8 sm:p-12">
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Selamat Datang</h2>
                <p class="text-gray-500 text-sm mb-8">Silakan masukkan akun Anda untuk melanjutkan.</p>

                @if(session('error'))
                <div class="mb-6 p-4 bg-red-100 border border-red-200 text-red-600 rounded-2xl flex items-center shadow-sm">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span class="text-sm font-medium">{{ session('error') }}</span>
                </div>
                @endif

                <form action="{{ route('login.post') }}" method="POST" class="space-y-6">
                    @csrf
                    
                    <div>
                        <label for="email" class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2 ml-1">Email Address</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 group-focus-within:text-blue-500 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path></svg>
                            </div>
                            <input type="email" name="email" id="email" required value="{{ old('email') }}"
                                class="block w-full pl-12 pr-4 py-4 bg-white/50 border border-gray-200 rounded-2xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 outline-none transition-all duration-300 text-gray-700 placeholder-gray-400 shadow-sm"
                                placeholder="nama@email.com">
                        </div>
                        @error('email') <p class="mt-2 text-xs text-red-600 font-medium italic">* {{ $message }}</p> @enderror
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <label for="password" class="block text-xs font-bold text-gray-400 uppercase tracking-widest ml-1">Secret Key</label>
                        </div>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 group-focus-within:text-blue-500 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                            </div>
                            <input type="password" name="password" id="password" required
                                class="block w-full pl-12 pr-4 py-4 bg-white/50 border border-gray-200 rounded-2xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 outline-none transition-all duration-300 text-gray-700 placeholder-gray-400 shadow-sm"
                                placeholder="••••••••">
                        </div>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="remember" id="remember" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <label for="remember" class="ml-2 text-xs font-bold text-gray-500 uppercase tracking-tight cursor-pointer select-none">Ingat saya di perangkat ini</label>
                    </div>

                    <button type="submit" 
                        class="w-full py-4 bg-blue-600 hover:bg-blue-700 text-white rounded-2xl font-bold uppercase tracking-widest text-sm shadow-xl shadow-blue-200 transform hover:-translate-y-1 active:translate-y-0 active:shadow-inner transition-all duration-300 flex items-center justify-center space-x-2">
                        <span>Masuk ke Dashboard</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </button>
                </form>
            </div>
        </div>

        <p class="text-center mt-12 text-blue-200/60 text-[10px] font-bold uppercase tracking-[0.2em]">
            &copy; 2026 Wirodayan Digital &bull; All Rights Reserved
        </p>
    </div>
</body>
</html>
