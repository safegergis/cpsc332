<?php
$host = "mariadb";
$username = "cs332t16";
$password = "IU5mxYgd";
$database = "cs332t16";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$course_num = isset($_GET['course_num']) ? $_GET['course_num'] : '';
$course_info = [];
$sections = [];
$error = '';

if ($course_num) {
    $course_query = "SELECT title FROM Course WHERE course_num = ?";
    $stmt = $conn->prepare($course_query);
    $stmt->bind_param("s", $course_num);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $course_info = $result->fetch_assoc();
        
        $sections_query = "
            SELECT 
                s.section_num,
                s.classroom,
                s.meeting_days,
                s.start_time,
                s.end_time,
                p.prof_name AS professor_name,
                COUNT(e.cwid) AS enrollment_count
            FROM 
                CourseSection s
                LEFT JOIN Enrollment e ON s.section_num = e.section_num
                LEFT JOIN Professor p ON s.professor_ssn = p.ssn
            WHERE 
                s.course_num = ?
            GROUP BY 
                s.section_num, s.classroom, s.meeting_days, s.start_time, s.end_time, p.prof_name
            ORDER BY 
                s.section_num
        ";
        
        $stmt = $conn->prepare($sections_query);
        $stmt->bind_param("s", $course_num);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sections[] = $row;
            }
        } else {
            $error = "No sections found for this course.";
        }
    } else {
        $error = "Course not found.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Sections - University Information System</title>
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
            <h1>Course Sections Information</h1>
            
            <?php if ($course_num): ?>
                <?php if (isset($course_info['title'])): ?>
                    <h2><?php echo htmlspecialchars($course_num); ?>: <?php echo htmlspecialchars($course_info['title']); ?></h2>
                    
                    <?php if (!empty($sections)): ?>
                        <table>
                            <tr>
                                <th>Section</th>
                                <th>Classroom</th>
                                <th>Meeting Days</th>
                                <th>Time</th>
                                <th>Professor</th>
                                <th>Enrollment</th>
                            </tr>
                            <?php foreach ($sections as $section): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($section['section_num']); ?></td>
                                    <td><?php echo htmlspecialchars($section['classroom']); ?></td>
                                    <td><?php echo htmlspecialchars($section['meeting_days']); ?></td>
                                    <td>
                                        <?php 
                                            $begin = date("g:i A", strtotime($section['start_time']));
                                            $end = date("g:i A", strtotime($section['end_time']));
                                            echo htmlspecialchars("$begin - $end"); 
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($section['professor_name']); ?></td>
                                    <td><?php echo htmlspecialchars($section['enrollment_count']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php else: ?>
                        <p class="message error"><?php echo $error; ?></p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="message error"><?php echo $error; ?></p>
                <?php endif; ?>
            <?php else: ?>
                <p>Please enter a course number to view section information.</p>
                <p><a href="students.html" class="btn">Back to Student Portal</a></p>
            <?php endif; ?>
        </section>
    </main>

    <footer>
        <div class="container">
            <p>Safe Gergis and Brandon Cobb; CPSC 332 Final Project</p>
        </div>
    </footer>
</body>
</html> 