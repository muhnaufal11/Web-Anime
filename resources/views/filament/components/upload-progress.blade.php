@props(['target' => 'video_files'])
<div x-data="{ progress: 0, active: false }"
     x-on:livewire-upload-start.window="active = true; progress = 0"
     x-on:livewire-upload-finish.window="progress = 100; setTimeout(() => active = false, 800)"
     x-on:livewire-upload-error.window="active = false"
     x-on:livewire-upload-progress.window="progress = $event.detail.progress"
     class="w-full mb-3">
    <div wire:loading wire:target="{{ $target }}" x-show="active" x-transition class="flex items-center gap-3 text-sm text-white/90">
        <div class="flex-1 h-2 bg-white/10 rounded-full overflow-hidden">
            <div class="h-full bg-gradient-to-r from-red-500 to-red-600" :style="`width: ${progress}%;`"></div>
        </div>
        <span class="font-semibold min-w-[3ch] text-right" x-text="progress + '%'">0%</span>
        <span class="text-white/70">Uploading…</span>
    </div>
    <div wire:loading.remove wire:target="{{ $target }}" class="text-xs text-white/40">Menunggu upload file…</div>
</div>
