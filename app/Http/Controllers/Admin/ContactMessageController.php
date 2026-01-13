<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ContactMessage;
use Carbon\Carbon;

class ContactMessageController extends Controller
{
    // Tampilkan daftar pesan
    public function index()
    {
        $messages = ContactMessage::orderBy('created_at', 'desc')->paginate(20);
        return view('admin.contact_messages.index', compact('messages'));
    }

    // Tampilkan detail pesan & form balas
    public function show($id)
    {
        $message = ContactMessage::findOrFail($id);
        return view('admin.contact_messages.show', compact('message'));
    }

    // Proses balasan pesan
    public function reply(Request $request, $id)
    {
        $request->validate([
            'reply' => 'required|string|max:2000',
        ]);
        $message = ContactMessage::findOrFail($id);
        $message->reply = $request->reply;
        $message->replied_at = Carbon::now();
        $message->save();

        // Kirim email ke user bahwa balasan sudah tersedia
        try {
            \Illuminate\Support\Facades\Mail::to($message->email)->send(new \App\Mail\ContactMessageReplied($message));
        } catch (\Exception $e) {
            logger()->error('Failed to send reply email: ' . $e->getMessage());
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
}
