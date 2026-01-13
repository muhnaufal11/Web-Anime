<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    /**
     * Display user profile page.
     */
    public function show()
    {
        return view('profile.show', [
            'user' => auth()->user(),
        ]);
    }

    /**
     * Update user profile.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . auth()->id(),
            'phone' => 'nullable|string|max:20',
            'gender' => 'nullable|in:male,female,other',
            'birth_date' => 'nullable|date',
            'location' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:500',
            'avatar-cropped' => 'nullable|string',
        ]);

        // Handle avatar upload from cropped image
        if ($request->has('avatar-cropped') && !empty($request->input('avatar-cropped'))) {
            // Delete old avatar if exists
            if (auth()->user()->avatar && file_exists(storage_path('app/public/' . auth()->user()->avatar))) {
                unlink(storage_path('app/public/' . auth()->user()->avatar));
            }

            // Decode base64 and save
            $croppedData = $request->input('avatar-cropped');
            if (strpos($croppedData, 'data:image') === 0) {
                // Extract base64 content
                $parts = explode(',', $croppedData);
                $imageData = base64_decode($parts[1]);
                
                // Determine file type from data URI
                $mimeType = explode(';', $parts[0])[0];
                $ext = str_contains($mimeType, 'png') ? 'png' : 'jpg';
                
                // Save file
                $fileName = 'avatars/' . 'avatar_' . auth()->id() . '_' . time() . '.' . $ext;
                $storagePath = storage_path('app/public/' . $fileName);
                
                if (!file_exists(dirname($storagePath))) {
                    mkdir(dirname($storagePath), 0755, true);
                }
                
                file_put_contents($storagePath, $imageData);
                $validated['avatar'] = $fileName;
                unset($validated['avatar-cropped']);
            }
        }

        auth()->user()->update($validated);

        return redirect()->route('profile.show')->with('success', 'Profil berhasil diperbarui!');
    }

    /**
     * Update password.
     */
    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if (!Hash::check($validated['current_password'], auth()->user()->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Password saat ini tidak sesuai.',
            ]);
        }

        auth()->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('profile.show')->with('success', 'Password berhasil diubah!');
    }
}
