<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once 'config.php';

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit;
}

$student_id = $_SESSION['student_id'];
$student_name = $_SESSION['student_name'] ?? 'Student'; 

if (!isset($_SESSION['student_name'])) {
    $stmt_name = $conn->prepare("SELECT name FROM students WHERE id = ?");
    if ($stmt_name) {
        $stmt_name->bind_param("i", $student_id);
        $stmt_name->execute();
        $result_name = $stmt_name->get_result();
        if ($row_name = $result_name->fetch_assoc()) {
            $_SESSION['student_name'] = $row_name['name'];
            $student_name = $row_name['name'];
        }
        $stmt_name->close();
    }
}

$enrolled_subjects = [];
$current_academic_year = date('Y') . '-' . (date('Y') + 1); 
$current_semester = '1st'; 

if (isset($_GET['academic_year']) && isset($_GET['semester'])) {
    $current_academic_year = htmlspecialchars(trim($_GET['academic_year']));
    $current_semester = htmlspecialchars(trim($_GET['semester']));
} else {
    $month = date('n');
    $year = date('Y');

    if ($month >= 8 || $month <= 1) {
        $current_semester = '1st';
        $current_academic_year = ($month >= 8) ? $year . '-' . ($year + 1) : ($year - 1) . '-' . $year;
    } elseif ($month >= 2 && $month <= 5) { 
        $current_semester = '2nd';
        $current_academic_year = ($year - 1) . '-' . $year;
    } else { 
        $current_semester = 'Mid Year';
        $current_academic_year = ($year - 1) . '-' . $year;
    }
}
$sql_subjects = "
    SELECT
        sub.subject_code,
        sub.subject_name,
        e.academic_year,
        e.semester,
        g.midterm_grade,
        g.finals_grade
    FROM enrollments e
    JOIN subjects sub ON e.subject_id = sub.id
    LEFT JOIN grades g ON g.enrollment_id = e.id
    WHERE e.student_id = ?
    AND e.academic_year = ?
    AND e.semester = ?
    ORDER BY sub.subject_code
";

$stmt_subjects = $conn->prepare($sql_subjects);

if ($stmt_subjects === false) {
    die("Database prepare error for fetching subjects: " . $conn->error); 
}

$stmt_subjects->bind_param("iss", $student_id, $current_academic_year, $current_semester); 

if (!$stmt_subjects->execute()) {
    die("Database execute error for fetching subjects: " . $stmt_subjects->error); 
}

$result_subjects = $stmt_subjects->get_result();
$total_final_grades = 0;
$count_final_grades = 0;

while ($row = $result_subjects->fetch_assoc()) {
    $enrolled_subjects[] = $row;
    if (isset($row['finals_grade']) && $row['finals_grade'] !== null) {
        $total_final_grades += $row['finals_grade'];
        $count_final_grades++;
    }
}
$stmt_subjects->close();

$gwa = ($count_final_grades > 0) ? ($total_final_grades / $count_final_grades) : 0;
$gwa = round($gwa, 2);

$conn->close(); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Student Dashboard - Grade Tracking System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            max-width: 900px;
            margin: 20px auto;
        }
        h1,
        h2 {
            color: #333;
        }
        .success-message {
            color: green;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .error-message {
            color: red;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .gwa-display {
            background-color: #e6f7ff;
            border: 1px solid #91d5ff;
            padding: 10px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 1.2em;
            font-weight: bold;
            text-align: center;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 20px;
        }
        th,
        td {
            padding: 8px 12px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        form {
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: flex-end;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"],
        select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            flex-grow: 1;
            min-width: 150px;
        }
        button {
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background-color: #0056b3;
        }
        .action-buttons {
            margin-top: 20px;
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap; 
        }
        .action-buttons a,
        .action-buttons button {
            background-color: #f44336; 
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .action-buttons .enroll-btn {
            background-color: #2196F3; 
        }
        .action-buttons .view-sem-btn {
            background-color: #4CAF50; 
        }
        .view-subjects-controls {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            align-items: center;
        }

        .view-subjects-controls select,
        .view-subjects-controls input[type="text"] {
            flex-grow: 1;
            max-width: 200px; 
        }
    </style>
</head>
<body style="background-image: url('assets/Grade Track.jpg'); background-size: cover; background-repeat: no-repeat;">
    <div class="container">
        <h1>Welcome, <?= htmlspecialchars($student_name) ?>!</h1>
        <?php
        if (isset($_SESSION['success'])): ?>
            <p class="success-message"><?= htmlspecialchars($_SESSION['success']) ?></p>
        <?php unset($_SESSION['success']);
        endif;
        if (isset($_SESSION['error'])): ?>
            <p class="error-message"><?= htmlspecialchars($_SESSION['error']) ?></p>
        <?php unset($_SESSION['error']);
        endif;
        ?>

        <div class="action-buttons">
            <a href="enroll_next_sem.php" class="enroll-btn">Enroll New Subject</a>
            <a href="index.html" class="logout-btn">Logout</a>
        </div>

        <div class="gwa-display">
            Your General Weight Average: <?= htmlspecialchars($gwa) ?>
        </div>

        <h2>Your Enrolled Subjects and Grades</h2>

        <div class="view-subjects-controls">
            <form action="dashboard.php" method="GET" style="margin: 0; display:flex; gap: 10px; align-items:center;">
                <label for="display_academic_year">View Year:</label>
                <input type="text" id="display_academic_year" name="academic_year"
                    value="<?= htmlspecialchars($current_academic_year) ?>" placeholder="e.g., 2024-2025" style="width: 150px;">

                <label for="display_semester">View Semester:</label>
                <select id="display_semester" name="semester" style="width: 120px;">
                    <option value="1st" <?= ($current_semester == '1st') ? 'selected' : '' ?>>1st</option>
                    <option value="2nd" <?= ($current_semester == '2nd') ? 'selected' : '' ?>>2nd</option>
                    <option value="Mid Year" <?= ($current_semester == 'Mid Year') ? 'selected' : '' ?>>Midyear</option>
                </select>
                <button type="submit" class="view-sem-btn">View Subjects</button>
            </form>
        </div>


        <table>
            <thead>
                <tr>
                    <th>Subject Code</th>
                    <th>Subject Name</th>
                    <th>Academic Year</th>
                    <th>Semester</th>
                    <th>Midterm Grade</th>
                    <th>Final Grade</th>
                    </tr>
            </thead>
            <tbody>
                <?php if (empty($enrolled_subjects)): ?>
                    <tr>
                        <td colspan="6">No subjects enrolled for <?= htmlspecialchars($current_semester) ?>, <?= htmlspecialchars($current_academic_year) ?>.</td> </tr>
                <?php else: ?>
                    <?php foreach ($enrolled_subjects as $sub): ?>
                        <tr>
                            <td><?= htmlspecialchars($sub['subject_code']) ?></td>
                            <td><?= htmlspecialchars($sub['subject_name']) ?></td>
                            <td><?= htmlspecialchars($sub['academic_year'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($sub['semester'] ?? 'N/A') ?></td>
                            <td><?= $sub['midterm_grade'] !== null ? htmlspecialchars($sub['midterm_grade']) : 'N/A' ?></td>
                            <td><?= $sub['finals_grade'] !== null ? htmlspecialchars($sub['finals_grade']) : 'N/A' ?></td>
                            </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <h2>Enroll New Subject</h2>
        <form action="add_subject.php" method="post">
            <input type="text" name="subject_code" placeholder="Subject Code" required />
            <input type="text" name="subject_name" placeholder="Subject Name" required />
            <input type="text" name="academic_year" placeholder="Academic Year (e.g. 2025-2026)" value="<?= htmlspecialchars($current_academic_year) ?>" required />
            <select name="semester" required>
                <option value="">Select Semester</option>
                <option value="1st" <?= ($current_semester == '1st') ? 'selected' : '' ?>>1st</option>
                <option value="2nd" <?= ($current_semester == '2nd') ? 'selected' : '' ?>>2nd</option>
                <option value="Mid Year" <?= ($current_semester == 'Mid Year') ? 'selected' : '' ?>>Midyear</option>
            </select>
            <button type="submit">Add Subject</button>
        </form>
    </div>
</body>
</html>