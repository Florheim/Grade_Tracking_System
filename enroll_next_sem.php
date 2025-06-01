<?php
$current_year = date('Y');
$current_month = date('n');

$next_academic_year = '';
$next_semester = '';

if ($current_month >= 8) {
    $next_academic_year = $current_year . '-' . ($current_year + 1);
    $next_semester = '2nd';
} elseif ($current_month >= 2 && $current_month <= 5) { 
    $next_academic_year = ($current_year -1) . '-' . $current_year; 
    $next_semester = 'Mid Year';
} else { 
    $next_academic_year = $current_year . '-' . ($current_year + 1);
    $next_semester = '1st';
}
header("Location: add_subject.php?academic_year=" . urlencode($next_academic_year) . "&semester=" . urlencode($next_semester));
exit;
?>