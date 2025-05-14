<?php
$host = "mariadb";
$username = "cs332t16";
$password = "IU5mxYgd";
$database = "cs332t16";

// Handle API requests
if (isset($_GET['api'])) {
    header('Content-Type: application/json');
    $conn = new mysqli($host, $username, $password, $database);

    if ($conn->connect_error) {
        echo json_encode(['error' => 'Connection failed: ' . $conn->connect_error]);
        exit;
    }

    // API to get course sections
    if ($_GET['api'] === 'course_sections' && isset($_GET['course_num'])) {
        $course_num = $_GET['course_num'];
        $response = ['course_num' => $course_num];
        
        // Get course info
        $course_query = "SELECT title FROM Course WHERE course_num = ?";
        $stmt = $conn->prepare($course_query);
        $stmt->bind_param("s", $course_num);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $course_info = $result->fetch_assoc();
            $response['course_title'] = $course_info['title'];
            
            // Get sections for the course
            $sections_query = "
                SELECT 
                    s.section_number,
                    s.classroom,
                    s.meeting_days,
                    s.start_time,
                    s.end_time,
                    p.prof_name AS professor_name,
                    COUNT(e.cwid) AS enrollment_count
                FROM 
                    CourseSection s
                    LEFT JOIN Enrollment e ON s.section_number = e.section_num
                    LEFT JOIN Professor p ON s.professor_ssn = p.ssn
                WHERE 
                    s.course_num = ?
                GROUP BY 
                    s.section_number, s.classroom, s.meeting_days, s.start_time, s.end_time, p.prof_name
                ORDER BY 
                    s.section_number
            ";
            
            $stmt = $conn->prepare($sections_query);
            $stmt->bind_param("s", $course_num);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $sections = [];
                while ($row = $result->fetch_assoc()) {
                    // Format times for display
                    $row['start_time_formatted'] = date("g:i A", strtotime($row['start_time']));
                    $row['end_time_formatted'] = date("g:i A", strtotime($row['end_time']));
                    $sections[] = $row;
                }
                $response['sections'] = $sections;
            } else {
                $response['error'] = "No sections found for this course.";
            }
        } else {
            $response['error'] = "Course not found.";
        }
        
        echo json_encode($response);
        exit;
    }
    
    // API to get student grades
    if ($_GET['api'] === 'student_grades' && isset($_GET['cwid'])) {
        $cwid = $_GET['cwid'];
        $response = ['cwid' => $cwid];
        
        // Get student info
        $student_query = "SELECT first_name, last_name, d.dept_name AS major 
                         FROM Student s
                         JOIN Department d ON s.major = d.dept_num
                         WHERE cwid = ?";
        $stmt = $conn->prepare($student_query);
        $stmt->bind_param("s", $cwid);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $student_info = $result->fetch_assoc();
            $response['student_info'] = $student_info;
            
            // Get student grades
            $grades_query = "
                SELECT 
                    cs.course_num,
                    c.title AS course_title,
                    cs.section_number,
                    e.grade,
                    c.units,
                    d.dept_name AS department
                FROM 
                    Enrollment e
                    JOIN CourseSection cs ON e.section_num = cs.section_number
                    JOIN Course c ON cs.course_num = c.course_num
                    JOIN Department d ON c.dept_number = d.dept_num
                WHERE 
                    e.cwid = ?
                ORDER BY 
                    cs.course_num, cs.section_number
            ";
            
            $stmt = $conn->prepare($grades_query);
            $stmt->bind_param("s", $cwid);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $grades = [];
                while ($row = $result->fetch_assoc()) {
                    $grades[] = $row;
                }
                $response['grades'] = $grades;
                
                // Calculate GPA
                $response['gpa'] = calculateGPA($grades);
            } else {
                $response['error'] = "No course history found for this student.";
            }
        } else {
            $response['error'] = "Student not found.";
        }
        
        echo json_encode($response);
        exit;
    }
    
    $conn->close();
    exit;
}

// GPA calculation function
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University Information System - Student Portal</title>
    <link rel="stylesheet" href="styles.css">
    <style>
    .mt-3 {
        margin-top: 1rem;
    }
    .mt-4 {
        margin-top: 1.5rem;
    }
    .gpa-display {
        background-color: var(--light-gray);
        padding: 0.75rem;
        border-radius: 4px;
        text-align: right;
    }
    .message.error {
        color: var(--accent-color);
    }
    .result-card {
        display: none;
        margin-top: 1rem;
        padding: 1rem;
        border-radius: 5px;
        background-color: var(--light-gray);
    }
    .loading {
        display: inline-block;
        margin-left: 10px;
        width: 20px;
        height: 20px;
        border: 3px solid rgba(0, 0, 0, 0.1);
        border-radius: 50%;
        border-top-color: var(--primary-color);
        animation: spin 1s ease-in-out infinite;
    }
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    </style>
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
            <h1>Student Information Portal</h1>
            <p>Access course information and student records through the forms below.</p>
        </section>
        
        <section class="card">
            <h2>Course Sections Information</h2>
            <p>View all sections for a specific course, including classroom, schedule, and enrollment.</p>
            <form id="course-form">
                <div class="form-group">
                    <label for="course_num">Enter Course Number (e.g., CS101):</label>
                    <input type="text" id="course_num" name="course_num" required>
                </div>
                <button type="submit">View Sections</button>
                <span id="course-loading" class="loading" style="display: none;"></span>
            </form>
            
            <div id="course-result" class="result-card">
                <h3 id="course-title"></h3>
                <div id="course-error" class="message error" style="display: none;"></div>
                <div id="course-sections" style="display: none;">
                    <table class="mt-3">
                        <thead>
                            <tr>
                                <th>Section</th>
                                <th>Classroom</th>
                                <th>Meeting Days</th>
                                <th>Time</th>
                                <th>Professor</th>
                                <th>Enrollment</th>
                            </tr>
                        </thead>
                        <tbody id="sections-table-body">
                            <!-- Section data will be populated here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
        
        <section class="card">
            <h2>Student Grade Information</h2>
            <p>View all courses and grades for a specific student.</p>
            <form id="student-form">
                <div class="form-group">
                    <label for="cwid">Enter Student Campus ID (e.g., S10001):</label>
                    <input type="text" id="cwid" name="cwid" required>
                </div>
                <button type="submit">View Grades</button>
                <span id="student-loading" class="loading" style="display: none;"></span>
            </form>
            
            <div id="student-result" class="result-card">
                <div id="student-info" style="display: none;">
                    <h3 id="student-name"></h3>
                    <p id="student-major"></p>
                </div>
                
                <div id="student-error" class="message error" style="display: none;"></div>
                
                <div id="student-grades" style="display: none;">
                    <table class="mt-3">
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Title</th>
                                <th>Department</th>
                                <th>Section</th>
                                <th>Units</th>
                                <th>Grade</th>
                            </tr>
                        </thead>
                        <tbody id="grades-table-body">
                            <!-- Grades data will be populated here -->
                        </tbody>
                    </table>
                    
                    <div class="gpa-display mt-3">
                        <strong id="gpa-display">Overall GPA: </strong>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2023 University Database System. All rights reserved.</p>
        </div>
    </footer>
    
    <script>
        // Course sections form
        document.getElementById('course-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const courseNum = document.getElementById('course_num').value.trim();
            if (!courseNum) return;
            
            // Reset and show loading
            document.getElementById('course-result').style.display = 'none';
            document.getElementById('course-loading').style.display = 'inline-block';
            
            // Fetch course sections data
            fetch(`?api=course_sections&course_num=${encodeURIComponent(courseNum)}`)
                .then(response => response.json())
                .then(data => {
                    // Hide loading indicator
                    document.getElementById('course-loading').style.display = 'none';
                    
                    // Show result container
                    document.getElementById('course-result').style.display = 'block';
                    
                    if (data.error) {
                        // Show error message
                        document.getElementById('course-error').textContent = data.error;
                        document.getElementById('course-error').style.display = 'block';
                        document.getElementById('course-sections').style.display = 'none';
                        document.getElementById('course-title').textContent = data.course_num;
                    } else {
                        // Show course title
                        document.getElementById('course-title').textContent = `${data.course_num}: ${data.course_title}`;
                        document.getElementById('course-error').style.display = 'none';
                        
                        // Show sections table
                        const tableBody = document.getElementById('sections-table-body');
                        tableBody.innerHTML = '';
                        
                        data.sections.forEach(section => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${section.section_number}</td>
                                <td>${section.classroom}</td>
                                <td>${section.meeting_days}</td>
                                <td>${section.start_time_formatted} - ${section.end_time_formatted}</td>
                                <td>${section.professor_name}</td>
                                <td>${section.enrollment_count}</td>
                            `;
                            tableBody.appendChild(row);
                        });
                        
                        document.getElementById('course-sections').style.display = 'block';
                    }
                })
                .catch(error => {
                    // Handle network errors
                    document.getElementById('course-loading').style.display = 'none';
                    document.getElementById('course-result').style.display = 'block';
                    document.getElementById('course-error').textContent = 'Network error. Please try again.';
                    document.getElementById('course-error').style.display = 'block';
                    document.getElementById('course-sections').style.display = 'none';
                });
        });
        
        // Student grades form
        document.getElementById('student-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const cwid = document.getElementById('cwid').value.trim();
            if (!cwid) return;
            
            // Reset and show loading
            document.getElementById('student-result').style.display = 'none';
            document.getElementById('student-loading').style.display = 'inline-block';
            
            // Fetch student data
            fetch(`?api=student_grades&cwid=${encodeURIComponent(cwid)}`)
                .then(response => response.json())
                .then(data => {
                    // Hide loading indicator
                    document.getElementById('student-loading').style.display = 'none';
                    
                    // Show result container
                    document.getElementById('student-result').style.display = 'block';
                    
                    if (data.error) {
                        // Show error message
                        document.getElementById('student-error').textContent = data.error;
                        document.getElementById('student-error').style.display = 'block';
                        document.getElementById('student-info').style.display = 'none';
                        document.getElementById('student-grades').style.display = 'none';
                    } else {
                        // Show student info
                        document.getElementById('student-name').textContent = 
                            `${data.student_info.first_name} ${data.student_info.last_name} (${data.cwid})`;
                        document.getElementById('student-major').textContent = `Major: ${data.student_info.major}`;
                        document.getElementById('student-info').style.display = 'block';
                        document.getElementById('student-error').style.display = 'none';
                        
                        // Show grades table
                        const tableBody = document.getElementById('grades-table-body');
                        tableBody.innerHTML = '';
                        
                        data.grades.forEach(grade => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${grade.course_num}</td>
                                <td>${grade.course_title}</td>
                                <td>${grade.department}</td>
                                <td>${grade.section_number}</td>
                                <td>${grade.units}</td>
                                <td>${grade.grade || 'N/A'}</td>
                            `;
                            tableBody.appendChild(row);
                        });
                        
                        // Show GPA
                        document.getElementById('gpa-display').textContent = `Overall GPA: ${data.gpa.toFixed(2)}`;
                        document.getElementById('student-grades').style.display = 'block';
                    }
                })
                .catch(error => {
                    // Handle network errors
                    document.getElementById('student-loading').style.display = 'none';
                    document.getElementById('student-result').style.display = 'block';
                    document.getElementById('student-error').textContent = 'Network error. Please try again.';
                    document.getElementById('student-error').style.display = 'block';
                    document.getElementById('student-info').style.display = 'none';
                    document.getElementById('student-grades').style.display = 'none';
                });
        });
    </script>
</body>
</html>
