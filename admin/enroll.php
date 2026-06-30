<?php
/**
 * admin/enroll.php
 * Two roles:
 *
 * 1) Called by ADMIN PANEL (manage_members.php "Enroll" button)
 *    -> ?action=request&student_id=X&group=Y
 *    -> Creates a pending enroll request for the ESP32 to pick up
 *
 * 2) Called by ESP32 after successfully storing the fingerprint
 *    -> ?student_id=X&fingerprint_id=Y
 *    -> Saves fingerprint_id into the correct member table (any group)
 *       and clears the pending request
 */

session_start();
include '../database.php';

date_default_timezone_set("Asia/Kolkata");

// ════════════════════════════════════════════════════════
// MODE 1 — ADMIN REQUESTS AN ENROLLMENT (sets pending slot)
// ════════════════════════════════════════════════════════
if(isset($_GET['action']) && $_GET['action'] === 'request'){

    if(!isset($_SESSION['admin'])){
        http_response_code(403);
        echo "Forbidden — admin login required";
        exit();
    }

    $student_id = $_GET['student_id'] ?? '';
    $group      = $_GET['group']      ?? '';

    if(empty($student_id) || empty($group)){
        echo "Missing student_id or group";
        exit();
    }

    // Validate group exists
    $gq = mysqli_query($conn, "SELECT * FROM groups_registry WHERE table_name='$group'");
    if(!$gq || mysqli_num_rows($gq) === 0){
        echo "Invalid group";
        exit();
    }
    $group_info = mysqli_fetch_assoc($gq);
    $member_table = $group_info['table_name'];

    // Find the member to get/assign a fingerprint slot
    $mq = mysqli_query($conn, "SELECT * FROM `$member_table` WHERE student_id='$student_id'");
    if(!$mq || mysqli_num_rows($mq) === 0){
        echo "Member not found in $group";
        exit();
    }
    $member = mysqli_fetch_assoc($mq);

    // Determine fingerprint slot to use:
    // - If member already has a fingerprint_id, reuse that slot (re-enroll)
    // - Otherwise, find the next free slot number (1-127) across ALL groups
    if(!empty($member['fingerprint_id'])){
        $slot = (int)$member['fingerprint_id'];
    } else {
        $slot = findNextFreeSlot($conn);
        if($slot === -1){
            echo "No free fingerprint slots available (max 127)";
            exit();
        }
    }

    // Clear any old pending requests, then create new one
    mysqli_query($conn, "DELETE FROM enroll_requests");
    mysqli_query($conn, "
        INSERT INTO enroll_requests (student_id, group_table, fingerprint_id, requested_at)
        VALUES ('$student_id', '$member_table', '$slot', NOW())
    ");

    echo "Enrollment requested. Slot: $slot. Waiting for ESP32 scan...";
    exit();
}

// ════════════════════════════════════════════════════════
// MODE 2 — ESP32 CONFIRMS ENROLLMENT SAVED
// ════════════════════════════════════════════════════════

$student_id     = $_GET['student_id']     ?? '';
$fingerprint_id = $_GET['fingerprint_id'] ?? '';

if(empty($student_id) || empty($fingerprint_id)){
    echo "Missing parameters";
    exit();
}

// Find which group this pending request belongs to
$pending = mysqli_query($conn,
    "SELECT * FROM enroll_requests WHERE fingerprint_id='$fingerprint_id' ORDER BY id DESC LIMIT 1"
);

if(!$pending || mysqli_num_rows($pending) === 0){
    echo "No pending enroll request found for this slot";
    exit();
}

$req          = mysqli_fetch_assoc($pending);
$member_table = $req['group_table'];
$real_student_id = $req['student_id']; // use the ID from the original request, not ESP32 param

// Validate table exists
$tbl_check = mysqli_query($conn, "SHOW TABLES LIKE '$member_table'");
if(!$tbl_check || mysqli_num_rows($tbl_check) === 0){
    echo "Member table not found: $member_table";
    exit();
}

// Save fingerprint_id to the correct member in the correct group table
$update = mysqli_query($conn,
    "UPDATE `$member_table` SET fingerprint_id='$fingerprint_id' WHERE student_id='$real_student_id'"
);

if($update){
    // Clear the pending request now that it's fulfilled
    mysqli_query($conn, "DELETE FROM enroll_requests WHERE id='{$req['id']}'");
    echo "Fingerprint $fingerprint_id saved for $real_student_id in $member_table";
} else {
    echo "Database error: " . mysqli_error($conn);
}

// ════════════════════════════════════════════════════════
// HELPER — find next free fingerprint slot across ALL groups
// ════════════════════════════════════════════════════════
function findNextFreeSlot($conn){
    $used = [];

    $groups = mysqli_query($conn, "SELECT * FROM groups_registry");
    while($g = mysqli_fetch_assoc($groups)){
        $mt = $g['table_name'];
        $tbl_check = mysqli_query($conn, "SHOW TABLES LIKE '$mt'");
        if(!$tbl_check || mysqli_num_rows($tbl_check) === 0) continue;

        $res = mysqli_query($conn, "SELECT fingerprint_id FROM `$mt` WHERE fingerprint_id != '' AND fingerprint_id IS NOT NULL");
        while($row = mysqli_fetch_assoc($res)){
            $used[] = (int)$row['fingerprint_id'];
        }
    }

    for($i = 1; $i <= 127; $i++){
        if(!in_array($i, $used)) return $i;
    }
    return -1; // all slots full
}
?>