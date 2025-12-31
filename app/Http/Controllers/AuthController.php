<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;
use App\Mail\OtpVerificationMail;

class AuthController extends Controller
{
    // --- 1. LOGIKA PENDAFTARAN BARU (SMART REGISTRATION) ---

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        // Validasi Input
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        // STEP PINTAR: Jangan create User dulu! 
        // Simpan data pendaftaran di Cache sementara (selama 30 menit)
        $tempUserData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']), // Hash sekarang biar aman
            'otp' => random_int(100000, 999999)
        ];

        // Key Cache pakai email biar unik: 'regist_temp_email@domain.com'
        Cache::put('regist_temp_' . $validated['email'], $tempUserData, now()->addMinutes(30));

        // Kirim OTP ke email tersebut (Kita kirim manual object user dummy)
        $dummyUser = (object) ['name' => $validated['name'], 'email' => $validated['email']];
        Mail::to($validated['email'])->queue(new OtpVerificationMail($dummyUser, $tempUserData['otp']));

        // Simpan email di session browser supaya halaman OTP tahu siapa yang mau diverifikasi
        session(['otp_email' => $validated['email']]);

        return redirect()->route('auth.otp')->with('success', 'OTP dikirim! Akun belum dibuat sampai Anda verifikasi.');
    }

    // --- 2. HALAMAN & PROSES VERIFIKASI (HYBRID) ---

    public function showOtpForm()
    {
        // Cek: Apakah ini user lama (sudah login) atau user baru (ada di session)?
        if (!Auth::check() && !session('otp_email')) {
            return redirect()->route('auth.login');
        }
        return view('auth.otp');
    }

    public function verifyOtp(Request $request)
    {
        $request->validate(['otp' => 'required|digits:6']);

        // SKENARIO A: User Lama (Sudah ada di DB tapi belum verify)
        if (Auth::check()) {
            $user = Auth::user();
            $cacheKey = 'otp_' . $user->id; // Key khusus user lama
            $cachedOtp = Cache::get($cacheKey);

            if ($request->otp == $cachedOtp) {
                $user->email_verified_at = now();
                $user->save();
                Cache::forget($cacheKey);
                return redirect()->route('home')->with('success', 'Akun berhasil diverifikasi!');
            }
        } 
        
        // SKENARIO B: User Baru (Calon pendaftar, data masih di Cache)
        else {
            $email = session('otp_email');
            if (!$email) return redirect()->route('auth.register')->with('error', 'Sesi habis, daftar ulang.');

            $cacheKey = 'regist_temp_' . $email;
            $tempData = Cache::get($cacheKey);

            // Cek apakah OTP cocok dengan data di Cache
            if ($tempData && $request->otp == $tempData['otp']) {
                
                // BARU SEKARANG KITA BUAT AKUNNYA DI DATABASE
                $user = User::create([
                    'name' => $tempData['name'],
                    'email' => $tempData['email'],
                    'password' => $tempData['password'],
                    'email_verified_at' => now(), // Langsung verified!
                ]);

                // Hapus data sementara & login otomatis
                Cache::forget($cacheKey);
                session()->forget('otp_email');
                
                Auth::login($user);
                
                return redirect()->route('home')->with('success', 'Pendaftaran sukses & Terverifikasi!');
            }
        }

        return back()->withErrors(['otp' => 'Kode OTP salah atau kadaluarsa.']);
    }

    // --- 3. KIRIM ULANG OTP (HYBRID) ---

    public function resendOtp()
    {
        // SKENARIO A: User Lama
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->email_verified_at) return redirect()->route('home');

            $otp = random_int(100000, 999999);
            Cache::put('otp_' . $user->id, $otp, now()->addMinutes(15));
            Mail::to($user->email)->queue(new OtpVerificationMail($user, $otp));
        } 
        
        // SKENARIO B: User Baru
        else {
            $email = session('otp_email');
            if (!$email) return redirect()->route('auth.register');

            $cacheKey = 'regist_temp_' . $email;
            $tempData = Cache::get($cacheKey);

            if ($tempData) {
                // Update OTP baru di cache yang sama
                $tempData['otp'] = random_int(100000, 999999);
                Cache::put($cacheKey, $tempData, now()->addMinutes(30));

                $dummyUser = (object) ['name' => $tempData['name'], 'email' => $email];
                Mail::to($email)->queue(new OtpVerificationMail($dummyUser, $tempData['otp']));
            }
        }

        return back()->with('success', 'Kode OTP baru dikirim.');
    }

    // --- 4. FUNGSI STANDAR LAINNYA ---

    public function showLogin() {
        if (Auth::check()) return redirect()->route('home');
        return view('auth.login');
    }

    public function login(Request $request) {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->route('home');
        }
        return back()->withErrors(['email' => 'Email atau password salah.']);
    }

    public function logout(Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('auth.login');
    }
}