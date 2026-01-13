<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Genre;
use App\Models\Anime;
use App\Models\Episode;
use App\Models\VideoServer;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Genres
        $genres = [
            ['name' => 'Action', 'slug' => 'action'],
            ['name' => 'Adventure', 'slug' => 'adventure'],
            ['name' => 'Comedy', 'slug' => 'comedy'],
            ['name' => 'Drama', 'slug' => 'drama'],
            ['name' => 'Fantasy', 'slug' => 'fantasy'],
            ['name' => 'Horror', 'slug' => 'horror'],
            ['name' => 'Sci-Fi', 'slug' => 'sci-fi'],
            ['name' => 'Romance', 'slug' => 'romance'],
            ['name' => 'Slice of Life', 'slug' => 'slice-of-life'],
            ['name' => 'Supernatural', 'slug' => 'supernatural'],
        ];

        foreach ($genres as $genre) {
            Genre::firstOrCreate(['slug' => $genre['slug']], $genre);
        }

        // Create Sample Animes
        $animeData = [
            [
                'title' => 'Attack on Titan',
                'slug' => 'attack-on-titan',
                'synopsis' => 'Humanity has been decimated by the sudden appearance of Titans, giant humanoid creatures who devour humans. The remaining humans retreated behind three enormous walls that protect a city, after the last invasion a century ago.',
                'type' => 'TV',
                'status' => 'Completed',
                'release_year' => 2013,
                'rating' => 9.0,
                'featured' => true,
                'genres' => [1, 2, 4], // Action, Adventure, Drama
            ],
            [
                'title' => 'Death Note',
                'slug' => 'death-note',
                'synopsis' => 'Light Yagami, an average high school boy, accidentally obtains a death note - a notebook from the shinigami realm where the user can write the name of anyone to kill them.',
                'type' => 'TV',
                'status' => 'Completed',
                'release_year' => 2006,
                'rating' => 8.8,
                'featured' => true,
                'genres' => [1, 4, 10], // Action, Drama, Supernatural
            ],
            [
                'title' => 'My Hero Academia',
                'slug' => 'my-hero-academia',
                'synopsis' => 'In a world where most of the population has some kind of superpower, a powerless boy dreams of becoming a superhero.',
                'type' => 'TV',
                'status' => 'Ongoing',
                'release_year' => 2016,
                'rating' => 8.4,
                'featured' => true,
                'genres' => [1, 2, 5], // Action, Adventure, Fantasy
            ],
            [
                'title' => 'Demon Slayer',
                'slug' => 'demon-slayer',
                'synopsis' => 'After the murder of his family, a young man joins the Demon Slayer Corps in order to find and defeat the demon responsible.',
                'type' => 'TV',
                'status' => 'Ongoing',
                'release_year' => 2019,
                'rating' => 8.7,
                'featured' => true,
                'genres' => [1, 2, 4], // Action, Adventure, Drama
            ],
            [
                'title' => 'One Piece',
                'slug' => 'one-piece',
                'synopsis' => 'A young pirate named Monkey D. Luffy sets out to sea in hopes of becoming the Pirate King, the man who has conquered the ocean.',
                'type' => 'TV',
                'status' => 'Ongoing',
                'release_year' => 1999,
                'rating' => 8.9,
                'featured' => true,
                'genres' => [1, 2, 3], // Action, Adventure, Comedy
            ],
        ];

        foreach ($animeData as $data) {
            $genreIds = $data['genres'];
            unset($data['genres']);

            $anime = Anime::firstOrCreate(['slug' => $data['slug']], $data);
            $anime->genres()->sync($genreIds);

            // Create Episodes for each anime
            for ($i = 1; $i <= 3; $i++) {
                $episode = Episode::firstOrCreate([
                    'anime_id' => $anime->id,
                    'episode_number' => $i,
                    'slug' => $anime->slug . '-episode-' . $i,
                ], [
                    'title' => 'Episode ' . $i,
                    'description' => 'Watch ' . $anime->title . ' Episode ' . $i . ' online.',
                ]);

                // Add video servers to episodes
                $servers = ['GDrive', 'Mirror', 'Backup'];
                foreach ($servers as $index => $server) {
                    VideoServer::firstOrCreate([
                        'episode_id' => $episode->id,
                        'server_name' => $server,
                    ], [
                        'embed_url' => 'https://example.com/embed/' . $episode->id . '/' . $index,
                        'is_active' => true,
                    ]);
                }
            }
        }
    }
}
