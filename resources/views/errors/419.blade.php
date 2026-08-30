<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="3;url={{ route('login') }}">
    <title>Sesi Berakhir | WiroManagement</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .glass { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); }
        .bg-gradient { background: radial-gradient(circle at top left, #2563eb, #1e40af, #1e3a8a, #0f172a); }
    </style>
</head>
<body class="bg-gradient min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full text-center">
        <div class="glass border border-white/30 rounded-[2.5rem] shadow-2xl p-8 sm:p-10 animate-in fade-in zoom-in duration-500">
            <div class="w-16 h-16 bg-amber-100 text-amber-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-sm">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>

            <h1 class="text-2xl font-bold text-gray-800 mb-2">Sesi Telah Berakhir (419)</h1>
            <p class="text-gray-600 text-sm mb-6 leading-relaxed">
                Token keamanan sesi Anda telah kedaluwarsa. Sistem telah mereset sesi lama demi keamanan akun Anda.
            </p>

            <div class="p-3 bg-blue-50 border border-blue-100 rounded-xl mb-6 text-xs text-blue-700 font-medium">
                Mengalihkan otomatis ke halaman login dalam <span id="countdown" class="font-bold">3</span> detik...
            </div>

            <a href="{{ route('login') }}" 
                class="inline-flex items-center justify-center w-full py-3.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold text-sm shadow-lg shadow-blue-200 transition-all duration-200">
                <span>Kembali ke Halaman Login</span>
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                </svg>
            </a>
        </div>

        <p class="mt-8 text-blue-200/60 text-xs font-semibold">
            &copy; {{ date('Y') }} WiroManagement &bull; Keamanan Sesi Terjamin
        </p>
    </div>

    <script>
        let seconds = 3;
        const countdownEl = document.getElementById('countdown');
        const timer = setInterval(() => {
            seconds--;
            if (countdownEl) countdownEl.textContent = seconds;
            if (seconds <= 0) {
                clearInterval(timer);
                window.location.href = "{{ route('login') }}";
            }
        }, 1000);
    </script>
</body>
</html>
