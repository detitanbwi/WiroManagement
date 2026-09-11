<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Access Denied | Wirodev Internal</title>
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
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 min-h-screen flex items-center justify-center p-4 text-slate-100 antialiased">
    <div class="max-w-lg w-full text-center">
        <!-- Shield Icon -->
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-3xl bg-rose-500/10 border border-rose-500/20 text-rose-400 mb-6 shadow-2xl shadow-rose-900/40">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
        </div>

        <div class="inline-block px-3 py-1 rounded-full bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs font-bold uppercase tracking-widest mb-3">
            Error 403 &bull; Restricted Access
        </div>

        <h1 class="text-3xl font-black tracking-tight text-white mb-3">
            Access Denied
        </h1>

        <p class="text-slate-400 text-sm leading-relaxed mb-8 max-w-md mx-auto">
            {{ $exception?->getMessage() ?: 'Your account does not have the required role or permissions to access this module/page.' }}
        </p>

        @auth
        <div class="bg-slate-800/60 border border-slate-700/60 rounded-2xl p-4 mb-8 text-left max-w-md mx-auto backdrop-blur-xs">
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Account Information</div>
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-bold text-white">{{ auth()->user()->name }}</div>
                    <div class="text-xs text-slate-400">{{ auth()->user()->email }}</div>
                </div>
                <div class="flex flex-wrap gap-1 justify-end max-w-[180px]">
                    @foreach(auth()->user()->role_badges as $badge)
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $badge['classes'] }}">
                            {{ $badge['name'] }}
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
        @endauth

        <div class="flex items-center justify-center gap-3">
            <a href="{{ route('dashboard') }}" class="inline-flex items-center px-5 py-2.5 rounded-xl bg-primary hover:bg-blue-700 text-white font-bold text-xs uppercase tracking-wider shadow-lg shadow-blue-900/50 transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Back to Dashboard
            </a>

            <a href="javascript:history.back()" class="inline-flex items-center px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-bold text-xs uppercase tracking-wider transition">
                Previous Page
            </a>
        </div>

        <div class="mt-12 text-slate-500 text-xs">
            &copy; 2026 Wirodev Ecosystem. Please contact a Super Admin if you need role escalation.
        </div>
    </div>
</body>
</html>
