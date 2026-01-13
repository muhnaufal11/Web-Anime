@extends('admin.layouts.app')

@section('title', 'Edit Iklan')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.advertisements.index') }}" class="p-2 hover:bg-white/10 rounded-lg transition">
            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-white">Edit Iklan</h1>
            <p class="text-gray-400 text-sm mt-1">{{ $ad->name }}</p>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-3 gap-4">
        <div class="bg-[#1a1d24] rounded-xl p-4 border border-white/10 text-center">
            <p class="text-2xl font-bold text-white">{{ number_format($ad->impressions) }}</p>
            <p class="text-gray-500 text-sm">Impressions</p>
        </div>
        <div class="bg-[#1a1d24] rounded-xl p-4 border border-white/10 text-center">
            <p class="text-2xl font-bold text-white">{{ number_format($ad->clicks) }}</p>
            <p class="text-gray-500 text-sm">Clicks</p>
        </div>
        <div class="bg-[#1a1d24] rounded-xl p-4 border border-white/10 text-center">
            <p class="text-2xl font-bold text-{{ $ad->ctr >= 1 ? 'green' : ($ad->ctr >= 0.5 ? 'yellow' : 'red') }}-400">{{ $ad->ctr }}%</p>
            <p class="text-gray-500 text-sm">CTR</p>
        </div>
    </div>

    <form action="{{ route('admin.advertisements.update', $ad) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Basic Info -->
        <div class="bg-[#1a1d24] rounded-xl p-6 border border-white/10 space-y-4">
            <h2 class="text-lg font-bold text-white mb-4">📋 Informasi Dasar</h2>
            
            <div>
                <label class="block text-sm font-bold text-gray-300 mb-2">Nama Iklan *</label>
                <input type="text" name="name" value="{{ old('name', $ad->name) }}" required
                       class="w-full bg-[#0f1115] border border-white/10 rounded-lg px-4 py-3 text-white focus:border-red-500 focus:ring-1 focus:ring-red-500">
                @error('name')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold text-gray-300 mb-2">Posisi *</label>
                    <select name="position" required
                            class="w-full bg-[#0f1115] border border-white/10 rounded-lg px-4 py-3 text-white focus:border-red-500 focus:ring-1 focus:ring-red-500">
                        @foreach($positions as $key => $label)
                            <option value="{{ $key }}" {{ old('position', $ad->position) == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-300 mb-2">Tipe Iklan *</label>
                    <select name="type" id="adType" required onchange="toggleAdFields()"
                            class="w-full bg-[#0f1115] border border-white/10 rounded-lg px-4 py-3 text-white focus:border-red-500 focus:ring-1 focus:ring-red-500">
                        @foreach($types as $key => $label)
                            <option value="{{ $key }}" {{ old('type', $ad->type) == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-300 mb-2">Ukuran</label>
                <select name="size"
                        class="w-full bg-[#0f1115] border border-white/10 rounded-lg px-4 py-3 text-white focus:border-red-500 focus:ring-1 focus:ring-red-500">
                    @foreach($sizes as $key => $label)
                        <option value="{{ $key }}" {{ old('size', $ad->size) == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Ad Content -->
        <div class="bg-[#1a1d24] rounded-xl p-6 border border-white/10 space-y-4">
            <h2 class="text-lg font-bold text-white mb-4">📝 Konten Iklan</h2>

            <!-- Code (for adsense/custom/html) -->
            <div id="codeField">
                <label class="block text-sm font-bold text-gray-300 mb-2">Kode Iklan</label>
                <textarea name="code" rows="6"
                          class="w-full bg-[#0f1115] border border-white/10 rounded-lg px-4 py-3 text-white font-mono text-sm focus:border-red-500 focus:ring-1 focus:ring-red-500"
                          placeholder="Paste kode AdSense atau HTML custom di sini...">{{ old('code', $ad->code) }}</textarea>
            </div>

            <!-- Image Upload (for image type) -->
            <div id="imageField" class="hidden">
                @if($ad->image_path)
                <div class="mb-4">
                    <p class="text-sm font-bold text-gray-300 mb-2">Gambar Saat Ini:</p>
                    <img src="{{ asset('storage/' . $ad->image_path) }}" alt="{{ $ad->name }}" class="max-w-xs rounded-lg">
                </div>
                @endif
                <label class="block text-sm font-bold text-gray-300 mb-2">Ganti Gambar</label>
                <input type="file" name="image" accept="image/*"
                       class="w-full bg-[#0f1115] border border-white/10 rounded-lg px-4 py-3 text-white file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:bg-red-600 file:text-white file:font-bold">
            </div>

            <!-- Link (for image type) -->
            <div id="linkField" class="hidden">
                <label class="block text-sm font-bold text-gray-300 mb-2">Link Target</label>
                <input type="url" name="link" value="{{ old('link', $ad->link) }}"
                       class="w-full bg-[#0f1115] border border-white/10 rounded-lg px-4 py-3 text-white focus:border-red-500 focus:ring-1 focus:ring-red-500"
                       placeholder="https://example.com">
            </div>
        </div>

        <!-- Display Settings -->
        <div class="bg-[#1a1d24] rounded-xl p-6 border border-white/10 space-y-4">
            <h2 class="text-lg font-bold text-white mb-4">⚙️ Pengaturan Tampilan</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold text-gray-300 mb-2">Urutan (Order)</label>
                    <input type="number" name="order" value="{{ old('order', $ad->order) }}" min="0"
                           class="w-full bg-[#0f1115] border border-white/10 rounded-lg px-4 py-3 text-white focus:border-red-500 focus:ring-1 focus:ring-red-500">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold text-gray-300 mb-2">Tanggal Mulai</label>
                    <input type="datetime-local" name="start_date" 
                           value="{{ old('start_date', $ad->start_date ? $ad->start_date->format('Y-m-d\TH:i') : '') }}"
                           class="w-full bg-[#0f1115] border border-white/10 rounded-lg px-4 py-3 text-white focus:border-red-500 focus:ring-1 focus:ring-red-500">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-300 mb-2">Tanggal Berakhir</label>
                    <input type="datetime-local" name="end_date" 
                           value="{{ old('end_date', $ad->end_date ? $ad->end_date->format('Y-m-d\TH:i') : '') }}"
                           class="w-full bg-[#0f1115] border border-white/10 rounded-lg px-4 py-3 text-white focus:border-red-500 focus:ring-1 focus:ring-red-500">
                </div>
            </div>

            <div class="flex flex-wrap gap-6">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ $ad->is_active ? 'checked' : '' }}
                           class="w-5 h-5 rounded bg-[#0f1115] border-white/20 text-red-600 focus:ring-red-500">
                    <span class="text-gray-300 font-bold">Aktif</span>
                </label>
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="show_on_mobile" value="1" {{ $ad->show_on_mobile ? 'checked' : '' }}
                           class="w-5 h-5 rounded bg-[#0f1115] border-white/20 text-red-600 focus:ring-red-500">
                    <span class="text-gray-300 font-bold">Tampil di Mobile</span>
                </label>
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="show_on_desktop" value="1" {{ $ad->show_on_desktop ? 'checked' : '' }}
                           class="w-5 h-5 rounded bg-[#0f1115] border-white/20 text-red-600 focus:ring-red-500">
                    <span class="text-gray-300 font-bold">Tampil di Desktop</span>
                </label>
            </div>
        </div>

        <!-- Submit -->
        <div class="flex items-center justify-end gap-4">
            <a href="{{ route('admin.advertisements.index') }}" class="px-6 py-3 bg-white/10 hover:bg-white/20 text-white rounded-lg font-bold transition">
                Batal
            </a>
            <button type="submit" class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg font-bold transition">
                💾 Update Iklan
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function toggleAdFields() {
    const type = document.getElementById('adType').value;
    const codeField = document.getElementById('codeField');
    const imageField = document.getElementById('imageField');
    const linkField = document.getElementById('linkField');

    if (type === 'image') {
        codeField.classList.add('hidden');
        imageField.classList.remove('hidden');
        linkField.classList.remove('hidden');
    } else {
        codeField.classList.remove('hidden');
        imageField.classList.add('hidden');
        linkField.classList.add('hidden');
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', toggleAdFields);
</script>
@endpush
@endsection
