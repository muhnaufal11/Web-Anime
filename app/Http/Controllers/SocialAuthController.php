<?php

namespace App\Http\Controllers;

use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;

class SocialAuthController extends Controller
{
    public function redirectToGoogle(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Exception $e) {
            return redirect()->route('auth.login')->withErrors(['oauth' => 'Google login gagal.']);
        }

        // Prefer match by provider fields, fall back to email
        $user = User::where('provider', 'google')
            ->where('provider_id', $googleUser->id)
            ->first()
            ?? User::where('email', $googleUser->email)->first();

        if (!$user) {
            $user = User::create([
                'name' => $googleUser->name ?? $googleUser->nickname ?? 'User',
                'email' => $googleUser->email,
                'email_verified_at' => now(),
                'password' => bcrypt(Str::random(16)),
                'provider' => 'google',
                'provider_id' => $googleUser->id,
                'avatar' => $googleUser->avatar ?? null,
            ]);
        } else {
            // Ensure provider fields are set for existing user
            $needs = [];
            if (!$user->provider) $needs['provider'] = 'google';
            if (!$user->provider_id) $needs['provider_id'] = $googleUser->id;
            if ($googleUser->avatar && !$user->avatar) $needs['avatar'] = $googleUser->avatar;
            if (!empty($needs)) $user->update($needs);
        }

        Auth::login($user, true);

        return redirect()->route('home');
    }
}
