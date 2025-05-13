<?php
$host = "mariadb";
$username = "cs332t16";
$password = "IU5mxYgd";
$database = "university";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$campus_id = isset($_GET['campus_id']) ? $_GET['campus_id'] : '';
$student_info = [];
$grades = [];
$error = '';

if ($campus_id) {
    $student_query = "SELECT first_name, last_name, d.name AS major 
                     FROM Student s
                     JOIN Department d ON s.major_dept = d.dept_number
                     WHERE campus_id = ?";
    $stmt = $conn->prepare($student_query);
    $stmt->bind_param("s", $campus_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $student_info = $result->fetch_assoc();
        
        $grades_query = "
            SELECT 
                e.course_number,
                c.title AS course_title,
                e.section_number,
                e.grade,
                c.units,
                d.name AS department
            FROM 
                Enrollment e
                JOIN Course c ON e.course_number = c.course_number
                JOIN Department d ON c.dept_number = d.dept_number
            WHERE 
                e.campus_id = ?
            ORDER BY 
                e.course_number, e.section_number
        ";
        
        $stmt = $conn->prepare($grades_query);
        $stmt->bind_param("s", $campus_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $grades[] = $row;
            }
        } else {
            $error = "No course history found for this student.";
        }
    } else {
        $error = "Student not found.";
    }
}

$conn->close();

function calculateGPA($grades) {
    $gradePoints = [
        'A' => 4.0,
        'A-' => 3.7,
        'B+' => 3.3,
        'B' => 3.0,
        'B-' => 2.7,
        'C+' => 2.3,
        'C' => 2.0,
        'C-' => 1.7,
        'D+' => 1.3,
        'D' => 1.0,
        'D-' => 0.7,
        'F' => 0.0
    ];
    
    $totalPoints = 0;
    $totalUnits = 0;
    
    foreach ($grades as $grade) {
        if (isset($grade['grade']) && isset($gradePoints[$grade['grade']])) {
            $totalPoints += $gradePoints[$grade['grade']] * $grade['units'];
            $totalUnits += $grade['units'];
        }
    }
    
    return $totalUnits > 0 ? round($totalPoints / $totalUnits, 2) : 0;
}

$gpa = !empty($grades) ? calculateGPA($grades) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Grades - University Information System</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="site-title">University Database System</div>
            <nav class="main-nav">
                <ul>
                    <li><a href="index.html">Home</a></li>
                    <li><a href="students.html">Student Portal</a></li>
                    <li><a href="professor.php">Professor Portal</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <section class="card">
            <h1>Student Grade Information</h1>
            
            <?php if ($campus_id): ?>
                <?php if (isset($student_info['first_name'])): ?>
                    <div class="student-info card">
                        <h2>
                            <?php echo htmlspecialchars($student_info['first_name'] . ' ' . $student_info['last_name']); ?> 
                            (<?php echo htmlspecialchars($campus_id); ?>)
                        </h2>
                        <p>Major: <?php echo htmlspecialchars($student_info['major']); ?></p>
                    </div>
                    
                    <?php if (!empty($grades)): ?>
                        <table>
                            <tr>
                                <th>Course</th>
                                <th>Title</th>
                                <th>Department</th>
                                <th>Section</th>
                                <th>Units</th>
                                <th>Grade</th>
                            </tr>
                            <?php foreach ($grades as $grade): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($grade['course_number']); ?></td>
                                    <td><?php echo htmlspecialchars($grade['course_title']); ?></td>
                                    <td><?php echo htmlspecialchars($grade['department']); ?></td>
                                    <td><?php echo htmlspecialchars($grade['section_number']); ?></td>
                                    <td><?php echo htmlspecialchars($grade['units']); ?></td>
                                    <td><?php echo is_null($grade['grade']) ? 'N/A' : htmlspecialchars($grade['grade']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                        
                        <div class="gpa-display">
                            Overall GPA: <?php echo number_format($gpa, 2); ?>
                        </div>
                    <?php else: ?>
                        <p class="message error"><?php echo $error; ?></p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="message error"><?php echo $error; ?></p>
                <?php endif; ?>
            <?php else: ?>
                <p>Please enter a student ID to view grades.</p>
                <p><a href="students.html" class="btn">Back to Student Portal</a></p>
            <?php endif; ?>
        </section>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2023 University Database System. All rights reserved.</p>
        </div>
    </footer>
</body>
</html> 