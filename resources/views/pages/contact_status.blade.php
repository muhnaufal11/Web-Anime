@extends('layouts.app')
@section('title', 'Status Pesan')
@section('content')
<div class="max-w-2xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6">Status Pesan</h1>

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
        @else
            <div class="mt-4 p-4 bg-yellow-900/20 border border-yellow-500/20 rounded">Belum ada balasan. Silakan cek nanti.</div>
        @endif

        @if($message->is_closed)
            <div class="mt-4 p-4 bg-gray-800/60 border border-gray-600/40 rounded">Percakapan telah ditutup oleh admin pada {{ $message->closed_at ? $message->closed_at->format('d M Y H:i') : '' }}.</div>
        @endif
    </div>

    <div class="mt-6">
        <a href="{{ route('contact') }}" class="text-blue-400 hover:underline">&larr; Kembali ke halaman kontak</a>
    </div>
</div>
@endsection