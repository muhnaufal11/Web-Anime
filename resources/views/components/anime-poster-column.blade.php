<img src="{{ $state ? asset('storage/' . $state) : asset('images/placeholder.png') }}" 
    alt="{{ $record->title ?? 'Anime Poster' }}"
    class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-700 bg-gray-800">