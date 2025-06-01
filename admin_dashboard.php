<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

require_once 'config.php'; 

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$message = '';
$error = '';

if (isset($_SESSION['admin_dashboard_message'])) {
    $message = $_SESSION['admin_dashboard_message'];
    unset($_SESSION['admin_dashboard_message']);
}
if (isset($_SESSION['admin_dashboard_error'])) {
    $error = $_SESSION['admin_dashboard_error'];
    unset($_SESSION['admin_dashboard_error']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grades'])) {
    $grades_data = json_decode($_POST['grades'], true); 

    if ($grades_data) {
        foreach ($grades_data as $grade_entry) {
            $grade_id = intval($grade_entry['grade_id']);
            $midterm_grade = filter_var($grade_entry['midterm_grade'], FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
            $finals_grade = filter_var($grade_entry['finals_grade'], FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
            
            if ($midterm_grade === null || $finals_grade === null || $midterm_grade < 0 || $midterm_grade > 100 || $finals_grade < 0 || $finals_grade > 100) {
                $_SESSION['admin_dashboard_error'] = "Invalid grade values. Grades must be numeric and between 0 and 100.";
                continue; 
            }

            $update_sql = "UPDATE grades SET midterm_grade = ?, finals_grade = ? WHERE id = ?";
            $stmt = $conn->prepare($update_sql);

            if ($stmt === false) {
                $_SESSION['admin_dashboard_error'] = "Database prepare error for grade update: " . $conn->error;
            } else {
                $stmt->bind_param("ddi", $midterm_grade, $finals_grade, $grade_id); 
                if (!$stmt->execute()) {
                    $_SESSION['admin_dashboard_error'] = "Error updating grade: " . $stmt->error;
                }
                $stmt->close();
            }
        }
        $_SESSION['admin_dashboard_message'] = "Grades updated successfully!";
    } else {
        $_SESSION['admin_dashboard_error'] = "No grade data received or invalid format.";
    }
    $conn->close(); 
    header("Location: admin_dashboard.php");
    exit;
}

$sql_detailed_records = "
    SELECT
        s.student_id_no,
        s.name AS student_name,
        sub.subject_code,
        sub.subject_name,
        e.academic_year,
        e.semester,
        g.midterm_grade,
        g.finals_grade,
        g.id AS grade_id,
        e.id AS enrollment_id
    FROM students s
    JOIN enrollments e ON s.id = e.student_id
    JOIN subjects sub ON e.subject_id = sub.id
    LEFT JOIN grades g ON g.enrollment_id = e.id
    ORDER BY s.student_id_no, e.academic_year DESC, e.semester DESC, sub.subject_code
";

$result_detailed_records = $conn->query($sql_detailed_records);
$detailed_records = [];
if ($result_detailed_records) {
    if ($result_detailed_records->num_rows > 0) {
        while ($row = $result_detailed_records->fetch_assoc()) {
            $detailed_records[] = $row;
        }
    }
} else {
    $error .= " Error fetching detailed student records: " . $conn->error;
    error_log("Admin Dashboard detailed records error: " . $conn->error);
}

$sql_gwa_summary = "
    SELECT
        s.student_id_no,
        s.name AS student_name,
        AVG(CASE WHEN g.finals_grade IS NOT NULL THEN g.finals_grade ELSE NULL END) AS gwa
    FROM students s
    JOIN enrollments e ON s.id = e.student_id
    LEFT JOIN grades g ON g.enrollment_id = e.id
    GROUP BY s.id, s.student_id_no, s.name
    ORDER BY s.student_id_no;
";

$result_gwa_summary = $conn->query($sql_gwa_summary);
$gwa_summary = [];
if ($result_gwa_summary) {
    while ($row = $result_gwa_summary->fetch_assoc()) {
        $row['gwa'] = round($row['gwa'], 2); 
        $gwa_summary[] = $row;
    }
} else {
    $error .= " Error fetching GWA summary: " . $conn->error;
    error_log("Admin Dashboard GWA summary error: " . $conn->error);
}

$conn->close(); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Dashboard - Grade Tracking System</title>
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
            max-width: 1200px;
            margin: 20px auto;
        }

        h1,
        h2 {
            color: #333;
        }

        .message {
            color: green;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .error {
            color: red;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .gwa-summary-table {
            margin-bottom: 30px;
        }

        .gwa-summary-table th,
        .gwa-summary-table td {
            background-color: #e6f7ff;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 20px;
        }

        th,
        td {
            padding: 8px 12px;
            border: 1px solid #ddd;
            text-align: left;
            vertical-align: top;
        }

        th {
            background-color: #f2f2f2;
        }

        .editing input[type="number"] {
            width: 70px; 
            padding: 5px;
            border: 1px solid #ccc;
            border-radius: 3px;
        }

        #saveChangesBtn {
            display: none;
            padding: 8px 12px;
            background-color: #4CAF50; 
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 10px;
            float: right; 
            margin-bottom: 20px; 
        }

        #saveChangesBtn:hover {
            background-color: #388E3C;
        }

        .edit-grades-btn {
            padding: 8px 12px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin-bottom: 10px; 
        }

        .edit-grades-btn:hover {
            background-color: #0056b3;
        }


        .logout-btn {
            background-color: #f44336;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
            margin-top: 20px; 
        }

        .logout-btn:hover {
            background-color: #d32f2f;
        }
    </style>
</head>
<body style="background-image: url('assets/Grade Track.jpg'); background-size: cover; background-repeat: no-repeat;">
    <script src="script.js"></script>
    <div class="container">
        <h1>Admin Dashboard</h1>
        <?php if (!empty($message)): ?>
            <p class="message"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <h2>Student GWA Summary</h2>
        <table class="gwa-summary-table">
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Student Name</th>
                    <th>GWA</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($gwa_summary)): ?>
                    <tr>
                        <td colspan="3">No student GWA data available.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($gwa_summary as $gwa_rec): ?>
                        <tr>
                            <td><?= htmlspecialchars($gwa_rec['student_id_no']) ?></td>
                            <td><?= htmlspecialchars($gwa_rec['student_name']) ?></td>
                            <td><?= htmlspecialchars($gwa_rec['gwa'] ?? 'N/A') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <h2>All Student Records</h2>
        <button id="editGradesBtn" class="edit-grades-btn">Edit Grades</button>

        <table id="gradeTable">
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Student Name</th>
                    <th>Subject Code</th>
                    <th>Subject Name</th>
                    <th>Academic Year</th>
                    <th>Semester</th>
                    <th>Midterm Grade</th>
                    <th>Final Grade</th>
                    </tr>
            </thead>
            <tbody>
                <?php if (empty($detailed_records)): ?>
                    <tr>
                        <td colspan="8">No student records found.</td> </tr>
                <?php else: ?>
                    <?php foreach ($detailed_records as $rec): ?>
                        <tr data-grade-id="<?= htmlspecialchars($rec['grade_id']) ?>">
                            <td><?= htmlspecialchars($rec['student_id_no']) ?></td>
                            <td><?= htmlspecialchars($rec['student_name']) ?></td>
                            <td><?= htmlspecialchars($rec['subject_code']) ?></td>
                            <td><?= htmlspecialchars($rec['subject_name']) ?></td>
                            <td><?= htmlspecialchars($rec['academic_year'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($rec['semester'] ?? 'N/A') ?></td>
                            <td><?= $rec['midterm_grade'] !== null ? htmlspecialchars($rec['midterm_grade']) : 'N/A' ?></td>
                            <td><?= $rec['finals_grade'] !== null ? htmlspecialchars($rec['finals_grade']) : 'N/A' ?></td>
                            </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <div style="clear: both;"></div> <button id="saveChangesBtn">Save Changes</button>
        <a href="index.html" class="logout-btn" style="margin-left: 10px;">Logout</a>
    </div>
</body>

</html>