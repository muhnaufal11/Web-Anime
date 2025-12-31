<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\DetailController;
use App\Http\Controllers\WatchController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\VideoProxyController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\AnimeRequestController;
use App\Http\Controllers\PageController;

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

// Public Routes (No Auth Required)
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/search', [HomeController::class, 'search'])->name('search');
Route::get('/schedule', [ScheduleController::class, 'index'])->name('schedule');
Route::get('/anime/{anime:slug}', [DetailController::class, 'show'])->name('detail');
Route::get('/watch/{episode:slug}', [WatchController::class, 'show'])->name('watch');

// Legal Pages
Route::get('/dmca', [PageController::class, 'dmca'])->name('dmca');
Route::get('/privacy', [PageController::class, 'privacy'])->name('privacy');
Route::get('/terms', [PageController::class, 'terms'])->name('terms');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::post('/contact', [PageController::class, 'sendContact'])->name('contact.send');

// Video Proxy Routes
Route::get('/api/video/proxy/animesail/{playerType}', [VideoProxyController::class, 'proxyAnimeSail'])->name('video.proxy.animesail');
Route::get('/api/video/proxy/external', [VideoProxyController::class, 'proxyExternal'])->name('video.proxy.external');


// Auth Routes
Route::prefix('auth')->name('auth.')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login']);
    Route::get('register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('register', [AuthController::class, 'register']);
    Route::post('logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

    // OTP Verification
    Route::middleware('auth')->group(function () {
        Route::get('otp', [AuthController::class, 'showOtpForm'])->name('otp');
        Route::post('otp', [AuthController::class, 'verifyOtp'])->name('otp.verify');
        Route::post('otp/resend', [AuthController::class, 'resendOtp'])->name('otp.resend');
    });
});

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
    
    // Anime Request Routes
    Route::get('/request', [AnimeRequestController::class, 'index'])->name('request.index');
    Route::post('/request', [AnimeRequestController::class, 'store'])->name('request.store');
    Route::post('/request/{animeRequest}/vote', [AnimeRequestController::class, 'vote'])->name('request.vote');
});
