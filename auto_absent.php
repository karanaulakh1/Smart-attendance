<?php
/**
 * auto_absent.php
 * Called by cron-job.org every night at 23:59 IST
 * Works with TiDB + Render
 */

// ── SECRET KEY PROTECTION ──────────────────────────────
$secret = "smartattend2026"; // change this to anything you want

if(!isset($_GET['key']) || $_GET['key'] !== $secret){
    http_response_code(403);
    echo "Forbidden";
    exit();
}
// ───────────────────────────────────────────────────────

include("database.php");

date_default_timezone_set("Asia/Kolkata");

$today = date("Y-m-d");
$time  = "23:59:00";

// ── CHECK LOCK ─────────────────────────────────────────
$check = mysqli_query($conn,
    "SELECT * FROM auto_absent_log WHERE date='$today'"
);

if($check && mysqli_num_rows($check) > 0){
    echo "Already ran today ($today). No action taken.";
    exit();
}
// ───────────────────────────────────────────────────────

// ── MARK ABSENT ────────────────────────────────────────
$result = mysqli_query($conn, "
    INSERT INTO attendance (student_id, course, status, date, time)
    SELECT s.student_id, s.course, 'Absent', '$today', '$time'
    FROM students s
    WHERE s.student_id NOT IN (
        SELECT a.student_id
        FROM attendance a
        WHERE a.date = '$today'
    )
");

if(!$result){
    echo "Error: " . mysqli_error($conn);
    exit();
}
// ───────────────────────────────────────────────────────

// ── WRITE LOCK ─────────────────────────────────────────
mysqli_query($conn,
    "INSERT INTO auto_absent_log (date) VALUES ('$today')"
);
// ───────────────────────────────────────────────────────

$count = mysqli_affected_rows($conn);
echo "Done. $count student(s) marked Absent for $today.";
?>