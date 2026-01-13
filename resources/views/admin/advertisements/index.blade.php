@extends('admin.layouts.app')

@section('title', 'Kelola Iklan')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white">Kelola Iklan</h1>
            <p class="text-gray-400 text-sm mt-1">Atur iklan AdSense dan custom untuk website</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.advertisements.stats') }}" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-bold transition flex items-center gap-2">
                📊 Statistik
            </a>
            <a href="{{ route('admin.advertisements.create') }}" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-bold transition flex items-center gap-2">
                ➕ Tambah Iklan
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-[#1a1d24] rounded-xl p-4 border border-white/10">
        <form method="GET" class="flex flex-wrap gap-4">
            <select name="position" class="bg-[#0f1115] border border-white/10 rounded-lg px-4 py-2 text-white text-sm">
                <option value="">Semua Posisi</option>
                @foreach($positions as $key => $label)
                    <option value="{{ $key }}" {{ request('position') == $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <select name="type" class="bg-[#0f1115] border border-white/10 rounded-lg px-4 py-2 text-white text-sm">
                <option value="">Semua Tipe</option>
                @foreach($types as $key => $label)
                    <option value="{{ $key }}" {{ request('type') == $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <select name="status" class="bg-[#0f1115] border border-white/10 rounded-lg px-4 py-2 text-white text-sm">
                <option value="">Semua Status</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Nonaktif</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-white/10 hover:bg-white/20 text-white rounded-lg font-bold transition">
                🔍 Filter
            </button>
            <a href="{{ route('admin.advertisements.index') }}" class="px-4 py-2 bg-white/5 hover:bg-white/10 text-gray-400 rounded-lg transition">
                Reset
            </a>
        </form>
    </div>

    <!-- Ads Table -->
    <div class="bg-[#1a1d24] rounded-xl border border-white/10 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-[#0f1115]">
                    <tr>
                        <th class="text-left text-xs font-bold text-gray-400 uppercase tracking-wider px-6 py-4">Nama</th>
                        <th class="text-left text-xs font-bold text-gray-400 uppercase tracking-wider px-6 py-4">Posisi</th>
                        <th class="text-left text-xs font-bold text-gray-400 uppercase tracking-wider px-6 py-4">Tipe</th>
                        <th class="text-center text-xs font-bold text-gray-400 uppercase tracking-wider px-6 py-4">Impressions</th>
                        <th class="text-center text-xs font-bold text-gray-400 uppercase tracking-wider px-6 py-4">Clicks</th>
                        <th class="text-center text-xs font-bold text-gray-400 uppercase tracking-wider px-6 py-4">CTR</th>
                        <th class="text-center text-xs font-bold text-gray-400 uppercase tracking-wider px-6 py-4">Status</th>
                        <th class="text-right text-xs font-bold text-gray-400 uppercase tracking-wider px-6 py-4">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($ads as $ad)
                    <tr class="hover:bg-white/5 transition">
                        <td class="px-6 py-4">
                            <div>
                                <p class="font-bold text-white">{{ $ad->name }}</p>
                                <p class="text-xs text-gray-500">{{ $ad->size ?? 'Responsive' }}</p>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 bg-blue-600/20 text-blue-400 text-xs font-bold rounded">
                                {{ $positions[$ad->position] ?? $ad->position }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 bg-purple-600/20 text-purple-400 text-xs font-bold rounded">
                                {{ $types[$ad->type] ?? $ad->type }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center text-gray-300">
                            {{ number_format($ad->impressions) }}
                        </td>
                        <td class="px-6 py-4 text-center text-gray-300">
                            {{ number_format($ad->clicks) }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="text-{{ $ad->ctr >= 1 ? 'green' : ($ad->ctr >= 0.5 ? 'yellow' : 'red') }}-400 font-bold">
                                {{ $ad->ctr }}%
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <button onclick="toggleAd({{ $ad->id }})" 
                                    id="toggle-{{ $ad->id }}"
                                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $ad->is_active ? 'bg-green-600' : 'bg-gray-600' }}">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $ad->is_active ? 'translate-x-6' : 'translate-x-1' }}"></span>
                            </button>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.advertisements.edit', $ad) }}" 
                                   class="p-2 text-gray-400 hover:text-white hover:bg-white/10 rounded-lg transition">
                                    ✏️
                                </a>
                                <form action="{{ route('admin.advertisements.destroy', $ad) }}" method="POST" 
                                      onsubmit="return confirm('Yakin ingin menghapus iklan ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 text-gray-400 hover:text-red-500 hover:bg-white/10 rounded-lg transition">
                                        🗑️
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                            <div class="text-4xl mb-2">📭</div>
                            <p>Belum ada iklan</p>
                            <a href="{{ route('admin.advertisements.create') }}" class="text-red-500 hover:underline mt-2 inline-block">
                                Tambah iklan pertama →
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($ads->hasPages())
        <div class="px-6 py-4 border-t border-white/10">
            {{ $ads->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
function toggleAd(id) {
    fetch(`/admin/advertisements/${id}/toggle`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const btn = document.getElementById(`toggle-${id}`);
            const span = btn.querySelector('span');
            if (data.is_active) {
                btn.classList.remove('bg-gray-600');
                btn.classList.add('bg-green-600');
                span.classList.remove('translate-x-1');
                span.classList.add('translate-x-6');
            } else {
                btn.classList.remove('bg-green-600');
                btn.classList.add('bg-gray-600');
                span.classList.remove('translate-x-6');
                span.classList.add('translate-x-1');
            }
        }
    });
}
</script>
@endpush
@endsection
