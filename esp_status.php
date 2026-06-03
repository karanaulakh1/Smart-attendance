<?php
/**
 * esp_status.php
 * Two uses:
 *   1. ESP32 heartbeat pings every 5 seconds → updates last_ping
 *      Call: https://yoursite.com/esp_status.php?action=ping&key=smartattend2026
 *
 *   2. Frontend polls every second via AJAX to show live status
 *      Call: https://yoursite.com/esp_status.php?action=status
 */error_reporting(E_ALL);
ini_set('display_errors', 1);

include("database.php");

date_default_timezone_set("Asia/Kolkata");

$action = isset($_GET['action']) ? $_GET['action'] : 'status';

// ── PING (called by ESP32 heartbeat every 5s) ───────────────────────────
if($action === 'ping'){

    $secret = "smartattend2026";
    if(!isset($_GET['key']) || $_GET['key'] !== $secret){
        http_response_code(403);
        echo "Forbidden";
        exit();
    }

    $now = date("Y-m-d H:i:s");

    $result = mysqli_query($conn,
        "UPDATE esp32_status SET last_ping='$now' WHERE id=1"
    );

    if($result){
        echo "OK";
    } else {
        http_response_code(500);
        echo "DB Error: " . mysqli_error($conn);
    }
    exit();
}

// ── STATUS (polled by frontend every second) ────────────────────────────
if($action === 'status'){

    header('Content-Type: application/json');
    header('Cache-Control: no-store, no-cache, must-revalidate');

    $row = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT * FROM esp32_status WHERE id=1"
    ));

    if(!$row || !$row['last_ping']){
        echo json_encode([
            'online'      => false,
            'status'      => 'offline',
            'label'       => 'Never Connected',
            'last_ping'   => null,
            'seconds_ago' => null,
            'time_ago'    => 'Never',
            'device_name' => $row['device_name'] ?? 'ESP32 Device',
        ]);
        exit();
    }

    $last   = strtotime($row['last_ping']);
    $now    = time();
    $diff   = $now - $last;

    // Heartbeat is every 5s → if no ping for 15s, consider offline
    // Gives 3 missed heartbeats before marking offline
    $online = ($diff <= 15);

    // Human-readable time ago
    if($diff < 10){
        $ago = "Just now";
    } elseif($diff < 60){
        $ago = $diff . "s ago";
    } elseif($diff < 3600){
        $ago = floor($diff / 60) . "m ago";
    } else {
        $ago = floor($diff / 3600) . "h ago";
    }

    echo json_encode([
        'online'      => $online,
        'status'      => $online ? 'online' : 'offline',
        'label'       => $online ? 'Online'  : 'Offline',
        'last_ping'   => date("d M Y, h:i:s A", $last),
        'seconds_ago' => $diff,
        'time_ago'    => $ago,
        'device_name' => $row['device_name'],
    ]);
    exit();
}

http_response_code(400);
echo json_encode(['error' => 'Unknown action']);
?>