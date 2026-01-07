<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance - NipNime</title>
    <link rel="icon" href="/favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes pulse-slow {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .animate-pulse-slow {
            animation: pulse-slow 3s ease-in-out infinite;
        }
        @keyframes spin-slow {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .animate-spin-slow {
            animation: spin-slow 8s linear infinite;
        }
    </style>
</head>
<body class="bg-gray-900 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-lg w-full text-center">
        <!-- Logo/Icon -->
        <div class="mb-8">
            <div class="inline-block relative">
                <div class="w-32 h-32 bg-gradient-to-br from-orange-500 to-red-600 rounded-full flex items-center justify-center animate-pulse-slow">
                    <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </div>
                <div class="absolute -top-2 -right-2 w-8 h-8 bg-yellow-500 rounded-full flex items-center justify-center animate-spin-slow">
                    <svg class="w-5 h-5 text-yellow-900" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Title -->
        <h1 class="text-4xl font-bold text-white mb-4">
            🔧 Sedang Maintenance
        </h1>

        <!-- Message -->
        <p class="text-gray-300 text-lg mb-6">
            {{ $message ?? 'Website sedang dalam perbaikan untuk memberikan pengalaman yang lebih baik.' }}
        </p>

        <!-- Estimated Time -->
        @if(!empty($estimated_time))
        <div class="bg-gray-800 rounded-xl p-4 mb-6 inline-block">
            <div class="flex items-center gap-3 text-gray-300">
                <svg class="w-6 h-6 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>Estimasi selesai: <strong class="text-white">{{ $estimated_time }}</strong></span>
            </div>
        </div>
        @endif

        <!-- Status Box -->
        <div class="bg-gray-800/50 rounded-xl p-6 mb-8">
            <div class="flex items-center justify-center gap-2 mb-4">
                <span class="w-3 h-3 bg-yellow-500 rounded-full animate-pulse"></span>
                <span class="text-yellow-500 font-medium">Maintenance Mode Active</span>
            </div>
            
            @if(!empty($updated_at))
            <p class="text-gray-500 text-sm">
                Dimulai: {{ $updated_at }}
            </p>
            @endif
        </div>

        <!-- Social Links -->
        <div class="flex items-center justify-center gap-4 mb-8">
            <a href="https://discord.gg/nipnime" target="_blank" class="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028 14.09 14.09 0 0 0 1.226-1.994.076.076 0 0 0-.041-.106 13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.946 2.418-2.157 2.418z"/>
                </svg>
                Join Discord
            </a>
        </div>

        <!-- Footer -->
        <p class="text-gray-600 text-sm">
            &copy; {{ date('Y') }} NipNime. Kami akan segera kembali!
        </p>
    </div>

    <!-- Background Decoration -->
    <div class="fixed inset-0 -z-10 overflow-hidden">
        <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-orange-500/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-red-500/10 rounded-full blur-3xl"></div>
    </div>
</body>
</html>
