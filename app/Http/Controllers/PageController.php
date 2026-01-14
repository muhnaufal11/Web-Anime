<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ContactMessage;
use App\Models\ContactReply;

class PageController extends Controller
{
    public function about()
    {
        return view('pages.about');
    }

    public function disclaimer()
    {
        return view('pages.disclaimer');
    }

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
        $message = ContactMessage::with('replies.admin')->where('view_token', $token)->firstOrFail();

        return view('pages.contact_status', compact('message'));
    }

    /**
     * API: Get messages for real-time chat
     */
    public function getMessages($token)
    {
        $message = ContactMessage::with(['replies.admin'])
            ->where('view_token', $token)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'is_closed' => $message->is_closed,
            'replies' => $message->replies->map(function ($reply) {
                return [
                    'id' => $reply->id,
                    'message' => $reply->message,
                    'is_admin' => $reply->is_admin,
                    'admin' => $reply->admin ? ['name' => $reply->admin->name] : null,
                    'created_at' => $reply->created_at->toISOString(),
                ];
            }),
        ]);
    }

    public function replyToContact(Request $request, $token)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $message = ContactMessage::where('view_token', $token)->firstOrFail();

        // Check if chat is closed
        if ($message->is_closed) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Percakapan sudah ditutup.'
                ], 400);
            }
            return back()->with('error', 'Percakapan sudah ditutup.');
        }

        // Create new reply from user
        $reply = ContactReply::create([
            'contact_message_id' => $message->id,
            'message' => $validated['message'],
            'is_admin' => false,
            'admin_id' => null,
        ]);

        // Return JSON for AJAX requests
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Pesan berhasil dikirim',
                'reply' => $reply
            ]);
        }

        return back()->with('success', 'Pesan berhasil dikirim!');
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

        $message = ContactMessage::create($validated);

        // Send a simple confirmation email with a link to check status
        try {
            \Illuminate\Support\Facades\Mail::to($message->email)->send(new \App\Mail\ContactMessageCreated($message));
        } catch (\Exception $e) {
            // don't break if mail fails; optionally log
            logger()->error('Failed to send contact message confirmation email: ' . $e->getMessage());
        }

        // Redirect langsung ke halaman chat dengan pesan sukses
        return redirect()->route('contact.status', $message->view_token)
            ->with('success', 'Pesan berhasil dikirim! Simpan link ini untuk melihat balasan dari admin.');
    }

    public function myTickets(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $email = $validated['email'];
        $tickets = ContactMessage::with('replies')
            ->where('email', $email)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('pages.my_tickets', compact('email', 'tickets'));
    }
}
