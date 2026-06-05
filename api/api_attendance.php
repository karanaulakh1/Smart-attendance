<?php
/**
 * mark_attendance.php
 * ESP32 endpoint — NO time window restriction
 *
 * 1st scan of the day = IN  (status = Present, in_time = now)
 * 2nd scan of the day = OUT (out_time = now, working_hours calculated)
 * 3rd+ scan           = Already Marked
 *
 * URL: https://yoursite.onrender.com/mark_attendance.php?fingerprint_id=1&key=smartattend2026
 */

include("../database.php");
date_default_timezone_set("Asia/Kolkata");

// ── SECRET KEY ──────────────────────────────────────────────────────────
$secret = "smartattend2026";
if(!isset($_GET['key']) || $_GET['key'] !== $secret){
    http_response_code(403);
    echo "Forbidden";
    exit();
}
// ───────────────────────────────────────────────────────────────────────

if(!isset($_GET['fingerprint_id'])){
    echo "No Fingerprint ID";
    exit();
}

$fingerprint_id = $_GET['fingerprint_id'];
$date           = date("Y-m-d");
$time_now       = date("h:i A");   // e.g. 09:15 AM
$now_dt         = date("Y-m-d H:i:s");

// ── UPDATE ESP32 LAST PING ──────────────────────────────────────────────
mysqli_query($conn,
    "UPDATE esp32_status SET last_ping='$now_dt' WHERE id=1"
);
// ───────────────────────────────────────────────────────────────────────

// ── FIND MEMBER ACROSS ALL GROUPS ────────────────────────────────────────
$groups = mysqli_query($conn, "SELECT * FROM groups_registry");
$found_member = null;
$found_att    = null;
$found_group  = null;

while($group = mysqli_fetch_assoc($groups)){
    $mt = $group['table_name'];
    $at = $group['attendance_table'];

    $member = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT * FROM `$mt` WHERE fingerprint_id='$fingerprint_id'"
    ));

    if($member){
        $found_member = $member;
        $found_att    = $at;
        $found_group  = $group['group_name'];
        break;
    }
}

if(!$found_member){
    echo "Student Not Found";
    exit();
}
// ───────────────────────────────────────────────────────────────────────

$student_id = $found_member['student_id'];
$course     = $found_member['course'];

// ── CHECK EXISTING RECORD FOR TODAY ──────────────────────────────────────
$existing = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM `$found_att`
     WHERE student_id='$student_id' AND date='$date'"
));
// ───────────────────────────────────────────────────────────────────────

// ── NO RECORD → FIRST SCAN = IN ─────────────────────────────────────────
if(!$existing){

    mysqli_query($conn,
        "INSERT INTO `$found_att`
         (student_id, course, status, date, time, in_time, out_time, working_hours)
         VALUES
         ('$student_id','$course','Present','$date','$time_now','$time_now',NULL,NULL)"
    );

    echo "IN: $time_now";
    exit();
}
// ───────────────────────────────────────────────────────────────────────

// ── HAS IN BUT NO OUT → SECOND SCAN = OUT ───────────────────────────────
if($existing['in_time'] && empty($existing['out_time'])){

    // Calculate working hours
    $in_obj  = DateTime::createFromFormat("h:i A", $existing['in_time']);
    $out_obj = new DateTime();

    if($in_obj && $out_obj && $out_obj > $in_obj){
        $diff    = $in_obj->diff($out_obj);
        $working = sprintf("%02d:%02d", ($diff->days * 24) + $diff->h, $diff->i);
    } else {
        $working = "00:00";
    }

    mysqli_query($conn,
        "UPDATE `$found_att`
         SET out_time='$time_now', working_hours='$working'
         WHERE id='{$existing['id']}'"
    );

    echo "OUT: $time_now — Hours: $working";
    exit();
}
// ───────────────────────────────────────────────────────────────────────

// ── ALREADY HAS BOTH IN AND OUT ─────────────────────────────────────────
echo "Already Marked — IN: {$existing['in_time']} OUT: {$existing['out_time']}";
// ───────────────────────────────────────────────────────────────────────
?>