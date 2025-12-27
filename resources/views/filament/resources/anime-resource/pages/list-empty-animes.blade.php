@extends('filament::page')

@section('content')
    <div class="space-y-4">
        <h1 class="text-2xl font-bold">Anime Tanpa Episode / Video Server</h1>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-left">#</th>
                        <th class="px-4 py-2 text-left">Judul Anime</th>
                        <th class="px-4 py-2 text-left">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($animes as $index => $anime)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2">{{ $index + 1 }}</td>
                            <td class="px-4 py-2">{{ $anime->title }}</td>
                            <td class="px-4 py-2">
                                <button onclick="navigator.clipboard.writeText('{{ $anime->title }}')" class="px-2 py-1 bg-blue-500 text-white rounded hover:bg-blue-600">Copy Judul</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-2 text-center">Semua anime sudah punya episode & video server.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
