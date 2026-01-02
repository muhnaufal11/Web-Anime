<div x-data="{ progress: 0, active: false }"
     x-on:livewire-upload-start="active = true; progress = 0"
     x-on:livewire-upload-finish="progress = 100; setTimeout(() => active = false, 800)"
     x-on:livewire-upload-error="active = false"
     x-on:livewire-upload-progress="progress = $event.detail.progress"
     class="w-full mb-2">
    <div x-show="active" class="flex items-center gap-2 text-sm text-white/80">
        <div class="w-40 h-2 bg-white/10 rounded-full overflow-hidden">
            <div class="h-full bg-gradient-to-r from-red-500 to-red-600" :style="`width: ${progress}%;`"></div>
        </div>
        <span class="font-semibold" x-text="progress + '%'">
        </span>
    </div>
</div>
