# University Database Query System

A simple PHP/HTML application that allows users to:

1. View a professor's classes by entering their SSN
2. View the grade distribution for a specific course section

## Setup Instructions

### Prerequisites

- PHP 7.0 or higher
- MySQL or MariaDB
- Web server (Apache, Nginx, etc.)

### Database Setup

1. Import the provided `ddl.sql` file into your MySQL database:

   ```
   mysql -u yourusername -p < ddl.sql
   ```

2. Update the database connection details in `professor.html`:
   - Open `professor.html`
   - Find the database connection lines (around line 57 and line 117)
   - Replace 'localhost', 'username', 'password' with your actual database credentials

### Web Server Configuration

1. Place the `professor.html` file in your web server's document root directory.
2. Ensure the file has the correct permissions.
3. Make sure your web server is configured to execute PHP within HTML files. If not, rename the file to `professor.php`.

## Usage

1. Access the application by navigating to http://yourdomain.com/professor.html or http://localhost/professor.html in your web browser.

2. Professor's Classes Query:

   - Enter a professor's SSN (e.g., 123456789) in the first form.
   - Click "Search" to see all classes taught by that professor.

3. Grade Distribution Query:
   - Enter a course number (e.g., CS101) and section number (e.g., 1) in the second form.
   - Click "Search" to see the distribution of grades for that course section.

## Sample Data

The following professors are available in the sample data:

- James Choi (SSN: 123456789)
- Shawn X Wang (SSN: 234567890)
- Michael Franklin (SSN: 345678901)

Sample courses and sections include:

- CS101 (sections 1 and 2)
- CS201 (section 1)
- MATH101 (sections 1 and 2)
- MATH201 (section 1)
