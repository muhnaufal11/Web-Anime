<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ContactMessage;
use App\Models\ContactReply;
use Carbon\Carbon;

class ContactMessageController extends Controller
{
    // Tampilkan daftar pesan
    public function index()
    {
        $messages = ContactMessage::orderBy('created_at', 'desc')->paginate(20);
        return view('admin.contact_messages.index', compact('messages'));
    }

    // Tampilkan detail pesan & form balas (chat view)
    public function show($id)
    {
        $message = ContactMessage::with('replies')->findOrFail($id);
        return view('admin.contact_messages.chat', compact('message'));
    }

    // Proses balasan pesan dari admin
    public function reply(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string|max:2000',
        ]);
        
        $message = ContactMessage::findOrFail($id);
        
        // Create new reply
        $reply = ContactReply::create([
            'contact_message_id' => $message->id,
            'message' => $request->message,
            'is_admin' => true,
            'admin_id' => auth()->id(),
        ]);
        
        // Update legacy reply field for backward compatibility
        $message->reply = $request->message;
        $message->replied_at = Carbon::now();
        $message->save();

        // Kirim email ke user bahwa balasan sudah tersedia
        try {
            \Illuminate\Support\Facades\Mail::to($message->email)->send(new \App\Mail\ContactMessageReplied($message));
        } catch (\Exception $e) {
            logger()->error('Failed to send reply email: ' . $e->getMessage());
        }

        // Return JSON for AJAX requests
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Balasan berhasil dikirim',
                'reply' => $reply
            ]);
        }

        return redirect()->route('admin.contact-messages.show', $id)
            ->with('success', 'Balasan berhasil disimpan dan pemberitahuan email dikirim.');
    }

    // Tandai percakapan selesai (tutup chat)
    public function close(Request $request, $id)
    {
        $message = ContactMessage::findOrFail($id);
        $message->is_closed = true;
        $message->closed_at = Carbon::now();
        $message->save();

        return redirect()->route('admin.contact-messages.show', $id)
            ->with('success', 'Percakapan ditandai selesai.');
    }

    // API: Get all replies for real-time chat
    public function getReplies($id)
    {
        // Check if user is authenticated and is admin
        if (!auth()->check() || !auth()->user()->is_admin) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $message = ContactMessage::with(['replies' => function($query) {
            $query->orderBy('created_at', 'asc');
        }, 'replies.admin'])->findOrFail($id);

        $replies = $message->replies->map(function($reply) {
            return [
                'id' => $reply->id,
                'message' => $reply->message,
                'is_admin' => $reply->is_admin,
                'admin_name' => $reply->admin?->name ?? 'Admin',
                'created_at' => $reply->created_at->format('d M Y H:i'),
            ];
        });

        return response()->json([
            'success' => true,
            'replies' => $replies,
            'is_closed' => $message->is_closed,
        ]);
    }

    // API: Quick reply for real-time chat
    public function quickReply(Request $request, $id)
    {
        // Check if user is authenticated and is admin
        if (!auth()->check() || !auth()->user()->is_admin) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'message' => 'required|string|max:2000',
        ]);
        
        $message = ContactMessage::findOrFail($id);
        
        // Create new reply
        $reply = ContactReply::create([
            'contact_message_id' => $message->id,
            'message' => $request->message,
            'is_admin' => true,
            'admin_id' => auth()->id(),
        ]);
        
        // Update legacy reply field
        $message->reply = $request->message;
        $message->replied_at = Carbon::now();
        $message->save();

        // Try to send email
        try {
            \Illuminate\Support\Facades\Mail::to($message->email)->send(new \App\Mail\ContactMessageReplied($message));
        } catch (\Exception $e) {
            logger()->error('Failed to send reply email: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Balasan terkirim',
            'reply' => [
                'id' => $reply->id,
                'message' => $reply->message,
                'is_admin' => $reply->is_admin,
                'admin_name' => auth()->user()->name ?? 'Admin',
                'created_at' => $reply->created_at->format('d M Y H:i'),
            ]
        ]);
    }
}
