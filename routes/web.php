<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\DetailController;
use App\Http\Controllers\WatchController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\VideoProxyController;
use App\Http\Controllers\VideoSourceController;
use App\Http\Controllers\PlayerProxyController;
use App\Http\Controllers\StreamProxyController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\AnimeRequestController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\EpisodeStreamController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Language Switch
Route::get('/language/{locale}', [LanguageController::class, 'switch'])->name('language.switch');

// Real-time Episode Stream (SSE)
Route::get('/api/episodes/stream', [EpisodeStreamController::class, 'stream'])->name('episodes.stream');
Route::get('/api/episodes/latest', [EpisodeStreamController::class, 'getLatest'])->name('episodes.latest');

// Filament logout GET handler (redirect to login if accessed via GET)
Route::get('/filament/logout', function () {
    return redirect('/admin/login');
});

// Public Routes (No Auth Required)
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/search', [HomeController::class, 'search'])->name('search');
Route::get('/api/search/suggestions', [HomeController::class, 'searchSuggestions'])->name('search.suggestions');
Route::get('/latest-episodes', [HomeController::class, 'latestEpisodes'])->name('latest-episodes');
Route::get('/schedule', [ScheduleController::class, 'index'])->name('schedule');
Route::get('/anime/{anime:slug}', [DetailController::class, 'show'])->name('detail');
Route::get('/watch/{episode:slug}', [WatchController::class, 'show'])->name('watch')->middleware('adult.content');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

// Public: view contact message status by token (chat)
Route::get('/contact/{token}', [PageController::class, 'viewContactStatus'])->name('contact.status')->where('token', '[A-Za-z0-9]+');
Route::post('/contact/{token}/reply', [PageController::class, 'replyToContact'])->name('contact.reply');
Route::get('/api/contact/{token}/messages', [PageController::class, 'getMessages'])->name('contact.messages');

// Legal Pages
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/disclaimer', [PageController::class, 'disclaimer'])->name('disclaimer');
Route::get('/dmca', [PageController::class, 'dmca'])->name('dmca');
Route::get('/privacy', [PageController::class, 'privacy'])->name('privacy');
Route::get('/terms', [PageController::class, 'terms'])->name('terms');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::post('/contact', [PageController::class, 'sendContact'])->name('contact.send');
Route::post('/contact/my-tickets', [PageController::class, 'myTickets'])->name('contact.my-tickets');

// Video Proxy Routes
Route::get('/api/video/proxy/animesail/{playerType}', [VideoProxyController::class, 'proxyAnimeSail'])->name('video.proxy.animesail');
Route::get('/api/video/proxy/external', [VideoProxyController::class, 'proxyExternal'])->name('video.proxy.external');

// Video Source API (Protected)
Route::post('/api/video/source', [VideoSourceController::class, 'getSource'])->name('video.source');

// Video Subtitle Extraction API (for MKV files)
Route::get('/api/video/subtitle/{token}', [VideoSourceController::class, 'getSubtitle'])->name('video.subtitle');

// Player proxy page (hide external URL in parent HTML)
Route::get('/player/{token}', [PlayerProxyController::class, 'show'])->name('player.proxy');

// Extracted video proxy (for ad-free playback) - MUST be before stream.proxy
Route::match(['get', 'options'], '/stream/extracted/{token}', [StreamProxyController::class, 'proxyExtracted'])->name('stream.extracted')->middleware('signed');

// Stream proxy (short-lived signed redirect) - supports GET and OPTIONS for CORS
Route::match(['get', 'options'], '/stream/{token}', [StreamProxyController::class, 'redirect'])->name('stream.proxy')->middleware('signed');


// Auth Routes
Route::prefix('auth')->name('auth.')->group(function () {
    // Halaman Tamu (Login/Register)
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login']);

    // Google OAuth
    Route::get('google', [AuthController::class, 'redirectToGoogle'])->name('google');
    Route::get('google/callback', [AuthController::class, 'handleGoogleCallback'])->name('google.callback');
    Route::get('register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('register', [AuthController::class, 'register']);
    
    // Halaman OTP (SEKARANG KITA TARUH DILUAR MIDDLEWARE AUTH)
    // Karena Controller sudah punya logika pengecekan sendiri
    Route::get('otp', [AuthController::class, 'showOtpForm'])->name('otp');
    Route::post('otp', [AuthController::class, 'verifyOtp'])->name('otp.verify');
    Route::post('otp/resend', [AuthController::class, 'resendOtp'])->name('otp.resend');

    // Logout tetap butuh auth
    Route::post('logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');
});;

// Profile Routes (Auth + OTP Verified Required)
Route::middleware(['auth', 'otp.verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.update-password');
    
    // Comment Routes
    Route::post('/comments', [CommentController::class, 'store'])->name('comments.store');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
    
    // Watch Progress
    Route::post('/watch/{episode:slug}/progress', [WatchController::class, 'updateProgress'])->name('watch.progress');
    
    // History Routes
    Route::get('/watch-history', [WatchController::class, 'history'])->name('watch-history');
    
    // Anime Request Routes
    Route::get('/request', [AnimeRequestController::class, 'index'])->name('request.index');
    Route::post('/request', [AnimeRequestController::class, 'store'])->name('request.store');
    Route::post('/request/{animeRequest}/vote', [AnimeRequestController::class, 'vote'])->name('request.vote');
});

// Admin Routes
Route::prefix('admin')->middleware(['auth', 'is_admin'])->name('admin.')->group(function () {
    // Contact Messages
    Route::get('contact-messages', [ContactMessageController::class, 'index'])->name('contact-messages.index');
    Route::get('contact-messages/{id}', [ContactMessageController::class, 'show'])->name('contact-messages.show');
    Route::post('contact-messages/{id}/reply', [ContactMessageController::class, 'reply'])->name('contact-messages.reply');
    Route::post('contact-messages/{id}/close', [ContactMessageController::class, 'close'])->name('contact-messages.close');
});

// API for real-time chat (using web middleware for Filament compatibility)
Route::prefix('admin/api')->middleware(['web'])->group(function () {
    Route::get('contact-messages/{id}/replies', [ContactMessageController::class, 'getReplies']);
    Route::post('contact-messages/{id}/reply', [ContactMessageController::class, 'quickReply']);
});
