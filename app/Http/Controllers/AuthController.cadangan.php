<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /**
     * Show OTP verification form
     */
    public function showOtpForm()
    {
        return view('auth.otp');
    }

    /**
     * Handle OTP verification
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|digits:6',
        ]);

        $user = Auth::user();
        $cacheKey = 'otp_' . $user->id;
        $otp = \Cache::get($cacheKey);

        if ($otp && $request->otp == $otp) {
            $user->email_verified_at = now();
            $user->save();
            \Cache::forget($cacheKey);
            return redirect()->route('home')->with('success', 'Email berhasil diverifikasi!');
        }

        return back()->withErrors(['otp' => 'Kode OTP salah atau sudah kadaluarsa.']);
    }

    /**
     * Resend OTP
     */
    public function resendOtp()
    {
        $user = Auth::user();
        
        if ($user->email_verified_at) {
            return redirect()->route('home');
        }

        $otp = random_int(100000, 999999);
        \Cache::put('otp_' . $user->id, $otp, now()->addMinutes(15));
        
        // Mengirim email ke antrian (Queue)
        \Mail::to($user->email)->queue(new \App\Mail\OtpVerificationMail($user, $otp));

        return back()->with('success', 'Kode OTP baru telah dikirim ke email Anda.');
    }

    /**
     * Show login form
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }
        return view('auth.login');
    }

    /**
     * Handle login
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->route('home')->with('success', 'Login berhasil! Selamat datang kembali.');
        }

        return back()->withErrors([
            'email' => 'Email atau password salah.',
        ])->onlyInput('email');
    }

    /**
     * Show register form
     */
    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }
        return view('auth.register');
    }

    /**
     * Handle registration
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => null,
        ]);

        // Generate 6 digit OTP
        $otp = random_int(100000, 999999);
        
        // Simpan OTP di cache selama 15 menit
        \Cache::put('otp_' . $user->id, $otp, now()->addMinutes(15));

        // Kirim email OTP via Queue
        \Mail::to($user->email)->queue(new \App\Mail\OtpVerificationMail($user, $otp));

        Auth::login($user);

        return redirect()->route('auth.otp')->with('success', 'Kode OTP telah dikirim ke email Anda.');
    }

    /**
     * Handle logout
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'Anda telah logout.');
    }
}