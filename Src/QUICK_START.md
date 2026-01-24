# Quick Start Guide - Multiple Sections & Conflict Detection

## 🚀 What You Have

Your system is **FULLY IMPLEMENTED** with:
- ✅ **Multiple sections per course** (A, B, C, etc.)
- ✅ **Time and day scheduling** for each section
- ✅ **Automatic conflict detection** when a student enrolls
- ✅ **Shows conflicting course name and time** when there's a clash

---

## 📍 Key Files

### For Students (Enrollment)
- **courses_new.php** - Shows all courses with their sections
- **enroll_action_new.php** - Handles enrollment with conflict checking

### Backend
- **scheduling_helpers.php** - Contains all scheduling and conflict logic
- **server.php** - Database connection (includes scheduling_helpers)

### Database Setup
- **test_setup.sql** - Creates TEST courses with sample data

---

## ⚡ 5-Minute Setup

### 1. Load Sample Data
```bash
Open phpMyAdmin → Your Database → Import
Choose: test_setup.sql → Execute
```

This creates:
- TEST101 (3 sections with schedules)
- TEST102 (3 sections with schedules)
- Intentional schedule conflicts for testing

### 2. Start Apache & MySQL
```bash
Open XAMPP Control Panel
Start: Apache + MySQL
```

### 3. Login & Test
```
URL: localhost/Project2/index.php
Login with student username/password
```

### 4. Go to "Courses"
- See all courses with their sections
- Each section shows: Instructor, Time/Day, Enrollment count

### 5. Try Enrolling
- Click [ENROLL] for any section
- If no conflicts → ✅ Enrolled
- If conflicts → ⚠️ Shows which course clashes

---

## 🔍 How Conflict Detection Works

**Example Scenario:**

```
TEST101 Section A: Monday & Wednesday 08:00-09:30 (Dr. Smith)
TEST102 Section A: Monday & Wednesday 08:00-09:30 (Dr. Brown)
    ↓
    Same day AND overlapping time
    ↓
    ⚠️ CONFLICT!
```

**What Student Sees:**
1. Already enrolled: TEST101-A (M/W 08:00-09:30)
2. Tries to enroll: TEST102-A (M/W 08:00-09:30)
3. System shows warning:
   ```
   ⚠️ Schedule Conflict:
   You already have course(s) at this time:
   • TEST101 - Programming
     Monday 08:00 - 09:30
   ```
4. Enrollment blocked ❌

---

## 📋 How It's Stored

### Courses Table
```
id  | course_code | course_name
1   | TEST101     | Programming
2   | TEST102     | Database Design
```

### Sections Table
```
id  | course_id | section_name | instructor
1   | 1         | A            | Dr. Smith
2   | 1         | B            | Dr. Johnson
3   | 2         | A            | Dr. Brown
```

### Schedules Table (THE KEY!)
```
id  | section_id | day_of_week | start_time | end_time
1   | 1          | M           | 08:00      | 09:30
2   | 1          | W           | 08:00      | 09:30
3   | 2          | T           | 10:00      | 11:30
4   | 2          | R           | 10:00      | 11:30
```

### Enrollments Table
```
id  | student_id | section_id | course_id | status
1   | 001        | 1          | 1         | enrolled
2   | 001        | 4          | 2         | enrolled
```

---

## 🔧 Adding More Courses

### Using SQL
```sql
-- 1. Insert course
INSERT INTO courses (course_code, course_name, credits, max_students) 
VALUES ('CS201', 'Data Structures', 3, 100);

-- 2. Get the ID
SET @cid = (SELECT id FROM courses WHERE course_code = 'CS201');

-- 3. Add sections
INSERT INTO course_sections (course_id, section_name, instructor, capacity) 
VALUES 
(@cid, 'A', 'Dr. Lee', 30),
(@cid, 'B', 'Dr. Park', 30);

-- 4. Get section IDs
SET @sA = (SELECT id FROM course_sections WHERE course_id = @cid AND section_name = 'A');
SET @sB = (SELECT id FROM course_sections WHERE course_id = @cid AND section_name = 'B');

-- 5. Add schedules
INSERT INTO section_schedules (section_id, day_of_week, start_time, end_time) 
VALUES 
(@sA, 'M', '13:00', '14:30'),
(@sA, 'W', '13:00', '14:30'),
(@sB, 'T', '14:30', '16:00'),
(@sB, 'R', '14:30', '16:00');
```

### Day Codes
| Code | Day |
|------|-----|
| S | Sunday |
| M | Monday |
| T | Tuesday |
| W | Wednesday |
| R | Thursday |
| F | Friday |
| X | Flexible |

---

## ✅ Verify Everything Works

Run this in your browser:
```
localhost/Project2/verify_system.php
```

You'll see:
- ✅ All required tables exist
- ✅ All functions are available
- ✅ Sample data is loaded

---

## 🎯 Student Workflow

```
1. Login → 2. Click "Courses" → 3. See sections → 4. Click [ENROLL]
                                                    ↓
                            Check for conflicts → 5. Show warning or confirm
```

---

## 🆘 Troubleshooting

**Q: Can't see sections?**
- A: Run `test_setup.sql` first

**Q: [ENROLL] button doesn't work?**
- A: Check browser console for errors
- A: Verify `enroll_action_new.php` exists

**Q: Conflicts not showing?**
- A: Make sure `scheduling_helpers.php` is included in server.php
- A: Check that sections have schedules (section_schedules table)

**Q: Why can I enroll in conflicting courses?**
- A: If it says "OK to enroll" but shouldn't, the conflict detection isn't working
- A: Run verify_system.php to check functions

---

## 📚 More Information

For detailed information, see:
- **COMPLETE_SYSTEM_GUIDE.md** - Full technical guide
- **SCHEDULING_SYSTEM_GUIDE.md** - Scheduling documentation
- **README_SCHEDULING.md** - Implementation notes

---

**Your System:** ✅ READY TO USE

Just run `test_setup.sql` and start testing!
