USE cs332t16;

-- Professor table
CREATE TABLE Professor (
    ssn CHAR(9) PRIMARY KEY,
    prof_name VARCHAR(100) NOT NULL,
    street_address VARCHAR(100) NOT NULL,
    city VARCHAR(50) NOT NULL,
    state CHAR(2) NOT NULL,
    zip_code CHAR(5) NOT NULL,
    area_code CHAR(3) NOT NULL,
    phone_num CHAR(7) NOT NULL,
    sex CHAR(1) CHECK (sex IN ('M', 'F')),
    title VARCHAR(50) NOT NULL,
    salary DECIMAL(10, 2) NOT NULL,
    college_degrees VARCHAR(255) NOT NULL
);

-- Department table
CREATE TABLE Department (
    dept_num INT PRIMARY KEY,
    dept_name VARCHAR(100) UNIQUE NOT NULL,
    phone_num CHAR(10) NOT NULL,
    location VARCHAR(100) NOT NULL,
    prof_ssn CHAR(9) NOT NULL,
    FOREIGN KEY (prof_ssn) REFERENCES Professor(ssn)
);

-- Course table
CREATE TABLE Course (
    course_num CHAR(3) PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    textbook VARCHAR(255) NOT NULL,
    units INT NOT NULL CHECK (units > 0),
    dept_num INT NOT NULL,
    FOREIGN KEY (dept_num) REFERENCES Department(dept_num),
    prereq_course_num CHAR(3),
    FOREIGN KEY (prereq_course_num) REFERENCES Course(course_num)
);

-- Course Section
CREATE TABLE CourseSection (
    section_num INT PRIMARY KEY,
    classroom VARCHAR(20) NOT NULL,
    num_seats INT NOT NULL CHECK (num_seats > 0),
    meeting_days VARCHAR(10) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    professor_ssn CHAR(9) NOT NULL,
    FOREIGN KEY (professor_ssn) REFERENCES Professor(ssn),
    course_num CHAR(3) NOT NULL,
    FOREIGN KEY (course_num) REFERENCES Course(course_num),
    CHECK (start_time < end_time)
);

-- Student table
CREATE TABLE Student (
    cwid VARCHAR(10) PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    address VARCHAR(157) NOT NULL,
    phone_num CHAR(10) NOT NULL,
    major CHAR(4) NOT NULL,
    minor CHAR(4) NOT NULL
);

-- Enrollment table
CREATE TABLE Enrollment (
    cwid VARCHAR(10),
    section_num INT,
    grade CHAR(2),
    PRIMARY KEY (cwid, section_num),
    FOREIGN KEY (cwid) REFERENCES Student(cwid),
    FOREIGN KEY (section_num) REFERENCES CourseSection(section_num),
    CHECK (grade IN ('A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'C-', 'D+', 'D', 'D-', 'F', 'W', 'I', NULL))
);

-- Insert Professors (3)
INSERT INTO Professor VALUES 
('123456789', 'James Choi', '123 Oak Street', 'Fullerton', 'CA', '94704', '714', '5551234', 'M', 'Professor', 120000.00, 'PhD Computer Science, MS Mathematics'),
('234567890', 'Shawn X Wang', '456 Elm Avenue', 'Fullerton', 'CA', '94705', '714', '5552345', 'M', 'Professor', 95000.00, 'PhD Computer Science, MS Statistics'),
('345678901', 'Michael Franklin', '789 Pine Road', 'Fullerton', 'CA', '94706', '714', '5553456', 'M', 'Assistant Professor', 85000.00, 'PhD Physics, MS Electrical Engineering');

-- Insert Departments (2)
INSERT INTO Department VALUES
(101, 'Computer Science', '5105554567', 'Soda Hall Room 310', '123456789'),
(102, 'Mathematics', '5105555678', 'Evans Hall Room 220', '234567890');

-- Insert Courses (4)
INSERT INTO Course VALUES
('101', 'Introduction to Programming', 'Python Fundamentals by Smith', 4, 101, NULL),
('102', 'Data Structures', 'Algorithms and Data Structures by Johnson', 4, 101, '101'),
('201', 'Calculus I', 'Calculus Made Simple by Williams', 3, 102, NULL),
('202', 'Linear Algebra', 'Linear Algebra and Applications by Garcia', 3, 102, '201');

-- Insert Course Sections (6)
INSERT INTO CourseSection VALUES
(1, 'CS112', 35, 'MWF', '09:00:00', '09:50:00', '123456789', '101'),
(2, 'CS108', 30, 'TuTh', '11:00:00', '12:15:00', '123456789', '101'),
(3, 'CS212', 25, 'MWF', '13:00:00', '13:50:00', '345678901', '102'),
(4, 'Langsdorf 10', 40, 'MWF', '10:00:00', '10:50:00', '234567890', '201'),
(5, 'Langsdorf 11', 35, 'TuTh', '14:00:00', '15:15:00', '234567890', '201'),
(6, 'Langsdorf 12', 30, 'MWF', '15:00:00', '15:50:00', '234567890', '202');

-- Insert Students (8)
INSERT INTO Student VALUES
('S10001', 'Emma', 'Davis', '101 University Drive, Fullerton, CA 92836', '7145556789', '101', '102'),
('S10002', 'Noah', 'Martinez', '202 College Avenue, Fullerton, CA 92837', '7145557890', '101', '000'),
('S10003', 'Olivia', 'Smith', '303 Campus Road, Anaheim, CA 92838', '7145558901', '102', '101'),
('S10004', 'Liam', 'Garcia', '404 Academic Street, Fullerton, CA 92839', '7145559012', '102', '000'),
('S10005', 'Ava', 'Brown', '505 Scholar Lane, Fullerton, CA 92840', '7145550123', '101', '102'),
('S10006', 'Ethan', 'Wilson', '606 Research Boulevard, Orange, CA 92841', '7145551234', '101', '000'),
('S10007', 'Sophia', 'Lee', '707 Learning Court, Fullerton, CA 92842', '7145552345', '102', '101'),
('S10008', 'Mason', 'Anderson', '808 Education Way, Buena Park, CA 92843', '7145553456', '102', '000');

-- Insert Enrollment Records (15)
INSERT INTO Enrollment VALUES
('S10001', 1, 'A'),
('S10001', 2, 'B+'),
('S10001', 3, 'A-'),
('S10002', 2, 'B'),
('S10002', 4, 'C+'),
('S10003', 2, 'A'),
('S10003', 5, 'A-'),
('S10004', 3, 'B'),
('S10004', 6, 'B+'),
('S10005', 1, 'A-'),
('S10005', 4, 'B'),
('S10006', 2, 'A'),
('S10006', 5, 'A-'),
('S10007', 3, 'B+'),
('S10008', 1, 'B-');