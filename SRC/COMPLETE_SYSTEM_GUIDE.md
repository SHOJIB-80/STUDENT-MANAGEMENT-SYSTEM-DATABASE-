# University Student Management System - Complete Implementation Guide
**Multiple Sections with Schedule Conflict Detection**

---

## ✅ System Status: FULLY IMPLEMENTED

Your system is **complete** with all the features you requested:
- ✅ Multiple sections per course
- ✅ Time and day scheduling for each section
- ✅ Automatic conflict detection when enrolling
- ✅ Warning display showing clashing course names and times

---

## 📋 Table of Contents

1. [Database Structure](#database-structure)
2. [How It Works](#how-it-works)
3. [File Descriptions](#file-descriptions)
4. [Feature Breakdown](#feature-breakdown)
5. [Testing the System](#testing-the-system)
6. [Admin Tasks](#admin-tasks)
7. [Student Experience](#student-experience)

---

## 🗄️ Database Structure

### Tables

#### 1. **courses**
Stores course information
```sql
id (PRIMARY KEY)
course_code (UNIQUE)
course_name
credits
max_students (0 = unlimited)
faculty_id (FOREIGN KEY)
enroll_start (NULLABLE)
enroll_end (NULLABLE)
```

#### 2. **course_sections**
Stores individual sections of each course
```sql
id (PRIMARY KEY)
course_id (FOREIGN KEY → courses.id)
section_name (A, B, C, etc.)
instructor
capacity (max students per section)
created_at
```

#### 3. **section_schedules**
Stores time and day information for each section
```sql
id (PRIMARY KEY)
section_id (FOREIGN KEY → course_sections.id)
day_of_week (S, M, T, W, R, F, X)
start_time (HH:MM format)
end_time (HH:MM format)
```

#### 4. **enrollments**
Tracks student enrollments in sections
```sql
id (PRIMARY KEY)
student_id (FOREIGN KEY → students.student_id)
section_id (FOREIGN KEY → course_sections.id)
course_id (FOREIGN KEY → courses.id)
status (enrolled, dropped, completed)
enrolled_at
```

---

## 🔄 How It Works

### 1. **Multiple Sections Per Course**

Example: **TEST101 - Programming** has 3 sections:
- **Section A**: Dr. Smith, Monday & Wednesday 08:00-09:30
- **Section B**: Dr. Johnson, Tuesday & Thursday 10:00-11:30
- **Section C**: Dr. Williams, Monday & Wednesday 13:00-14:30

### 2. **Schedule Conflict Detection**

When a student tries to enroll in a section, the system:

```
1. Gets the proposed section's schedule (days & times)
2. Fetches all courses the student is currently enrolled in
3. Compares time slots:
   - Same day (M, T, W, R, F)?
   - Time overlap (e.g., 08:00-09:30 overlaps with 08:30-10:00)?
4. If conflict found:
   - Shows warning with conflicting course name and time
   - Prevents enrollment
5. If no conflict:
   - Allows enrollment
```

### 3. **Conflict Detection Logic**

From [scheduling_helpers.php](scheduling_helpers.php):

```php
function checkScheduleConflicts($db, $student_id, $proposed_section_id) {
    // 1. Get proposed section's schedule
    $proposed_schedule = [];
    
    // 2. Get student's current enrolled sections
    $student_sections = [];
    
    // 3. Compare each existing section with proposed section
    foreach ($proposed_schedule as $proposed) {
        foreach ($student_sections as $existing) {
            if ($existing['day'] === $proposed['day']) {
                if (checkTimeOverlap(...)) {
                    // CONFLICT FOUND!
                    return $conflicting_courses;
                }
            }
        }
    }
    
    return null; // No conflict
}
```

**Time Overlap Check:**
```php
function checkTimeOverlap($start1, $end1, $start2, $end2) {
    // Converts times to minutes
    // Returns true if: !(end1 <= start2 || end2 <= start1)
    // Example: 08:00-09:30 OVERLAPS with 09:00-10:30 ✓
}
```

---

## 📁 File Descriptions

### Core Files

| File | Purpose |
|------|---------|
| **server.php** | Database connection + includes scheduling_helpers.php |
| **scheduling_helpers.php** | Contains all conflict detection and scheduling functions |
| **courses_new.php** | Student view: Shows courses with multiple sections, checks conflicts |
| **enroll_action_new.php** | Handles section enrollment/drop with conflict checking |

### Database Setup Files

| File | Purpose |
|------|---------|
| **test_setup.sql** | Creates TEST101 & TEST102 with 3 sections each |
| **add_sections.sql** | Alternative section creation script |
| **check_schema.sql** | Verifies database tables exist |

### Key Functions in [scheduling_helpers.php](scheduling_helpers.php)

```php
// Main conflict detection function
checkScheduleConflicts($db, $student_id, $proposed_section_id)
  → Returns array of conflicting courses with times, or null

// Time comparison
checkTimeOverlap($start1, $end1, $start2, $end2)
  → Returns true if times overlap

// Display helper
getDayName($day_char)  // S→Sunday, M→Monday, etc.

// Get section details
getCourseSectionsWithSchedule($db, $course_id, $student_id)
  → Returns all sections with schedules and enrollment status
```

---

## 🎯 Feature Breakdown

### ✨ Feature 1: Multiple Sections Per Course

**Where Implemented:** [courses_new.php](courses_new.php#L120-L140)

```php
// Get all sections for this course
$sections = getCourseSectionsWithSchedule($db, $course_id, $student_ref_id);

// Display each section with:
foreach ($sections as $section) {
    // - Section name (A, B, C)
    // - Instructor name
    // - Schedule (Day + Time)
    // - Enrollment count / capacity
    // - Enroll/Drop button
}
```

**Student sees:**
```
TEST101 - Programming
├─ Section A | Dr. Smith | Mon/Wed 08:00-09:30 | 25/30 enrolled | [ENROLL]
├─ Section B | Dr. Johnson | Tue/Thu 10:00-11:30 | 28/30 enrolled | [ENROLL]
└─ Section C | Dr. Williams | Mon/Wed 13:00-14:30 | 20/30 enrolled | [ENROLL]
```

---

### 🚨 Feature 2: Automatic Conflict Detection

**Where Implemented:** [enroll_action_new.php](enroll_action_new.php#L87-L96) & [courses_new.php](courses_new.php#L175-L195)

**Conflict Check Flow:**
```
Student clicks [ENROLL] for Section B
         ↓
enroll_action_new.php receives POST
         ↓
Calls: checkScheduleConflicts($db, $student_id, $section_id)
         ↓
Checks if student already has a course at same time
         ↓
YES → Show warning with course name & time
NO → Enroll student
```

**Example Conflict Message:**
```
⚠ Schedule Conflict:
You already have course(s) at this time:
• TEST101 - Programming
  Monday 08:00 - 09:30
```

---

### 📅 Feature 3: Time and Day Scheduling

**Where Stored:** `section_schedules` table

**Format:**
- **day_of_week:** S, M, T, W, R, F, X (Sunday-Friday + flexible)
- **start_time:** HH:MM (24-hour format)
- **end_time:** HH:MM (24-hour format)

**Example:**
```sql
section_id | day_of_week | start_time | end_time
1          | M           | 08:00      | 09:30
1          | W           | 08:00      | 09:30
2          | T           | 10:00      | 11:30
2          | R           | 10:00      | 11:30
```

---

## 🧪 Testing the System

### Step 1: Load Test Data

Run this SQL to create TEST courses with sections:

```bash
# In phpMyAdmin or MySQL client:
1. Open test_setup.sql
2. Run all queries
```

**This creates:**
- TEST101 with sections A, B, C
- TEST102 with sections A, B, C
- Schedules with intentional conflicts for testing

### Step 2: Login as Student

```
Username: (your Student ID)
Password: (your password)
```

### Step 3: View Courses with Sections

Go to **"Courses"** or **"Courses with Sections"** page

You'll see:
- TEST101 with 3 sections
- Each section with instructor, schedule, and enrollment status

### Step 4: Test Conflict Detection

**Scenario:** TEST101-A (Mon/Wed 08:00-09:30) and TEST102-A (same time)

1. **Enroll in TEST101-A**
   - Click [ENROLL]
   - ✅ Success: You're now in TEST101-A

2. **Try to enroll in TEST102-A**
   - Click [ENROLL]
   - ⚠️ **Warning appears:**
     ```
     Schedule Conflict:
     You already have course(s) at this time:
     • TEST101 - Programming
       Monday 08:00 - 09:30
     ```
   - ❌ Enrollment blocked

3. **Enroll in TEST101-B instead**
   - Section B is Tue/Thu 10:00-11:30 (different time)
   - ✅ Success: No conflict, enrollment allowed

---

## 👨‍💼 Admin Tasks

### Add a New Course with Sections

**Method 1: Using Admin Panel (if available)**

1. Go to **Admin → Add Course**
2. Fill in:
   - Course Code: CS201
   - Course Name: Data Structures
   - Credits: 3
   - Max Students: 100

**Method 2: Using SQL**

```sql
-- 1. Add course
INSERT INTO courses (course_code, course_name, credits, max_students) 
VALUES ('CS201', 'Data Structures', 3, 100);

-- 2. Get course ID
SET @course_id = (SELECT id FROM courses WHERE course_code = 'CS201');

-- 3. Add sections
INSERT INTO course_sections (course_id, section_name, instructor, capacity) 
VALUES 
(@course_id, 'A', 'Dr. Brown', 30),
(@course_id, 'B', 'Dr. Davis', 30);

-- 4. Get section IDs
SET @sec_a = (SELECT id FROM course_sections 
              WHERE course_id = @course_id AND section_name = 'A');
SET @sec_b = (SELECT id FROM course_sections 
              WHERE course_id = @course_id AND section_name = 'B');

-- 5. Add schedules
INSERT INTO section_schedules (section_id, day_of_week, start_time, end_time) 
VALUES 
(@sec_a, 'M', '13:00', '14:30'),
(@sec_a, 'W', '13:00', '14:30'),
(@sec_b, 'T', '15:00', '16:30'),
(@sec_b, 'R', '15:00', '16:30');
```

---

## 👨‍🎓 Student Experience

### Enrollment Flow

```
1. Student logs in
         ↓
2. Clicks "Courses" menu
         ↓
3. Sees all available courses with sections:
   
   CS101 - Programming
   ├─ [A] Dr. Smith     | MW 08:00-09:30 | 25/30 | [ENROLL]
   ├─ [B] Dr. Johnson   | TTh 10:00-11:30| 28/30 | [ENROLL]
   └─ [C] Dr. Williams  | MW 13:00-14:30 | 20/30 | [ENROLL]
         ↓
4. Clicks [ENROLL] for preferred section
         ↓
5. System checks for conflicts:
   ✅ No conflict → Enrollment successful
   ❌ Has conflict → Shows warning with conflicting course
         ↓
6. Student can:
   - Drop courses during drop window
   - View enrollments in "My Enrollments"
   - See grades in "My Grades"
```

### My Enrollments View

Students can see:
- All enrolled sections with their times
- Drop button (if within drop window)
- Total enrolled courses and credits

---

## 🔍 Database Query Examples

### Get all courses with their sections and schedules

```sql
SELECT 
    c.course_code,
    c.course_name,
    cs.section_name,
    cs.instructor,
    ss.day_of_week,
    ss.start_time,
    ss.end_time,
    COUNT(e.id) AS enrolled_count,
    cs.capacity
FROM courses c
JOIN course_sections cs ON c.id = cs.course_id
JOIN section_schedules ss ON cs.id = ss.section_id
LEFT JOIN enrollments e ON cs.id = e.section_id AND e.status = 'enrolled'
GROUP BY c.id, cs.id, ss.id
ORDER BY c.course_code, cs.section_name, ss.day_of_week;
```

### Get student's schedule

```sql
SELECT 
    c.course_code,
    c.course_name,
    cs.section_name,
    cs.instructor,
    ss.day_of_week,
    ss.start_time,
    ss.end_time
FROM enrollments e
JOIN course_sections cs ON e.section_id = cs.id
JOIN courses c ON cs.course_id = c.id
JOIN section_schedules ss ON cs.id = ss.section_id
WHERE e.student_id = ? AND e.status = 'enrolled'
ORDER BY ss.day_of_week, ss.start_time;
```

### Check for conflicts for a student trying to enroll in a section

```sql
-- This is what checkScheduleConflicts() does:

-- 1. Get proposed section's schedule
SELECT day_of_week, start_time, end_time
FROM section_schedules
WHERE section_id = ?;

-- 2. Get student's enrolled sections
SELECT DISTINCT cs.id, c.course_name
FROM enrollments e
JOIN course_sections cs ON e.section_id = cs.id
JOIN courses c ON cs.course_id = c.id
WHERE e.student_id = ? AND e.status = 'enrolled';

-- 3. Compare times (done in PHP)
```

---

## 📊 System Architecture

```
┌─────────────────────────────────────┐
│        Student Access               │
│  (courses_new.php)                  │
│                                      │
│  Shows: Courses → Sections →        │
│         Schedules                   │
└────────────────┬────────────────────┘
                 │
                 ├─ getCourseSectionsWithSchedule()
                 │  (from scheduling_helpers.php)
                 │
                 ↓
┌─────────────────────────────────────┐
│     Display Section Options         │
│  - Section name (A, B, C)           │
│  - Instructor                       │
│  - Schedule (Day + Time)            │
│  - Enrollment (X/Y)                 │
│  - [ENROLL] button                  │
└────────────────┬────────────────────┘
                 │
                 │ Student clicks [ENROLL]
                 ↓
┌─────────────────────────────────────┐
│  enroll_action_new.php              │
│  (POST handler)                     │
└────────────────┬────────────────────┘
                 │
                 ├─ checkScheduleConflicts()
                 │  - Get proposed schedule
                 │  - Get student's enrolled sections
                 │  - Compare times
                 │
                 ↓
            ┌────────┐
            │Conflict?
            └───┬────┘
           YES │ NO
               │ ├─────────────────┐
               │                   │
               ↓                   ↓
        ┌────────────┐      ┌──────────────┐
        │Show Warning│      │Enroll Student│
        │  Message   │      │  in Section  │
        └────────────┘      └──────────────┘
```

---

## 🆘 Troubleshooting

### Issue: "Undefined function checkScheduleConflicts"

**Cause:** `scheduling_helpers.php` not included

**Solution:** Check that [server.php](server.php#L12) contains:
```php
require_once('scheduling_helpers.php');
```

### Issue: Conflict detection not working

**Check:**
1. Are `section_schedules` rows properly inserted?
2. Are day_of_week values correct (S, M, T, W, R, F)?
3. Are times in HH:MM format?

**Verify:**
```sql
SELECT * FROM section_schedules ORDER BY section_id, day_of_week;
```

### Issue: Student can see sections but can't enroll

**Check:**
1. Is enrollment window open? (check `enroll_start` and `enroll_end` in courses table)
2. Is section full? (check capacity vs enrolled count)
3. Is there a schedule conflict? (message should show why)

---

## 📈 Next Steps / Future Enhancements

1. **Admin Section Management Panel**
   - Add/edit/delete sections through UI
   - Set instructor and capacity
   - Set schedules with calendar picker

2. **Enhanced Conflict Messages**
   - Show all conflicting courses at once
   - Suggest alternative sections without conflicts
   - Color-code schedule conflicts

3. **Student Schedule View**
   - Visual calendar showing enrolled sections
   - Color-coded by course
   - Export to iCal format

4. **Waitlist System**
   - Allow students to waitlist full sections
   - Auto-enroll if spot opens
   - Automatic conflict checking before auto-enroll

5. **Prerequisites & Co-requisites**
   - Block enrollment if prerequisite not met
   - Warn if co-requisite not also enrolled

---

## 📞 Support

For issues or questions, check:
- [SCHEDULING_SYSTEM_GUIDE.md](SCHEDULING_SYSTEM_GUIDE.md)
- [IMPLEMENTATION_SUMMARY.md](IMPLEMENTATION_SUMMARY.md)
- [README_SCHEDULING.md](README_SCHEDULING.md)

---

**Last Updated:** January 24, 2026
**System Status:** ✅ FULLY OPERATIONAL
