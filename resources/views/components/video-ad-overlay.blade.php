@props(['position' => 'video_overlay', 'page' => 'watch', 'timerSeconds' => 5])

@php
    use App\Models\Advertisement;
    $ads = Advertisement::getByPosition($position, $page);
@endphp

@if($ads->count() > 0)
<div id="videoAdOverlay" class="fixed inset-0 bg-black/95 flex items-center justify-center" style="z-index: 2147483647; display: none; isolation: isolate;">
    <div class="relative w-full max-w-2xl mx-4" style="z-index: 2147483647;">
        <!-- Timer & Close Button - Fixed at top right -->
        <div class="fixed top-4 right-4 flex items-center gap-3" style="z-index: 2147483647;">
            <span id="adTimerText" class="text-white text-sm font-bold bg-black/80 px-4 py-2 rounded-lg border border-white/20">
                Iklan dapat dilewati dalam <span id="adCountdown" class="text-yellow-400 font-black">{{ $timerSeconds }}</span> detik
            </span>
            <button id="adCloseBtn" 
                    disabled
                    class="px-5 py-2 bg-gray-700 text-gray-400 rounded-lg font-bold text-sm cursor-not-allowed transition-all flex items-center gap-2 border border-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                <span id="adCloseBtnText">Tunggu...</span>
            </button>
        </div>
        
        <!-- Ad Content - Centered -->
        <div class="bg-[#1a1d24] rounded-2xl overflow-hidden border-2 border-white/20 shadow-2xl">
            <div class="py-2 px-4 bg-gradient-to-r from-yellow-600 to-orange-600">
                <p class="text-center text-sm font-bold text-white uppercase tracking-wider">IKLAN</p>
            </div>
            <div class="p-8 flex items-center justify-center min-h-[300px]">
                @foreach($ads as $ad)
                <div class="ad-unit w-full" data-ad-id="{{ $ad->id }}">
                    {!! $ad->render() !!}
                </div>
                @endforeach
            </div>
        </div>
        
        <!-- Skip hint -->
        <p class="text-center text-gray-400 text-sm mt-6">
            Iklan membantu kami menyediakan konten gratis untuk kamu 💜
        </p>
    </div>
</div>

<style>
#videoAdOverlay {
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}
/* Hide video player iframe when ad is showing */
body.ad-showing iframe,
body.ad-showing video,
body.ad-showing .video-wrapper,
body.ad-showing .fullscreen-container {
    visibility: hidden !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const overlay = document.getElementById('videoAdOverlay');
    const closeBtn = document.getElementById('adCloseBtn');
    const closeBtnText = document.getElementById('adCloseBtnText');
    const timerText = document.getElementById('adTimerText');
    const countdown = document.getElementById('adCountdown');
    
    if (!overlay) return;
    
    let timerSeconds = {{ $timerSeconds }};
    let countdownInterval;
    
    // Show ad when video starts or on page load
    function showVideoAd() {
        overlay.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        document.body.classList.add('ad-showing');
        
        // Start countdown
        countdownInterval = setInterval(function() {
            timerSeconds--;
            countdown.textContent = timerSeconds;
            
            if (timerSeconds <= 0) {
                clearInterval(countdownInterval);
                // Enable close button
                closeBtn.disabled = false;
                closeBtn.classList.remove('bg-gray-700', 'text-gray-400', 'cursor-not-allowed', 'border-gray-600');
                closeBtn.classList.add('bg-red-600', 'hover:bg-red-700', 'text-white', 'cursor-pointer', 'border-red-500');
                closeBtnText.textContent = 'Lewati Iklan';
                timerText.innerHTML = '<span class="text-green-400 font-black">✓</span> Kamu bisa melewati iklan sekarang';
            }
        }, 1000);
    }
    
    // Close ad
    closeBtn.addEventListener('click', function() {
        if (!this.disabled) {
            overlay.style.display = 'none';
            document.body.style.overflow = '';
            document.body.classList.remove('ad-showing');
            
            // Track that ad was shown
            const adId = overlay.querySelector('.ad-unit')?.dataset.adId;
            if (adId) {
                fetch(`/ad/impression/${adId}`, { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }});
            }
        }
    });
    
    // Show ad after short delay (let page load first)
    setTimeout(showVideoAd, 500);
});
</script>
@endif
