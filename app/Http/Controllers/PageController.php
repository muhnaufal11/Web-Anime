<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PageController extends Controller
{
    public function dmca()
    {
        return view('pages.dmca');
    }

    public function privacy()
    {
        return view('pages.privacy');
    }

    public function terms()
    {
        return view('pages.terms');
    }

    public function contact()
    {
        return view('pages.contact');
    }

    public function viewContactStatus($token)
    {
        $message = \App\Models\ContactMessage::where('view_token', $token)->firstOrFail();

        return view('pages.contact_status', compact('message'));
    }

    public function sendContact(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:200',
            'message' => 'required|string|max:2000',
        ]);

        $validated['view_token'] = \Illuminate\Support\Str::random(48);

        $message = \App\Models\ContactMessage::create($validated);

        // Send a simple confirmation email with a link to check status
        try {
            \Illuminate\Support\Facades\Mail::to($message->email)->send(new \App\Mail\ContactMessageCreated($message));
        } catch (\Exception $e) {
            // don't break if mail fails; optionally log
            logger()->error('Failed to send contact message confirmation email: ' . $e->getMessage());
        }

        // Return link info so user can save it if needed
        $link = route('contact.status', $message->view_token);

        return back()->with('success', 'Pesan berhasil dikirim! Kami akan merespons dalam 1-2 hari kerja. Anda bisa mengecek status pesan Anda di: ' . $link);
    }
}
