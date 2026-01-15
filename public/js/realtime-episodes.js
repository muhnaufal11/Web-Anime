(function() {
    var episodesGrid = document.getElementById('latestEpisodesGrid');
    if (!episodesGrid) return;
    
    var lastEpisodeIds = [];
    var pollingInterval = null;
    var POLL_INTERVAL = 5000;
    var apiUrl = episodesGrid.getAttribute('data-api-url');
    
    if (!apiUrl) return;
    
    // Get initial episode IDs
    var cards = episodesGrid.querySelectorAll('.episode-card');
    for (var i = 0; i < cards.length; i++) {
        var id = cards[i].getAttribute('data-episode-id');
        if (id) lastEpisodeIds.push(id);
    }

    function startPolling() {
        if (pollingInterval) return;
        pollingInterval = setInterval(checkForUpdates, POLL_INTERVAL);
    }
    
    function stopPolling() {
        if (pollingInterval) {
            clearInterval(pollingInterval);
            pollingInterval = null;
        }
    }

    function checkForUpdates() {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', apiUrl, true);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                try {
                    var data = JSON.parse(xhr.responseText);
                    if (data.episode_ids && data.html) {
                        var newIds = [];
                        for (var i = 0; i < data.episode_ids.length; i++) {
                            var id = String(data.episode_ids[i]);
                            if (lastEpisodeIds.indexOf(id) === -1) {
                                newIds.push(id);
                            }
                        }
                        
                        if (newIds.length > 0) {
                            updateGrid(data.html, newIds);
                            showNotification('Episode baru tersedia!');
                            lastEpisodeIds = [];
                            for (var j = 0; j < data.episode_ids.length; j++) {
                                lastEpisodeIds.push(String(data.episode_ids[j]));
                            }
                        }
                    }
                } catch (e) {}
            }
        };
        xhr.send();
    }

    function updateGrid(html, newIds) {
        var tempDiv = document.createElement('div');
        tempDiv.innerHTML = html;
        var newItems = tempDiv.querySelectorAll('.episode-card');
        
        episodesGrid.style.opacity = '0.5';
        episodesGrid.style.transition = 'opacity 0.2s';
        
        setTimeout(function() {
            episodesGrid.innerHTML = '';
            
            for (var i = 0; i < newItems.length; i++) {
                (function(item, index) {
                    var episodeId = item.getAttribute('data-episode-id');
                    var isNew = newIds.indexOf(String(episodeId)) !== -1;
                    var clone = item.cloneNode(true);
                    clone.style.opacity = '0';
                    clone.style.transform = 'translateY(10px)';
                    episodesGrid.appendChild(clone);
                    
                    setTimeout(function() {
                        clone.style.transition = 'all 0.3s ease';
                        clone.style.opacity = '1';
                        clone.style.transform = 'translateY(0)';
                        
                        if (isNew) {
                            var cardDiv = clone.querySelector('div');
                            if (cardDiv) {
                                cardDiv.style.boxShadow = '0 0 20px rgba(34, 197, 94, 0.5)';
                                cardDiv.style.borderColor = 'rgb(34, 197, 94)';
                                setTimeout(function() {
                                    cardDiv.style.transition = 'all 1s ease';
                                    cardDiv.style.boxShadow = '';
                                    cardDiv.style.borderColor = '';
                                }, 3000);
                            }
                        }
                    }, 30 + (index * 50));
                })(newItems[i], i);
            }
            
            episodesGrid.style.opacity = '1';
        }, 200);
    }

    function showNotification(message) {
        var old = document.querySelector('.realtime-toast');
        if (old) old.parentNode.removeChild(old);
        
        var toast = document.createElement('div');
        toast.className = 'realtime-toast';
        toast.style.cssText = 'position:fixed;bottom:20px;right:20px;padding:16px 24px;background:linear-gradient(135deg,#16a34a,#15803d);color:#fff;font-weight:bold;border-radius:12px;box-shadow:0 10px 40px rgba(0,0,0,0.3);z-index:9999;transform:translateX(120%);transition:transform 0.4s ease;';
        toast.innerHTML = '<span style="margin-right:8px">📺</span>' + message;
        document.body.appendChild(toast);
        
        setTimeout(function() { toast.style.transform = 'translateX(0)'; }, 10);
        setTimeout(function() {
            toast.style.transform = 'translateX(120%)';
            setTimeout(function() { if(toast.parentNode) toast.parentNode.removeChild(toast); }, 400);
        }, 4000);
    }

    startPolling();

    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            stopPolling();
        } else {
            startPolling();
            checkForUpdates();
        }
    });
    
    window.addEventListener('beforeunload', stopPolling);
})();
