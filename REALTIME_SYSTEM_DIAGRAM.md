# Real-Time Episode Updates - System Diagram

## SSE (Server-Sent Events) Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                     ADMIN PANEL (Filament)                      │
│                                                                   │
│  ┌──────────────┐      ┌──────────────┐      ┌──────────────┐  │
│  │  Create      │      │  Create      │      │  Update      │  │
│  │  Anime       │─────▶│  Episode     │─────▶│  VideoServer │  │
│  └──────────────┘      └──────────────┘      └──────────────┘  │
│                              │                       │           │
│                              └───────┬───────────────┘           │
│                                      │                           │
└──────────────────────────────────────┼───────────────────────────┘
                                       │
                                       ▼
                ┌─────────────────────────────────────┐
                │  Laravel Model Event Listeners      │
                │  (Episode & VideoServer Models)     │
                │                                     │
                │  ┌──────────────────────────────┐   │
                │  │ Cache::forget(               │   │
                │  │  'latest_episodes_hash'      │   │
                │  │ )                            │   │
                │  └──────────────────────────────┘   │
                └─────────────────────────────────────┘
                                       │
                                       ▼
    ┌──────────────────────────────────────────────────────────────┐
    │           EpisodeStreamController (SSE Server)              │
    │                                                              │
    │  while (time < maxTime) {                                   │
    │    currentHash = generateHash(episodes);                    │
    │                                                              │
    │    if (lastHash !== currentHash) {                          │
    │      echo "data: episodes_updated event\n\n";              │
    │      lastHash = currentHash;                               │
    │    }                                                         │
    │                                                              │
    │    sleep(2); // Check every 2 seconds                       │
    │  }                                                           │
    └──────────────────────────────────────────────────────────────┘
                                       │
                ┌──────────────────────┼──────────────────────┐
                │                      │                      │
                ▼                      ▼                      ▼
    ┌─────────────────────┐ ┌──────────────────┐ ┌─────────────────────┐
    │   USER BROWSER 1    │ │  USER BROWSER 2  │ │   USER BROWSER 3    │
    │                     │ │                  │ │                     │
    │ EventSource listen: │ │ EventSource      │ │ EventSource listen: │
    │ /api/episodes/      │ │ listen:          │ │ /api/episodes/      │
    │ stream              │ │ /api/episodes/   │ │ stream              │
    │                     │ │ stream           │ │                     │
    │ ▼ episodes_updated  │ │ ▼ episodes_      │ │ ▼ episodes_updated  │
    │   event             │ │ updated event    │ │   event             │
    │                     │ │                  │ │                     │
    │ fetch('/api/        │ │ fetch('/api/     │ │ fetch('/api/        │
    │ episodes/latest')   │ │ episodes/        │ │ episodes/latest')   │
    │                     │ │ latest')         │ │                     │
    │ ▼ Get new HTML      │ │ ▼ Get new HTML   │ │ ▼ Get new HTML      │
    │                     │ │                  │ │                     │
    │ Update grid:        │ │ Update grid:     │ │ Update grid:        │
    │ Fade out → Insert → │ │ Fade out →       │ │ Fade out → Insert → │
    │ Fade in             │ │ Insert → Fade in │ │ Fade in             │
    │                     │ │                  │ │                     │
    │ Show toast:         │ │ Show toast:      │ │ Show toast:         │
    │ "📺 Episode baru    │ │ "📺 Episode baru │ │ "📺 Episode baru    │
    │ ditemukan!"         │ │ ditemukan!"      │ │ ditemukan!"         │
    └─────────────────────┘ └──────────────────┘ └─────────────────────┘
```

## Data Flow - New Episode Created

```
STEP 1: Admin Creates Episode
───────────────────────────────
  Admin clicks "Create Episode" in Filament
        │
        ▼
  Episode::create([...])
        │
        ▼
  Episode model 'created' event fires
        │
        ▼
  Cache::forget('latest_episodes_hash')

STEP 2: Stream Detection
────────────────────────
  SSE stream checks cache (every 2 seconds)
        │
        ▼
  Finds cache was cleared
        │
        ▼
  Fetches fresh episodes from DB
        │
        ▼
  Generates new hash
        │
        ▼
  lastHash !== currentHash ✓
        │
        ▼
  Send event: "episodes_updated"

STEP 3: Client Response
──────────────────────
  EventSource receives 'episodes_updated' event
        │
        ▼
  fetch('/api/episodes/latest')
        │
        ▼
  Receive HTML with new episode card
        │
        ▼
  Grid fade transition:
    ├─ Opacity: 1 → 0.7 (150ms)
    ├─ Insert new HTML
    └─ Opacity: 0.7 → 1 (300ms)
        │
        ▼
  showNotification('📺 Episode baru ditemukan!')
        │
        ▼
  Toast appears (4 seconds)
```

## Connection Lifecycle

```
Timeline: User visits /episodes/latest

T=0s     : Page loads
         : JavaScript initializes
         : Load localStorage preference
         : If preference === 'enabled': startRealtimeUpdates()

T=0.1s   : EventSource opens connection to /api/episodes/stream
         : Server sends handshake

T=2s     : Server checks episode hash (no change)

T=4s     : Server checks episode hash (no change)

T=10s    : Server sends heartbeat comment
         : JavaScript keeps connection alive

T=20s    : Admin creates new episode
         : Database event fires
         : Cache cleared
         : Server detects hash change

T=20.2s  : Server sends "episodes_updated" event
         : JavaScript receives event
         : Fetches new grid HTML
         : Updates DOM

T=20.5s  : Toast notification appears
         : User sees "📺 Episode baru ditemukan!"

T=25s    : User navigates away or closes tab
         : beforeunload event fires
         : stopRealtimeUpdates() closes connection

T=30min  : If user still connected, server closes stream
         : (timeout after 30 minutes max)
```

## Cache Strategy

```
┌─────────────────────────────────────────────────────┐
│ Latest Episodes Hash Cache                         │
├─────────────────────────────────────────────────────┤
│ Key: 'latest_episodes_hash'                        │
│ Value: SHA256(json_encode(episodes))               │
│ TTL: 1 minute                                      │
│                                                    │
│ Cache Cleared When:                                │
│ ├─ Episode::created()                             │
│ ├─ Episode::updated()                             │
│ ├─ Episode::deleted()                             │
│ ├─ VideoServer::created()                         │
│ ├─ VideoServer::updated()                         │
│ └─ VideoServer::deleted()                         │
│                                                    │
│ Regenerated When:                                  │
│ ├─ Next SSE check (if cache empty)                │
│ └─ /api/episodes/latest API call                  │
└─────────────────────────────────────────────────────┘
```

## Network Traffic

```
BEFORE (Auto-Refresh every 30 seconds):
───────────────────────────────────────
  Every 30 seconds:
  │
  ├─ Full page request (20-50 KB)
  ├─ JavaScript parse & execute
  ├─ DOM rebuild
  ├─ Image lazy load requests
  └─ Memory spike: High (full page reflow)

  Result: Every 30s = 2KB/min * 30s = massive traffic

AFTER (SSE Real-time):
──────────────────────
  Continuous:
  │
  ├─ SSE heartbeat: 200 bytes (every 10 seconds)
  │  = 1.2 KB/min heartbeat only
  │
  On change (rare):
  │
  ├─ SSE event: 500 bytes
  ├─ API fetch: 15-30 KB (only new cards)
  ├─ DOM update: Only episode cards
  └─ Memory spike: Low (only grid reflow)

  Result: Only sends data when content changes!
```

## Toggle State Management

```
┌─────────────────────────────────────────────────────┐
│ localStorage                                       │
├─────────────────────────────────────────────────────┤
│ Key: 'nipnime_latest_episodes_realtime'           │
│                                                    │
│ Values:                                            │
│ ├─ 'enabled'  : Live Updates ON (default)         │
│ ├─ 'disabled' : Live Updates OFF                  │
│ └─ (empty)    : Never toggled (use default)       │
│                                                    │
│ Flow:                                              │
│ ├─ Page load → Check localStorage                 │
│ ├─ If 'enabled' → Auto-start SSE                  │
│ ├─ User toggles → Update localStorage             │
│ ├─ Close tab → Preference persists                │
│ └─ Return to page → Resume SSE if was enabled     │
└─────────────────────────────────────────────────────┘
```

## Fallback & Error Handling

```
SSE Connection Flow:
────────────────────
  Try: EventSource('/api/episodes/stream')
    │
    ├─ Success ✓
    │   └─ Listen for events
    │
    ├─ Network Error
    │   └─ onerror fires
    │       └─ stopRealtimeUpdates()
    │       └─ Wait 5 seconds
    │       └─ Retry if toggle still ON
    │
    └─ Browser doesn't support EventSource
        └─ Graceful degradation
        └─ Toggle functionality maintained
        └─ User can still refresh manually
```

---

**Implementation**: ✅ Complete & Tested  
**Browser Compatibility**: ✅ All modern browsers  
**Performance**: ✅ Optimized (bandwidth & CPU)
