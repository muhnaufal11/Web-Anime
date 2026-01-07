@extends('admin.layouts.app')

@section('title', 'Statistik Iklan')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.advertisements.index') }}" class="p-2 hover:bg-white/10 rounded-lg transition">
            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-white">Statistik Iklan</h1>
            <p class="text-gray-400 text-sm mt-1">Overview performa semua iklan</p>
        </div>
    </div>

    <!-- Overall Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-blue-600/20 to-blue-800/20 rounded-xl p-6 border border-blue-500/30">
            <div class="text-3xl font-black text-white">{{ number_format($stats['total']) }}</div>
            <p class="text-blue-400 text-sm font-bold mt-1">Total Iklan</p>
        </div>
        <div class="bg-gradient-to-br from-green-600/20 to-green-800/20 rounded-xl p-6 border border-green-500/30">
            <div class="text-3xl font-black text-white">{{ number_format($stats['active']) }}</div>
            <p class="text-green-400 text-sm font-bold mt-1">Iklan Aktif</p>
        </div>
        <div class="bg-gradient-to-br from-purple-600/20 to-purple-800/20 rounded-xl p-6 border border-purple-500/30">
            <div class="text-3xl font-black text-white">{{ number_format($stats['total_impressions']) }}</div>
            <p class="text-purple-400 text-sm font-bold mt-1">Total Impressions</p>
        </div>
        <div class="bg-gradient-to-br from-red-600/20 to-red-800/20 rounded-xl p-6 border border-red-500/30">
            <div class="text-3xl font-black text-white">{{ number_format($stats['total_clicks']) }}</div>
            <p class="text-red-400 text-sm font-bold mt-1">Total Clicks</p>
        </div>
    </div>

    <!-- Stats by Position -->
    <div class="bg-[#1a1d24] rounded-xl border border-white/10 overflow-hidden">
        <div class="px-6 py-4 border-b border-white/10">
            <h2 class="text-lg font-bold text-white">📊 Statistik per Posisi</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-[#0f1115]">
                    <tr>
                        <th class="text-left text-xs font-bold text-gray-400 uppercase tracking-wider px-6 py-4">Posisi</th>
                        <th class="text-center text-xs font-bold text-gray-400 uppercase tracking-wider px-6 py-4">Jumlah Iklan</th>
                        <th class="text-center text-xs font-bold text-gray-400 uppercase tracking-wider px-6 py-4">Impressions</th>
                        <th class="text-center text-xs font-bold text-gray-400 uppercase tracking-wider px-6 py-4">Clicks</th>
                        <th class="text-center text-xs font-bold text-gray-400 uppercase tracking-wider px-6 py-4">CTR</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @php
                        $positions = \App\Models\Advertisement::POSITIONS;
                    @endphp
                    @foreach($stats['by_position'] as $stat)
                    <tr class="hover:bg-white/5 transition">
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 bg-blue-600/20 text-blue-400 text-sm font-bold rounded">
                                {{ $positions[$stat->position] ?? $stat->position }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center text-white font-bold">
                            {{ $stat->count }}
                        </td>
                        <td class="px-6 py-4 text-center text-gray-300">
                            {{ number_format($stat->impressions) }}
                        </td>
                        <td class="px-6 py-4 text-center text-gray-300">
                            {{ number_format($stat->clicks) }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            @php
                                $ctr = $stat->impressions > 0 ? round(($stat->clicks / $stat->impressions) * 100, 2) : 0;
                            @endphp
                            <span class="text-{{ $ctr >= 1 ? 'green' : ($ctr >= 0.5 ? 'yellow' : 'red') }}-400 font-bold">
                                {{ $ctr }}%
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tips -->
    <div class="bg-gradient-to-r from-yellow-600/10 to-orange-600/10 rounded-xl p-6 border border-yellow-500/30">
        <h3 class="text-lg font-bold text-yellow-400 mb-3">💡 Tips Optimasi Iklan</h3>
        <ul class="text-gray-300 space-y-2 text-sm">
            <li>• CTR (Click Through Rate) yang baik untuk iklan display adalah 0.5% - 1%</li>
            <li>• Posisi "content_middle" biasanya memiliki CTR tertinggi</li>
            <li>• Hindari terlalu banyak iklan yang dapat mengganggu user experience</li>
            <li>• Gunakan ukuran responsif untuk hasil terbaik di mobile</li>
            <li>• Test berbagai posisi dan format untuk menemukan kombinasi terbaik</li>
        </ul>
    </div>
</div>
@endsection
