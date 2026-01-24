# Conflict Detection Flow - Technical Deep Dive

## 📌 Overview

Your system implements **real-time schedule conflict detection** to prevent students from enrolling in sections that overlap with their existing course schedule.

---

## 🔄 Complete Enrollment Flow

```
┌─────────────────────────────────────────────────────┐
│           Student Views Course List                 │
│           (courses_new.php)                         │
│                                                     │
│  TEST101 - Programming                              │
│  ├─ Section A | Mon/Wed 08:00-09:30 [ENROLL]       │
│  ├─ Section B | Tue/Thu 10:00-11:30 [ENROLL]       │
│  └─ Section C | Mon/Wed 13:00-14:30 [ENROLL]       │
└──────────────────┬──────────────────────────────────┘
                   │ Student clicks [ENROLL]
                   ↓
┌─────────────────────────────────────────────────────┐
│        POST to enroll_action_new.php                │
│                                                     │
│  Parameters:                                        │
│  - action: "enroll"                                │
│  - section_id: 1  (Section A)                      │
│  - student_id: 001                                 │
└──────────────────┬──────────────────────────────────┘
                   │
                   ↓
    ┌──────────────────────────────┐
    │ Verify section exists         │
    │ Verify student exists         │
    └──────────────┬────────────────┘
                   │
                   ↓
    ┌──────────────────────────────────────────┐
    │ CHECK FOR SCHEDULE CONFLICTS             │
    │ (Line 87 in enroll_action_new.php)      │
    │ checkScheduleConflicts($db, $student, $section)
    └──────────────┬───────────────────────────┘
                   │
                   ↓ INSIDE checkScheduleConflicts()
    ╔════════════════════════════════════════════╗
    ║  CONFLICT DETECTION ALGORITHM              ║
    ╚════════════════════════════════════════════╝
                   │
           ┌───────┴────────┐
           │                │
        Step 1            Step 2
        │                │
        ↓                ↓
    ┌─────────────┐  ┌──────────────────────┐
    │Get proposed │  │Get student's current │
    │section's    │  │enrolled sections     │
    │schedule     │  │and their times       │
    │             │  │                      │
    │Section A:   │  │Student is in:        │
    │M: 08:00-    │  │- TEST102 Sec A:      │
    │   09:30     │  │  M: 08:00-09:30      │
    │W: 08:00-    │  │  W: 08:00-09:30      │
    │   09:30     │  │                      │
    └─────────────┘  └──────────────────────┘
        │                │
        └────────┬───────┘
                 │
              Step 3
                 │
                 ↓
    ┌────────────────────────────────┐
    │ FOR EACH proposed schedule     │
    │ FOR EACH student's schedule    │
    │                                │
    │  Proposed: M 08:00-09:30       │
    │  Existing: M 08:00-09:30       │
    │                                │
    │  Check: Same day? YES ✓        │
    │  Check: Time overlap? YES ✓    │
    │                                │
    │  Result: CONFLICT FOUND!       │
    └────────────────┬───────────────┘
                     │
           ┌─────────┴──────────┐
           │                    │
           ↓ (Conflict)         ↓ (No Conflict)
    ┌──────────────┐      ┌──────────────┐
    │Return array: │      │Return: null  │
    │[{            │      │(No conflicts)│
    │  course_code:│      └──────────────┘
    │  TEST102,    │             │
    │  course_name:│             │
    │  DB Design,  │             │
    │  day: Monday,│             │
    │  time: 08:00│             │
    │   -09:30     │             │
    │}]            │             │
    └──────┬───────┘             │
           │                     │
           └─────────┬───────────┘
                     │
                     ↓
           ┌──────────────────┐
           │ Back in          │
           │enroll_action_new │
           └────────┬─────────┘
                    │
            ┌───────┴────────┐
            │                │
        YES │ NO             │ NO │ YES
       (has conflict)   (no conflict)
            │                │
            ↓                ↓
    ┌──────────────┐  ┌─────────────────┐
    │Show error    │  │Insert into      │
    │message:      │  │enrollments      │
    │              │  │table            │
    │"⚠️ Schedule  │  │                 │
    │ Conflict:    │  │INSERT INTO      │
    │              │  │enrollments      │
    │You already   │  │(student_id,     │
    │have course(s)│  │section_id,      │
    │at this time: │  │course_id,       │
    │              │  │status)          │
    │• TEST102 -   │  │VALUES           │
    │  Database    │  │(001, 1, 1,      │
    │  Design      │  │'enrolled')      │
    │              │  │                 │
    │Monday        │  │✅ SUCCESS       │
    │08:00-09:30"  │  │message shown    │
    │              │  │                 │
    │❌ BLOCKED    │  │✅ ENROLLED      │
    │              │  │                 │
    └──────────────┘  └─────────────────┘
            │                │
            └────────┬───────┘
                     │
                     ↓
    ┌──────────────────────────┐
    │ Redirect back to         │
    │ courses_new.php          │
    │                          │
    │ Display result message   │
    └──────────────────────────┘
```

---

## 🔍 Code Walkthrough

### 1. Student Clicks Enroll

**File:** [courses_new.php](courses_new.php#L185)

```php
<form method='post' action='enroll_action_new.php' style='display:inline;'>
    <input type='hidden' name='action' value='enroll'>
    <input type='hidden' name='section_id' value='<?php echo $section['id']; ?>'>
    <button type='submit' class='btn btn-small' style='background:#28a745;'>Enroll</button>
</form>
```

**What Happens:**
- Sends POST request to `enroll_action_new.php`
- Passes: `section_id` (which section they want)

---

### 2. Enroll Action Handler Receives Request

**File:** [enroll_action_new.php](enroll_action_new.php#L1-40)

```php
<?php 
include('server.php');
session_start();

// Get the action and section_id
$action = $_POST['action'] ?? '';  // 'enroll' or 'drop'
$section_id = intval($_POST['section_id'] ?? 0);  // e.g., 1
$username = $_SESSION['username'];

// Get student_ref_id from username
$student_ref_id = null;
$stmt = mysqli_prepare($db, "
    SELECT student_ref_id 
    FROM users 
    WHERE username = ? 
    LIMIT 1
");
// ... bind and execute ...
```

**What Happens:**
- Gets student's ID from their username
- Gets the section they're trying to join

---

### 3. Call Conflict Detection Function

**File:** [enroll_action_new.php](enroll_action_new.php#L87-91)

```php
if ($action === 'enroll') {
    // Check for schedule conflicts
    $conflicts = checkScheduleConflicts($db, $student_ref_id, $section_id);
    
    if ($conflicts) {
        // CONFLICT FOUND - show error and exit
        $_SESSION['errors'][] = "You already have a course at this time:";
        foreach ($conflicts as $conflict) {
            $_SESSION['errors'][] = "  • " . $conflict['course_code'] . " - " . 
                                   $conflict['course_name'] . 
                                   " (" . $conflict['day'] . " " . $conflict['time'] . ")";
        }
        header('location: courses_new.php');
        exit();
    }
    
    // NO CONFLICT - proceed with enrollment
    $ins = mysqli_prepare($db, "
        INSERT INTO enrollments (student_id, section_id, course_id, status, enrolled_at) 
        VALUES (?, ?, ?, 'enrolled', CURRENT_TIMESTAMP)
    ");
    // ... execute ...
}
```

---

### 4. Conflict Detection Function (THE CORE)

**File:** [scheduling_helpers.php](scheduling_helpers.php#L97-190)

#### Step A: Get the Proposed Section's Schedule

```php
function checkScheduleConflicts($db, $student_id, $proposed_section_id) {
    
    // STEP A: Get the proposed section's schedule
    $proposed_schedule = [];
    $stmt = mysqli_prepare($db, "
        SELECT ss.day_of_week, ss.start_time, ss.end_time, 
               cs.course_id, c.course_name, c.course_code
        FROM section_schedules ss
        JOIN course_sections cs ON ss.section_id = cs.id
        JOIN courses c ON cs.course_id = c.id
        WHERE ss.section_id = ?
    ");
    
    mysqli_stmt_bind_param($stmt, 'i', $proposed_section_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $day, $start, $end, $course_id, $course_name, $course_code);
    
    while (mysqli_stmt_fetch($stmt)) {
        $proposed_schedule[] = [
            'day' => $day,
            'start_time' => $start,
            'end_time' => $end,
            'course_id' => $course_id,
            'course_name' => $course_name,
            'course_code' => $course_code
        ];
    }
    mysqli_stmt_close($stmt);
```

**What This Does:**
```
SELECT from section_schedules:
  WHERE section_id = 1  (the section they want to join)

Result might be:
  day: M, start: 08:00, end: 09:30, course: TEST101
  day: W, start: 08:00, end: 09:30, course: TEST101
```

#### Step B: Get Student's Currently Enrolled Sections

```php
    // STEP B: Get student's current enrolled sections
    $student_sections = [];
    $stmt = mysqli_prepare($db, "
        SELECT DISTINCT cs.id, c.course_name, c.course_code
        FROM enrollments e
        JOIN course_sections cs ON e.section_id = cs.id
        JOIN courses c ON cs.course_id = c.id
        WHERE e.student_id = ? AND e.status = 'enrolled'
    ");
    
    mysqli_stmt_bind_param($stmt, 'i', $student_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $section_id, $course_name, $course_code);
    
    $conflicting_courses = [];
    
    while (mysqli_stmt_fetch($stmt)) {
        // Get schedule for this existing section
        $section_stmt = mysqli_prepare($db, "
            SELECT day_of_week, start_time, end_time
            FROM section_schedules
            WHERE section_id = ?
        ");
        // ... get existing section's schedule ...
```

**What This Does:**
```
SELECT from enrollments WHERE student_id = 001 AND status = enrolled

Result might be:
  section_id: 2 (TEST102 Section A)
  course_name: Database Design
  course_code: TEST102

Then get its schedule:
SELECT from section_schedules WHERE section_id = 2
Result:
  day: M, start: 08:00, end: 09:30
  day: W, start: 08:00, end: 09:30
```

#### Step C: Compare Times

```php
        // STEP C: Compare each proposed slot with existing slots
        foreach ($proposed_schedule as $proposed) {
            while (mysqli_stmt_fetch($section_stmt)) {
                // Check if same day and time overlap
                if ($existing_day === $proposed['day']) {
                    if (checkTimeOverlap($existing_start, $existing_end, 
                                        $proposed['start_time'], $proposed['end_time'])) {
                        // CONFLICT FOUND!
                        $conflicting_courses[] = [
                            'course_code' => $course_code,
                            'course_name' => $course_name,
                            'day' => getDayName($existing_day),
                            'time' => $existing_start . ' - ' . $existing_end
                        ];
                        break 2;
                    }
                }
            }
        }
```

**Comparison Logic:**

```
Proposed: Monday 08:00-09:30
Existing: Monday 08:00-09:30

Check 1: Same day?  M === M ✓ YES
Check 2: Time overlap?
  
  checkTimeOverlap(08:00, 09:30, 08:00, 09:30)
  
  Converts to minutes:
    start1 = 480 (8*60)
    end1 = 570 (8*60 + 90)
    start2 = 480
    end2 = 570
  
  Returns: NOT(570 <= 480 OR 570 <= 480)
         = NOT(false OR false)
         = NOT(false)
         = TRUE (OVERLAP!)
```

**Other Examples:**

```
Proposed: Monday 08:00-09:30
Existing: Monday 09:00-10:30

start1=480, end1=570, start2=540, end2=630
NOT(570 <= 540 OR 630 <= 480)
NOT(false OR false)
TRUE → OVERLAP ✓

---

Proposed: Monday 08:00-09:30
Existing: Monday 10:00-11:30

start1=480, end1=570, start2=600, end2=690
NOT(570 <= 600 OR 690 <= 480)
NOT(true OR false)
FALSE → NO OVERLAP ✓

---

Proposed: Monday 08:00-09:30
Existing: Tuesday 08:00-09:30

Same day? M !== T ✗ NO
→ No conflict check needed
```

---

## 📊 Example Conflict Scenario

### Setup:
- **Student 001** is enrolled in: TEST102 Section A (M/W 08:00-09:30)
- **Student 001** tries to enroll in: TEST101 Section A (M/W 08:00-09:30)

### Execution:

```
1. Get Proposed Schedule:
   FROM section_schedules WHERE section_id = 1 (TEST101-A)
   Results:
     - day: M, time: 08:00-09:30
     - day: W, time: 08:00-09:30

2. Get Student's Sections:
   FROM enrollments WHERE student_id = 001 AND status = enrolled
   Results:
     - section_id: 3 (TEST102-A)

3. Get TEST102-A Schedule:
   FROM section_schedules WHERE section_id = 3
   Results:
     - day: M, time: 08:00-09:30
     - day: W, time: 08:00-09:30

4. Compare:
   Proposed M 08:00-09:30 vs Existing M 08:00-09:30
   Same day? YES
   Time overlap? YES
   → CONFLICT FOUND!

5. Return:
   [
     {
       'course_code' => 'TEST102',
       'course_name' => 'Database Design',
       'day' => 'Monday',
       'time' => '08:00 - 09:30'
     }
   ]

6. Display Error:
   ⚠️ Schedule Conflict:
   You already have course(s) at this time:
   • TEST102 - Database Design
     Monday 08:00 - 09:30

7. Result: ENROLLMENT BLOCKED ❌
```

---

## 🎯 Key Decision Points

### 1. Is section full?
- Check: `capacity >= enrolled_count`
- If YES → Block enrollment (section full message)

### 2. Do schedules conflict?
- Check: `checkScheduleConflicts()`
- If YES → Block enrollment (show conflict warning)
- If NO → Continue

### 3. Is student already enrolled?
- Check: Enrollments table for this student + section
- If YES → Block enrollment (already enrolled)
- If NO → Continue

### 4. Proceed with enrollment
- Insert into enrollments table
- Show success message

---

## 🔐 Multiple Schedule Entries Per Section

Note: A section can have **multiple days and times**.

**Example: TEST101 Section A**
```
section_id: 1

section_schedules entries:
  - section_id: 1, day: M, start: 08:00, end: 09:30
  - section_id: 1, W, start: 08:00, end: 09:30
```

So this section meets on **both Monday AND Wednesday**.

---

## ✅ Testing Conflict Detection

### Test Case 1: No Conflict

**Setup:**
- Student enrolled in: TEST101-B (T/R 10:00-11:30)
- Tries to enroll in: TEST101-A (M/W 08:00-09:30)

**Check:**
- T ≠ M (different day) → No conflict
- W ≠ T (different day) → No conflict
- R ≠ M (different day) → No conflict
- R ≠ W (different day) → No conflict

**Result:** ✅ Enrollment allowed

---

### Test Case 2: Conflict on Same Day

**Setup:**
- Student enrolled in: TEST101-A (M/W 08:00-09:30)
- Tries to enroll in: TEST102-A (M/W 08:00-09:30)

**Check:**
- Proposed M overlaps existing M ✓
- Time: 08:00-09:30 vs 08:00-09:30 = OVERLAP ✓

**Result:** ❌ Enrollment blocked, shows:
```
⚠️ Schedule Conflict:
• TEST101 - Programming
  Monday 08:00 - 09:30
```

---

### Test Case 3: Partial Overlap

**Setup:**
- Student enrolled in: TEST101-A (M 08:00-09:30)
- Tries to enroll in: TEST102-B (M 09:00-10:30)

**Check:**
- Same day: M = M ✓
- Time overlap: 09:00-10:30 overlaps 08:00-09:30? YES (they overlap at 09:00-09:30) ✓

**Result:** ❌ Enrollment blocked (they overlap by 30 minutes)

---

## 📈 Performance Considerations

### Optimization:

The function uses **prepared statements** for security:
```php
$stmt = mysqli_prepare($db, "SELECT ... WHERE section_id = ?");
mysqli_stmt_bind_param($stmt, 'i', $proposed_section_id);
```

### Database Queries:
1. Get proposed schedule: ~1-10 rows (days per section)
2. Get student's sections: ~1-5 rows (courses per student)
3. Get each section's schedule: ~1-10 rows per course

**Total:** Usually < 50 database rows checked per enrollment

---

## 🎓 Summary

**Your Conflict Detection System:**

```
✅ Checks same day of week
✅ Checks time overlap (exact minute comparison)
✅ Blocks conflicting enrollments
✅ Shows conflicting course name and time
✅ Uses prepared statements (SQL injection safe)
✅ Handles multiple schedule entries per section
✅ Works for multi-day classes (M/W, T/R, etc.)
```

This is a **production-ready implementation** of course conflict detection!
