#!/bin/bash

# NipNime Health Monitor - Host Level
# Runs outside Docker to detect when website/container is down
# Uses Discord message editing to keep channel clean

WEBHOOK_URL="https://discordapp.com/api/webhooks/1458066647205281833/KxSc_6QX2PV4ACkgeISX-NMm-XGJsH_bUbZBssPYgeHn0CJYhLTvQ8YByDiC-WMAYnRV"
WEBSITE_URL="https://nipnime.my.id"
API_URL="https://nipnime.my.id/api/health"
STATUS_FILE="/tmp/nipnime_health_status"
FAILURE_FILE="/tmp/nipnime_health_failures"
DOWN_SINCE_FILE="/tmp/nipnime_down_since"
MESSAGE_ID_FILE="/tmp/nipnime_discord_message_id"

# Get current timestamp
TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')

# Function to send new Discord message and save message ID
send_discord_new() {
    local payload="$1"
    
    response=$(curl -s -X POST "$WEBHOOK_URL?wait=true" \
        -H "Content-Type: application/json" \
        -d "$payload")
    
    # Extract message ID from response
    message_id=$(echo "$response" | grep -o '"id":"[^"]*"' | head -1 | cut -d'"' -f4)
    
    if [ -n "$message_id" ]; then
        echo "$message_id" > "$MESSAGE_ID_FILE"
        echo "[$TIMESTAMP] New message sent, ID: $message_id"
    fi
}

# Function to edit existing Discord message
edit_discord_message() {
    local payload="$1"
    local message_id="$2"
    
    http_code=$(curl -s -o /dev/null -w "%{http_code}" -X PATCH "$WEBHOOK_URL/messages/$message_id" \
        -H "Content-Type: application/json" \
        -d "$payload")
    
    if [ "$http_code" = "200" ]; then
        echo "[$TIMESTAMP] Message edited successfully"
    else
        echo "[$TIMESTAMP] Edit failed (HTTP $http_code), sending new message..."
        rm -f "$MESSAGE_ID_FILE"
        send_discord_new "$payload"
    fi
}

# Function to check endpoint
check_endpoint() {
    local url="$1"
    local timeout=10
    
    start_time=$(date +%s%3N 2>/dev/null || date +%s)
    response=$(curl -s -o /dev/null -w "%{http_code}" --connect-timeout $timeout --max-time $timeout "$url" 2>/dev/null)
    end_time=$(date +%s%3N 2>/dev/null || date +%s)
    response_time=$((end_time - start_time))
    
    # Fallback if milliseconds not supported
    [ $response_time -gt 100000 ] && response_time=$((response_time / 1000))
    
    if [ "$response" = "200" ]; then
        echo "ok:$response_time"
    else
        echo "fail:$response:$response_time"
    fi
}

# Function to check Docker container
check_docker() {
    if docker ps --format '{{.Names}}' 2>/dev/null | grep -q "nipnime-app"; then
        echo "ok"
    else
        echo "fail"
    fi
}

# Main health check
main_result=$(check_endpoint "$WEBSITE_URL")
main_check=$(echo "$main_result" | cut -d: -f1)
main_time=$(echo "$main_result" | cut -d: -f2)

api_result=$(check_endpoint "$API_URL")
api_check=$(echo "$api_result" | cut -d: -f1)
api_time=$(echo "$api_result" | cut -d: -f2)

docker_check=$(check_docker)

# Determine overall health
if [ "$main_check" = "ok" ] && [ "$docker_check" = "ok" ]; then
    current_status="healthy"
else
    current_status="unhealthy"
fi

# Get previous status
previous_status="unknown"
if [ -f "$STATUS_FILE" ]; then
    previous_status=$(cat "$STATUS_FILE")
fi

# Get failure count
failures=0
if [ -f "$FAILURE_FILE" ]; then
    failures=$(cat "$FAILURE_FILE")
fi

# Get message ID
message_id=""
if [ -f "$MESSAGE_ID_FILE" ]; then
    message_id=$(cat "$MESSAGE_ID_FILE")
fi

echo "[$TIMESTAMP] Main: $main_check (${main_time}ms) | API: $api_check (${api_time}ms) | Docker: $docker_check | Status: $current_status"

# Build status message
if [ "$current_status" = "healthy" ]; then
    # Reset failures
    echo "0" > "$FAILURE_FILE"
    echo "healthy" > "$STATUS_FILE"
    
    # Calculate uptime if was down before
    uptime_info=""
    if [ -f "$DOWN_SINCE_FILE" ]; then
        down_since=$(cat "$DOWN_SINCE_FILE")
        down_start=$(date -d "$down_since" +%s 2>/dev/null)
        if [ -n "$down_start" ]; then
            down_seconds=$(( $(date +%s) - down_start ))
            
            if [ $down_seconds -ge 3600 ]; then
                hours=$((down_seconds / 3600))
                mins=$(( (down_seconds % 3600) / 60 ))
                uptime_info="Recovered after ${hours}h ${mins}m downtime"
            elif [ $down_seconds -ge 60 ]; then
                mins=$((down_seconds / 60))
                secs=$((down_seconds % 60))
                uptime_info="Recovered after ${mins}m ${secs}s downtime"
            else
                uptime_info="Recovered after ${down_seconds}s downtime"
            fi
        fi
        rm -f "$DOWN_SINCE_FILE"
    fi
    
    description="Website berjalan normal"
    [ -n "$uptime_info" ] && description="$uptime_info"
    
    payload="{
        \"embeds\": [{
            \"title\": \"🟢 Status: ONLINE\",
            \"description\": \"**nipnime.my.id** - $description\",
            \"color\": 5763719,
            \"fields\": [
                {\"name\": \"🌐 Website\", \"value\": \"✅ OK (${main_time}ms)\", \"inline\": true},
                {\"name\": \"🔌 API\", \"value\": \"✅ OK (${api_time}ms)\", \"inline\": true},
                {\"name\": \"🐳 Docker\", \"value\": \"✅ Running\", \"inline\": true}
            ],
            \"footer\": {\"text\": \"Last checked\"},
            \"timestamp\": \"$(date -u +%Y-%m-%dT%H:%M:%SZ)\"
        }]
    }"
else
    # Increment failures
    failures=$((failures + 1))
    echo "$failures" > "$FAILURE_FILE"
    
    # Only mark as down after 2 consecutive failures
    if [ $failures -ge 2 ]; then
        if [ "$previous_status" != "unhealthy" ]; then
            echo "$TIMESTAMP" > "$DOWN_SINCE_FILE"
        fi
        echo "unhealthy" > "$STATUS_FILE"
        
        # Calculate downtime
        downtime_info="Just detected"
        if [ -f "$DOWN_SINCE_FILE" ]; then
            down_since=$(cat "$DOWN_SINCE_FILE")
            down_start=$(date -d "$down_since" +%s 2>/dev/null)
            if [ -n "$down_start" ]; then
                down_seconds=$(( $(date +%s) - down_start ))
                
                if [ $down_seconds -ge 3600 ]; then
                    hours=$((down_seconds / 3600))
                    mins=$(( (down_seconds % 3600) / 60 ))
                    downtime_info="Down for ${hours}h ${mins}m"
                elif [ $down_seconds -ge 60 ]; then
                    mins=$((down_seconds / 60))
                    secs=$((down_seconds % 60))
                    downtime_info="Down for ${mins}m ${secs}s"
                elif [ $down_seconds -gt 0 ]; then
                    downtime_info="Down for ${down_seconds}s"
                fi
            fi
        fi
        
        # Build status values
        main_status="❌ FAIL"
        [ "$main_check" = "ok" ] && main_status="✅ OK (${main_time}ms)"
        
        api_status="❌ FAIL"
        [ "$api_check" = "ok" ] && api_status="✅ OK (${api_time}ms)"
        
        docker_status="❌ Stopped"
        [ "$docker_check" = "ok" ] && docker_status="✅ Running"
        
        payload="{
            \"embeds\": [{
                \"title\": \"🔴 Status: DOWN\",
                \"description\": \"**nipnime.my.id** - $downtime_info\",
                \"color\": 15548997,
                \"fields\": [
                    {\"name\": \"🌐 Website\", \"value\": \"$main_status\", \"inline\": true},
                    {\"name\": \"🔌 API\", \"value\": \"$api_status\", \"inline\": true},
                    {\"name\": \"🐳 Docker\", \"value\": \"$docker_status\", \"inline\": true}
                ],
                \"footer\": {\"text\": \"Last checked\"},
                \"timestamp\": \"$(date -u +%Y-%m-%dT%H:%M:%SZ)\"
            }]
        }"
    else
        echo "[$TIMESTAMP] Failure $failures/2 - waiting for confirmation..."
        exit 0
    fi
fi

# Send or edit message
if [ -n "$message_id" ]; then
    # Try to edit existing message
    edit_discord_message "$payload" "$message_id"
else
    # Send new message
    send_discord_new "$payload"
fi
