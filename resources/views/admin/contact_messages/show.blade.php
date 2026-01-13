@extends('layouts.app')
@section('title', 'Detail Pesan Kontak')
@section('content')
<div class="max-w-2xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6">Detail Pesan Kontak</h1>
    <div class="bg-[#1a1d24] rounded-xl p-6 border border-white/5 mb-6">
        <div class="mb-2"><b>Nama:</b> {{ $message->name }}</div>
        <div class="mb-2"><b>Email:</b> {{ $message->email }}</div>
        <div class="mb-2"><b>Subjek:</b> {{ $message->subject }}</div>
        <div class="mb-2"><b>Tanggal:</b> {{ $message->created_at->format('d M Y H:i') }}</div>
        <div class="mb-2"><b>Pesan:</b><br> <div class="bg-gray-800 p-3 rounded mt-1">{{ $message->message }}</div></div>
        @if($message->reply)
            <div class="mt-4 p-4 bg-green-900/40 border border-green-500/30 rounded">
                <b>Balasan Admin ({{ $message->replied_at ? $message->replied_at->format('d M Y H:i') : '' }}):</b><br>
                <div class="mt-1">{{ $message->reply }}</div>
            </div>
        @endif

        <div class="mt-4">
            <b>Tautan Publik untuk Pengirim:</b>
            <div class="mt-1"> <a href="{{ route('contact.status', $message->view_token) }}" target="_blank" class="text-blue-400 hover:underline">{{ route('contact.status', $message->view_token) }}</a></div>
        </div>
    </div>
    <div class="bg-[#23272f] rounded-xl p-6 border border-white/10">
        <h2 class="text-lg font-semibold mb-3">Balas Pesan</h2>
        <form method="POST" action="{{ route('admin.contact-messages.reply', $message->id) }}">
            @csrf
            <div class="mb-4">
                <textarea name="reply" rows="4" class="w-full p-3 rounded bg-gray-900 text-white border border-white/20 focus:border-red-500" {{ $message->is_closed ? 'readonly' : '' }} required>{{ old('reply', $message->reply) }}</textarea>
            </div>
            @if(!$message->is_closed)
            <div class="flex gap-2">
                <button type="submit" class="py-2 px-6 bg-red-600 hover:bg-red-700 text-white rounded font-bold">Kirim Balasan</button>
                <form method="POST" action="{{ route('admin.contact-messages.close', $message->id) }}">
                    @csrf
                    <button type="submit" class="py-2 px-4 bg-gray-600 hover:bg-gray-700 text-white rounded">Tandai Selesai</button>
                </form>
            </div>
            @else
            <div class="p-4 bg-gray-800 rounded">Percakapan sudah ditutup pada {{ $message->closed_at ? $message->closed_at->format('d M Y H:i') : '' }}</div>
            @endif
        </form>
    </div>
    <div class="mt-6">
        <a href="{{ route('admin.contact-messages.index') }}" class="text-blue-400 hover:underline">&larr; Kembali ke daftar pesan</a>
    </div>
</div>
@endsection
