<?php

namespace App\Livewire;

use App\Models\Episode;
use App\Services\VideoExtractor\VideoExtractorService;
use Livewire\Component;
use Illuminate\Support\Str;

class VideoPlayer extends Component
{
    public int $episodeId;
    public int $selectedServerId = 0;

    public function mount(Episode $episode)
    {
        $this->episodeId = $episode->id;
        
        // Priority: 1. Default server, 2. First active server
        $defaultServer = $episode->videoServers()
            ->where('is_active', true)
            ->where('is_default', true)
            ->first();
        
        if ($defaultServer) {
            $this->selectedServerId = $defaultServer->id;
        } else {
            // Fallback to first active server
            $firstServer = $episode->videoServers()->where('is_active', true)->first();
            if ($firstServer) {
                $this->selectedServerId = $firstServer->id;
            }
        }
    }

    public function selectServer($serverId)
    {
        $this->selectedServerId = $serverId;
    }

    public function render()
    {
        // Fresh load episode with active video servers
        $episode = Episode::with(['videoServers' => function($q) {
            $q->where('is_active', true);
        }])->find($this->episodeId);
        
        $selectedServer = null;
        $extractedVideo = null;
        
        if ($this->selectedServerId && $episode) {
            $selectedServer = $episode->videoServers->firstWhere('id', $this->selectedServerId);
            
            // Try to extract direct video URL if supported
            if ($selectedServer) {
                $embedUrl = $selectedServer->embed_url;
                
                // Extract URL from iframe if needed
                if (stripos($embedUrl, '<iframe') !== false) {
                    if (preg_match('/src=["\']([^"\']+)["\']/i', $embedUrl, $matches)) {
                        $embedUrl = html_entity_decode($matches[1]);
                    }
                }
                
                // Try extraction
                if (VideoExtractorService::canExtract($embedUrl)) {
                    $extractedVideo = VideoExtractorService::extract($embedUrl);
                }
            }
        }

        return view('livewire.video-player', [
            'episode' => $episode,
            'selectedServer' => $selectedServer,
            'selectedServerId' => $this->selectedServerId,
            'extractedVideo' => $extractedVideo,
        ]);
    }
}
