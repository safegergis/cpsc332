<?php
$host = "mariadb";
$username = "cs332t16";
$password = "IU5mxYgd";
$database = "cs332t16";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$professor_ssn = isset($_POST['professor_ssn']) ? $_POST['professor_ssn'] : '';
$professor_classes = [];
$professor_name = "";
$professor_error = '';

$course_number = isset($_POST['course_num']) ? $_POST['course_num'] : '';
$section_number = isset($_POST['section_num']) ? $_POST['section_num'] : '';
$grade_distribution = [];
$course_title = "";
$grade_error = '';

if (isset($_POST['professor_submit']) && $professor_ssn) {
    $sql = "SELECT p.prof_name AS professor_name, c.title AS course_title, 
            s.classroom, s.meeting_days, s.start_time, s.end_time 
            FROM CourseSection s 
            JOIN Course c ON s.course_num = c.course_num 
            JOIN Professor p ON s.professor_ssn = p.ssn 
            WHERE p.ssn = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $professor_ssn);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $professor_name = $row['professor_name'];
            $professor_classes[] = $row;
        }
    } else {
        $professor_error = "No classes found for professor with SSN: " . htmlspecialchars($professor_ssn);
    }

    $stmt->close();
}

if (isset($_POST['grade_submit']) && $course_num && $section_num) {
    $title_sql = "SELECT title FROM Course c JOIN CourseSection cs ON c.course_num = cs.course_num WHERE c.course_num = ? AND cs.section_num = ?";
    $title_stmt = $conn->prepare($title_sql);
    $title_stmt->bind_param("ss", $course_num, $section_num);
    $title_stmt->execute();
    $title_result = $title_stmt->get_result();
    $course_title = ($title_result->num_rows > 0) ? $title_result->fetch_assoc()['title'] : "Unknown Course";
    $title_stmt->close();

    $sql = "SELECT grade, COUNT(*) as count 
            FROM Enrollment 
            WHERE cwid IN (
                SELECT cwid 
                FROM Enrollment 
                WHERE section_num = ?
            )
            GROUP BY grade 
            ORDER BY 
            CASE 
                WHEN grade = 'A' THEN 1 
                WHEN grade = 'A-' THEN 2 
                WHEN grade = 'B+' THEN 3 
                WHEN grade = 'B' THEN 4 
                WHEN grade = 'B-' THEN 5 
                WHEN grade = 'C+' THEN 6 
                WHEN grade = 'C' THEN 7 
                WHEN grade = 'C-' THEN 8 
                WHEN grade = 'D+' THEN 9 
                WHEN grade = 'D' THEN 10 
                WHEN grade = 'D-' THEN 11 
                WHEN grade = 'F' THEN 12 
                WHEN grade = 'W' THEN 13 
                WHEN grade = 'I' THEN 14 
                ELSE 15 
            END";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $section_number);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $grade_distribution[] = $row;
        }
    } else {
        $grade_error = "No enrollment records found for section " . htmlspecialchars($section_number);
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University Database - Professor Portal</title>
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
            <h1>Professor Portal</h1>
            <p>Access professor teaching schedules and course grade distributions.</p>
        </section>

        <section class="card">
            <h2>Professor's Classes</h2>
            <p>Enter a professor's SSN to see their classes:</p>

            <form method="post" action="">
                <div class="form-group">
                    <label for="professor_ssn">Professor SSN:</label>
                    <input type="text" id="professor_ssn" name="professor_ssn" placeholder="SSN (e.g., 123456789)"
                        required>
                </div>
                <input type="submit" name="professor_submit" value="Search">
            </form>

            <?php if (isset($_POST['professor_submit'])): ?>
                <?php if (!empty($professor_classes)): ?>
                    <h3>Classes for Professor: <?php echo htmlspecialchars($professor_name); ?></h3>
                    <table>
                        <tr>
                            <th>Professor</th>
                            <th>Course Title</th>
                            <th>Classroom</th>
                            <th>Meeting Days</th>
                            <th>Time</th>
                        </tr>
                        <?php foreach ($professor_classes as $class): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($class['professor_name']); ?></td>
                                <td><?php echo htmlspecialchars($class['course_title']); ?></td>
                                <td><?php echo htmlspecialchars($class['classroom']); ?></td>
                                <td><?php echo htmlspecialchars($class['meeting_days']); ?></td>
                                <td><?php echo htmlspecialchars($class['start_time']) . " - " . htmlspecialchars($class['end_time']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php else: ?>
                    <p class="message error"><?php echo $professor_error; ?></p>
                <?php endif; ?>
            <?php endif; ?>
        </section>

        <section class="card">
            <h2>Grade Distribution</h2>
            <p>Enter a section number to see grade distribution:</p>

            <form method="post" action="">
                <div class="form-group">
                    <label for="course_num">Course Number:</label>
                    <input type="text" id="course_num" name="course_num" placeholder="Course Number (e.g., CS101)"
                        required>
                    <label for="section_num">Section Number:</label>
                    <input type="text" id="section_num" name="section_num" placeholder="Section Number (e.g., 1)"
                        required>
                </div>
                <input type="submit" name="grade_submit" value="Search">
            </form>

            <?php if (isset($_POST['grade_submit'])): ?>
                <?php if (!empty($grade_distribution)): ?>

                    <h3>Grade Distribution for Section <?php echo htmlspecialchars($section_num); ?>:</h3>
                    <table>
                        <tr>
                            <th>Grade</th>
                            <th>Count</th>
                        </tr>
                        <?php foreach ($grade_distribution as $grade): ?>
                            <tr>
                                <td><?php echo is_null($grade['grade']) ? "No Grade" : htmlspecialchars($grade['grade']); ?></td>
                                <td><?php echo htmlspecialchars($grade['count']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php else: ?>
                    <p class="message error"><?php echo $grade_error; ?></p>
                <?php endif; ?>
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