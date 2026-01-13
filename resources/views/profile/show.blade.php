@extends('layouts.app')
@section('title', 'Profil Saya')

@section('content')
<div class="min-h-screen bg-gradient-to-b from-[#0f1115] via-[#0f1115] to-[#1a1d24]">
    <!-- Header -->
    <div class="max-w-4xl mx-auto px-4 py-8">
        <div class="mb-8">
            <h1 class="text-4xl font-black text-white uppercase tracking-tighter">👤 Profil Saya</h1>
            <p class="text-gray-400 mt-2">Kelola informasi akun dan preferensi Anda</p>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 pb-20">
        @if(session('success'))
            <div class="mb-6 p-4 bg-green-500/20 border border-green-500/50 rounded-xl">
                <p class="text-green-400 font-bold">✓ {{ session('success') }}</p>
            </div>
        @endif

        <!-- Profile Header Card -->
        <div class="bg-gradient-to-br from-[#1a1d24] to-[#0f1115] rounded-3xl p-8 border border-white/10 mb-8 shadow-xl">
            <div class="flex flex-col md:flex-row items-center md:items-start gap-8">
                <!-- Avatar -->
                <div class="relative">
                    <div class="w-32 h-32 md:w-40 md:h-40 rounded-2xl overflow-hidden border-4 border-red-600/50 bg-gradient-to-br from-[#1a1d24] to-[#0f1115] flex items-center justify-center flex-shrink-0 shadow-lg">
                        @if(auth()->user()->avatar)
                            <img src="{{ asset('storage/' . auth()->user()->avatar) }}" 
                                 alt="{{ auth()->user()->name }}"
                                 class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full bg-gradient-to-br from-red-600 to-red-700 flex items-center justify-center">
                                <span class="text-white text-5xl font-black">{{ substr(auth()->user()->name, 0, 1) }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- User Info -->
                <div class="flex-1 text-center md:text-left">
                    <h2 class="text-3xl font-black text-white mb-2">{{ auth()->user()->name }}</h2>
                    <p class="text-gray-400 mb-4">{{ auth()->user()->email }}</p>
                    
                    <div class="flex flex-wrap gap-3 mb-6">
                        @if(auth()->user()->location)
                            <div class="flex items-center gap-2 bg-white/5 px-4 py-2 rounded-lg border border-white/10">
                                <span class="text-sm">📍 {{ auth()->user()->location }}</span>
                            </div>
                        @endif
                        @if(auth()->user()->phone)
                            <div class="flex items-center gap-2 bg-white/5 px-4 py-2 rounded-lg border border-white/10">
                                <span class="text-sm">📞 {{ auth()->user()->phone }}</span>
                            </div>
                        @endif
                        @if(auth()->user()->birth_date)
                            <div class="flex items-center gap-2 bg-white/5 px-4 py-2 rounded-lg border border-white/10">
                                <span class="text-sm">🎂 {{ auth()->user()->birth_date->format('d M Y') }}</span>
                            </div>
                        @endif
                    </div>

                    @if(auth()->user()->bio)
                        <p class="text-gray-400 italic">{{ auth()->user()->bio }}</p>
                    @endif
                </div>

                <!-- Edit Button -->
                <button onclick="toggleEditProfile()" class="px-8 py-3 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-bold rounded-xl transition-all shadow-lg shadow-red-600/30 hover:shadow-xl hover:shadow-red-600/40 uppercase tracking-wider whitespace-nowrap">
                    ✎ Edit Profil
                </button>
            </div>
        </div>

        <!-- Tabs -->
        <div class="flex gap-4 mb-8 border-b border-white/10">
            <button onclick="switchTab('edit-profile')" id="tab-edit-profile" 
                    class="px-6 py-4 font-bold uppercase tracking-widest text-sm border-b-2 border-red-600 text-red-500 transition">
                Edit Profil
            </button>
            <button onclick="switchTab('change-password')" id="tab-change-password" 
                    class="px-6 py-4 font-bold uppercase tracking-widest text-sm border-b-2 border-transparent text-gray-400 hover:text-white transition">
                Ganti Password
            </button>
        </div>

        <!-- Edit Profile Form -->
        <div id="edit-profile" class="tab-content">
            <div class="bg-gradient-to-br from-[#1a1d24] to-[#0f1115] rounded-3xl p-8 border border-white/10 shadow-xl">
                <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <!-- Name -->
                        <div class="md:col-span-1">
                            <label class="block text-sm font-bold text-gray-400 uppercase tracking-widest mb-3">Nama Lengkap</label>
                            <input type="text" name="name" value="{{ auth()->user()->name }}" required
                                   class="w-full bg-[#0f1115] border-2 border-white/10 text-white rounded-xl px-4 py-3 focus:border-red-600 focus:ring-2 focus:ring-red-600/20 transition-all @error('name') border-red-600 @enderror">
                            @error('name')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>

                        <!-- Email -->
                        <div class="md:col-span-1">
                            <label class="block text-sm font-bold text-gray-400 uppercase tracking-widest mb-3">Email</label>
                            <input type="email" name="email" value="{{ auth()->user()->email }}" required
                                   class="w-full bg-[#0f1115] border-2 border-white/10 text-white rounded-xl px-4 py-3 focus:border-red-600 focus:ring-2 focus:ring-red-600/20 transition-all @error('email') border-red-600 @enderror">
                            @error('email')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>

                        <!-- Phone -->
                        <div class="md:col-span-1">
                            <label class="block text-sm font-bold text-gray-400 uppercase tracking-widest mb-3">Nomor Telepon</label>
                            <input type="tel" name="phone" value="{{ auth()->user()->phone }}"
                                   class="w-full bg-[#0f1115] border-2 border-white/10 text-white rounded-xl px-4 py-3 focus:border-red-600 focus:ring-2 focus:ring-red-600/20 transition-all @error('phone') border-red-600 @enderror">
                            @error('phone')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>

                        <!-- Gender -->
                        <div class="md:col-span-1">
                            <label class="block text-sm font-bold text-gray-400 uppercase tracking-widest mb-3">Jenis Kelamin</label>
                            <select name="gender" class="w-full bg-[#0f1115] border-2 border-white/10 text-white rounded-xl px-4 py-3 focus:border-red-600 focus:ring-2 focus:ring-red-600/20 transition-all appearance-none cursor-pointer @error('gender') border-red-600 @enderror" style="background-image: url('data:image/svg+xml;utf8,<svg fill=\"none\" stroke=\"%23888888\" viewBox=\"0 0 24 24\" xmlns=\"http://www.w3.org/2000/svg\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M19 14l-7 7m0 0l-7-7m7 7V3\"></path></svg>'); background-repeat: no-repeat; background-position: right 0.5rem center; background-size: 1.5em 1.5em; padding-right: 2.5rem;">
                                <option value="">Pilih jenis kelamin</option>
                                <option value="male" @selected(auth()->user()->gender === 'male')>Pria</option>
                                <option value="female" @selected(auth()->user()->gender === 'female')>Wanita</option>
                                <option value="other" @selected(auth()->user()->gender === 'other')>Lainnya</option>
                            </select>
                            @error('gender')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>

                        <!-- Birth Date -->
                        <div class="md:col-span-1">
                            <label class="block text-sm font-bold text-gray-400 uppercase tracking-widest mb-3">Tanggal Lahir</label>
                            <input type="date" name="birth_date" value="{{ auth()->user()->birth_date }}"
                                   class="w-full bg-[#0f1115] border-2 border-white/10 text-white rounded-xl px-4 py-3 focus:border-red-600 focus:ring-2 focus:ring-red-600/20 transition-all @error('birth_date') border-red-600 @enderror">
                            @error('birth_date')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>

                        <!-- Location -->
                        <div class="md:col-span-1">
                            <label class="block text-sm font-bold text-gray-400 uppercase tracking-widest mb-3">Lokasi</label>
                            <input type="text" name="location" value="{{ auth()->user()->location }}"
                                   class="w-full bg-[#0f1115] border-2 border-white/10 text-white rounded-xl px-4 py-3 focus:border-red-600 focus:ring-2 focus:ring-red-600/20 transition-all @error('location') border-red-600 @enderror">
                            @error('location')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <!-- Bio -->
                    <div class="mb-8">
                        <label class="block text-sm font-bold text-gray-400 uppercase tracking-widest mb-3">Bio</label>
                        <textarea name="bio" rows="4" placeholder="Ceritakan tentang diri Anda..."
                                  class="w-full bg-[#0f1115] border-2 border-white/10 text-white rounded-xl px-4 py-3 focus:border-red-600 focus:ring-2 focus:ring-red-600/20 transition-all @error('bio') border-red-600 @enderror">{{ auth()->user()->bio }}</textarea>
                        @error('bio')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>

                    <!-- Avatar Upload -->
                    <div class="mb-8">
                        <label class="block text-sm font-bold text-gray-400 uppercase tracking-widest mb-3">Foto Profil</label>
                        <div class="relative">
                            <input type="file" name="avatar-raw" accept="image/*" id="avatar-input"
                                   class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                            <input type="hidden" name="avatar-cropped" id="avatar-cropped" value="">
                            <div class="w-full bg-[#0f1115] border-2 border-dashed border-white/20 rounded-xl px-6 py-12 text-center cursor-pointer hover:border-red-600/50 transition-all hover:bg-white/5">
                                <svg class="w-16 h-16 mx-auto text-gray-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
                                </svg>
                                <p class="text-gray-300 font-semibold">Drag & drop atau klik untuk upload</p>
                                <p class="text-gray-500 text-sm mt-2">Max 2MB (JPEG, PNG, JPG, GIF)</p>
                            </div>
                        </div>
                        @error('avatar')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>

                    <button type="submit" class="w-full px-6 py-4 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-black rounded-xl transition-all shadow-lg shadow-red-600/30 hover:shadow-xl hover:shadow-red-600/40 uppercase tracking-wider">
                        💾 Simpan Perubahan
                    </button>
                </form>
            </div>
        </div>

        <!-- Change Password Form -->
        <div id="change-password" class="tab-content hidden">
            <div class="bg-gradient-to-br from-[#1a1d24] to-[#0f1115] rounded-3xl p-8 border border-white/10 shadow-xl">
                <form action="{{ route('profile.update-password') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="space-y-6 mb-8">
                        <!-- Current Password -->
                        <div>
                            <label class="block text-sm font-bold text-gray-400 uppercase tracking-widest mb-3">Password Saat Ini</label>
                            <input type="password" name="current_password" required
                                   class="w-full bg-[#0f1115] border-2 border-white/10 text-white rounded-xl px-4 py-3 focus:border-red-600 focus:ring-2 focus:ring-red-600/20 transition-all @error('current_password') border-red-600 @enderror">
                            @error('current_password')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>

                        <!-- New Password -->
                        <div>
                            <label class="block text-sm font-bold text-gray-400 uppercase tracking-widest mb-3">Password Baru</label>
                            <input type="password" name="password" required
                                   class="w-full bg-[#0f1115] border-2 border-white/10 text-white rounded-xl px-4 py-3 focus:border-red-600 focus:ring-2 focus:ring-red-600/20 transition-all @error('password') border-red-600 @enderror">
                            <p class="text-gray-500 text-sm mt-2">Minimal 8 karakter</p>
                            @error('password')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>

                        <!-- Confirm Password -->
                        <div>
                            <label class="block text-sm font-bold text-gray-400 uppercase tracking-widest mb-3">Konfirmasi Password</label>
                            <input type="password" name="password_confirmation" required
                                   class="w-full bg-[#0f1115] border-2 border-white/10 text-white rounded-xl px-4 py-3 focus:border-red-600 focus:ring-2 focus:ring-red-600/20 transition-all">
                        </div>
                    </div>

                    <button type="submit" class="w-full px-6 py-4 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-black rounded-xl transition-all shadow-lg shadow-red-600/30 hover:shadow-xl hover:shadow-red-600/40 uppercase tracking-wider">
                        🔒 Ubah Password
                    </button>
                </form>
            </div>
        </div>

        <!-- Logout Button -->
        <div class="mt-12 pt-8 border-t border-white/10">
            <form action="{{ route('auth.logout') }}" method="POST">
                @csrf
                <button type="submit" class="px-8 py-3 bg-red-600/20 hover:bg-red-600/30 border-2 border-red-600/50 hover:border-red-600 text-red-500 font-bold rounded-xl transition-all uppercase tracking-wider">
                    🚪 Logout
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Image Cropper Modal -->
<div id="cropperModal" class="hidden fixed inset-0 bg-black/80 z-50 flex items-center justify-center">
    <div class="bg-[#1a1d24] rounded-2xl border border-white/10 p-8 max-w-2xl w-full mx-4 shadow-2xl">
        <h3 class="text-2xl font-black text-white mb-6">Sesuaikan Foto Profil</h3>
        
        <div class="mb-6 max-h-96 overflow-hidden rounded-xl">
            <img id="cropperImage" src="" alt="Crop preview" class="w-full" style="max-width: 100%;">
        </div>
        
        <div class="flex gap-4">
            <div class="flex-1">
                <label class="text-sm text-gray-400 mb-2 block">Zoom</label>
                <input type="range" id="zoomSlider" min="0" max="100" value="0" class="w-full">
            </div>
        </div>
        
        <div class="flex gap-4 mt-6">
            <button type="button" onclick="closeCropper()" class="flex-1 px-4 py-3 bg-white/10 hover:bg-white/20 text-white font-bold rounded-xl transition-all border border-white/20">
                ✕ Batal
            </button>
            <button type="button" onclick="saveCrop()" class="flex-1 px-4 py-3 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-bold rounded-xl transition-all shadow-lg shadow-red-600/30">
                ✓ Terapkan
            </button>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
<script>
let cropper = null;
let originalFile = null;

document.getElementById('avatar-input').addEventListener('change', function(e) {
    const file = this.files[0];
    if (!file) return;
    
    // Validate file size (2MB)
    if (file.size > 2 * 1024 * 1024) {
        alert('File terlalu besar! Maksimal 2MB');
        this.value = '';
        return;
    }
    
    // Validate file type
    if (!['image/jpeg', 'image/png', 'image/gif'].includes(file.type)) {
        alert('Tipe file tidak didukung! Gunakan JPEG, PNG, atau GIF');
        this.value = '';
        return;
    }
    
    originalFile = file;
    
    // Read and display image
    const reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('cropperImage').src = e.target.result;
        openCropper();
    };
    reader.readAsDataURL(file);
});

function openCropper() {
    document.getElementById('cropperModal').classList.remove('hidden');
    
    // Initialize cropper
    const image = document.getElementById('cropperImage');
    if (cropper) {
        cropper.destroy();
    }
    
    cropper = new Cropper(image, {
        aspectRatio: 1,
        viewMode: 1,
        autoCropArea: 1,
        responsive: true,
        guides: true,
        highlight: true,
        cropBoxMovable: true,
        cropBoxResizable: true,
        toggleDragModeOnDblclick: true,
    });
}

function closeCropper() {
    document.getElementById('cropperModal').classList.add('hidden');
    if (cropper) {
        cropper.destroy();
        cropper = null;
    }
    document.getElementById('avatar-input').value = '';
}

function saveCrop() {
    if (!cropper) return;
    
    // Get cropped canvas
    const canvas = cropper.getCroppedCanvas({
        maxWidth: 500,
        maxHeight: 500,
        fillColor: '#fff',
        imageSmoothingEnabled: true,
        imageSmoothingQuality: 'high',
    });
    
    // Convert to blob and create file
    canvas.toBlob(function(blob) {
        // Create new file from blob
        const fileName = 'avatar_' + Date.now() + '.' + (originalFile.type === 'image/png' ? 'png' : 'jpg');
        const newFile = new File([blob], fileName, { type: blob.type });
        
        // Store as base64 or blob for upload
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('avatar-cropped').value = e.target.result;
            closeCropper();
            
            // Optional: show preview
            alert('Foto berhasil disesuaikan! Klik "Simpan Perubahan" untuk menyimpan.');
        };
        reader.readAsDataURL(newFile);
    }, originalFile.type);
}

function switchTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.add('hidden');
    });
    
    document.querySelectorAll('[id^="tab-"]').forEach(btn => {
        btn.classList.remove('border-red-600', 'text-red-500');
        btn.classList.add('border-transparent', 'text-gray-400');
    });
    
    document.getElementById(tabName).classList.remove('hidden');
    
    document.getElementById('tab-' + tabName).classList.remove('border-transparent', 'text-gray-400');
    document.getElementById('tab-' + tabName).classList.add('border-red-600', 'text-red-500');
}
</script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
@endsection
