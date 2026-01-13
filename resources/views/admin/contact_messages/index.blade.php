@extends('layouts.app')
@section('title', 'Pesan Kontak')
@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6">Daftar Pesan Kontak</h1>
    <div class="bg-[#1a1d24] rounded-xl p-6 border border-white/5">
        <table class="min-w-full text-white">
            <thead>
                <tr>
                    <th class="py-2 px-4">Nama</th>
                    <th class="py-2 px-4">Email</th>
                    <th class="py-2 px-4">Subjek</th>
                    <th class="py-2 px-4">Tanggal</th>
                    <th class="py-2 px-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($messages as $msg)
                <tr class="border-b border-white/10">
                    <td class="py-2 px-4">{{ $msg->name }}</td>
                    <td class="py-2 px-4">{{ $msg->email }}</td>
                    <td class="py-2 px-4">{{ $msg->subject }}</td>
                    <td class="py-2 px-4">{{ $msg->created_at->format('d M Y H:i') }}</td>
                    <td class="py-2 px-4">
                        <a href="{{ route('admin.contact-messages.show', $msg->id) }}" class="text-blue-400 hover:underline">Lihat</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="py-4 text-center text-gray-400">Belum ada pesan.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $messages->links() }}</div>
    </div>
</div>
@endsection
