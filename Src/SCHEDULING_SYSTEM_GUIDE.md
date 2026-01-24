# Course Scheduling System - Implementation Guide

## Overview
This system adds comprehensive course scheduling with sections, time slots, and conflict detection to prevent students from enrolling in courses that clash with their existing schedule.

## Key Features

1. **Course Sections**: Each course can have multiple sections (A, B, C or 01, 02, 03)
2. **Scheduled Classes**: Each section has specific days and times
   - Days: Sunday (S), Monday (M), Tuesday (T), Wednesday (W), Thursday (R), Friday (F)
   - Time: 8 AM to 8 PM
   - Duration: 1.5 hours per course
   - Break: 10 minutes between courses
   
3. **Automatic Schedule Conflict Detection**: System prevents enrollment if:
   - Student already has a course at the same time
   - Shows which course conflicts with the new enrollment

## Installation Steps

### Step 1: Run Migration SQL
1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Select your database `student_management_system`
3. Go to the SQL tab
4. Copy and paste the contents of `migration_scheduling.sql`
5. Click Execute

This will create:
- `course_sections` table
- `section_schedules` table
- Update `enrollments` table with `section_id` column

### Step 2: Verify Files Created
Confirm these new files exist in your Project2 folder:
- `scheduling_helpers.php` - Helper functions
- `courses_new.php` - Updated course listing page
- `enroll_action_new.php` - Updated enrollment logic with conflict checking
- `admin_sections.php` - Admin page to manage sections and schedules

### Step 3: Update Navigation (Optional)
If you want to use the new course system:
- Update your navigation menu to link to `courses_new.php` instead of `courses.php`
- Or add a separate "Browse Courses" link to `courses_new.php`

## Usage

### For Admins

1. **Create Course Sections**
   - Go to Admin → Manage Sections & Schedules
   - Select a course
   - Enter section name, instructor, and capacity
   - Click "Create Section"

2. **Add Schedule to Section**
   - Select the day of week
   - Select start time (system calculates end time as +1.5 hours)
   - System auto-calculates 10-minute break
   - Click "Add Schedule"

3. **View Available Time Slots**
   - Reference table shows all available slots from 8 AM to 8 PM
   - Each slot: 1.5 hours course + 10 min break = 100 minutes between start times

### For Students

1. **Browse Courses with Sections**
   - Go to "Browse Courses"
   - Each course shows all available sections
   - Each section displays:
     - Section name and instructor
     - Days and times (e.g., "Monday 08:00-09:30")
     - Current enrollment / capacity

2. **Enroll with Conflict Check**
   - Click "Enroll" button
   - System automatically checks for schedule conflicts
   - If conflict exists:
     - Shows warning message
     - Lists conflicting course(s) with day and time
     - Prevents enrollment
   - If no conflict:
     - Enrollment succeeds
   - Enrolled students see "✓ Enrolled" badge

3. **View My Enrollments**
   - Shows all enrolled sections with schedules

## Database Schema

### course_sections Table
```
- id: Primary key
- course_id: Foreign key to courses
- section_name: e.g., "A", "B", "01", "02"
- instructor: Instructor name
- capacity: Max students
- current_enrollment: For reference
- created_at: Timestamp
```

### section_schedules Table
```
- id: Primary key
- section_id: Foreign key to course_sections
- day_of_week: S, M, T, W, R, F
- start_time: HH:MM format
- end_time: Automatically calculated (start_time + 90 minutes)
- created_at: Timestamp
```

### enrollments Table (Updated)
- Added `section_id` column (foreign key to course_sections)
- This replaces direct course enrollment with section-based enrollment

## Time Slot Configuration

Current settings (in `scheduling_helpers.php`):
- START_HOUR: 8 (8 AM)
- END_HOUR: 20 (8 PM)
- COURSE_DURATION: 90 minutes
- BREAK_DURATION: 10 minutes
- Total slot time: 100 minutes

Example schedule:
- Slot 1: 08:00 - 09:30 (break until 09:40)
- Slot 2: 09:40 - 11:10 (break until 11:20)
- Slot 3: 11:20 - 12:50 (break until 13:00)
- ... continuing until 20:00 (8 PM)

## Conflict Detection Algorithm

The system checks for conflicts by:
1. Retrieving the proposed section's schedule
2. Retrieving the student's currently enrolled sections
3. Comparing each day-time combination
4. If same day AND times overlap → Conflict detected
5. Returns conflicting course info to display to student

## File Descriptions

### scheduling_helpers.php
- `getAvailableTimeSlots()`: Returns all valid time slots for the day
- `getDayName()`: Converts day characters to names
- `checkTimeOverlap()`: Compares two time ranges
- `checkScheduleConflicts()`: Main conflict detection function
- `getCourseSectionsWithSchedule()`: Gets sections with their schedules

### courses_new.php
- Students browse courses and sections
- Displays schedule for each section
- Shows conflict warnings if applicable
- Enroll/drop buttons

### enroll_action_new.php
- Handles enroll and drop actions
- Calls `checkScheduleConflicts()` before enrollment
- Provides detailed error messages about conflicts

### admin_sections.php
- Admin interface for managing sections
- Create new sections
- Add schedules to sections
- View enrollment status
- Reference table of available time slots

## Troubleshooting

1. **"Undefined function" errors**
   - Make sure `scheduling_helpers.php` is included in `server.php`
   - Check file paths are correct

2. **Database errors after migration**
   - Ensure migration SQL ran successfully
   - Check that tables were created: `course_sections`, `section_schedules`
   - Verify `enrollments` table has `section_id` column

3. **Conflicts not being detected**
   - Check that section schedules are set correctly
   - Verify student is enrolled in the conflicting course
   - Check day_of_week values (must be S, M, T, W, R, or F)

4. **Time calculation issues**
   - Verify times are in 24-hour HH:MM format
   - Check START_HOUR and END_HOUR in `scheduling_helpers.php`

## Future Enhancements

Potential improvements:
- Add room locations to sections
- Instructor preference for scheduling
- Prerequisite courses
- Class capacity waitlist
- Schedule changes/updates
- Cross-listed sections
- Lab/lecture combinations
