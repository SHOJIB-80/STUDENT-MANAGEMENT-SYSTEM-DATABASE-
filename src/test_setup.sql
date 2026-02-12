-- Test Setup: 2 Courses with 3 Sections Each
-- Sections will have overlapping times for conflict testing

USE student_management_system;

-- Insert 2 Test Courses
INSERT INTO courses (course_code, course_name, credits, max_students) 
VALUES 
('TEST101', 'Test Course 1 - Programming', 3, 100),
('TEST102', 'Test Course 2 - Database Design', 3, 100)
ON DUPLICATE KEY UPDATE course_code=course_code;

-- Get the IDs of the newly inserted courses
SET @course1_id = (SELECT id FROM courses WHERE course_code = 'TEST101' LIMIT 1);
SET @course2_id = (SELECT id FROM courses WHERE course_code = 'TEST102' LIMIT 1);

-- TEST COURSE 1: Create 3 Sections
INSERT INTO course_sections (course_id, section_name, instructor, capacity) 
VALUES 
(@course1_id, 'A', 'Dr. Smith', 30),
(@course1_id, 'B', 'Dr. Johnson', 30),
(@course1_id, 'C', 'Dr. Williams', 30)
ON DUPLICATE KEY UPDATE section_name=section_name;

-- TEST COURSE 2: Create 3 Sections
INSERT INTO course_sections (course_id, section_name, instructor, capacity) 
VALUES 
(@course2_id, 'A', 'Dr. Brown', 30),
(@course2_id, 'B', 'Dr. Davis', 30),
(@course2_id, 'C', 'Dr. Miller', 30)
ON DUPLICATE KEY UPDATE section_name=section_name;

-- Get section IDs
SET @t1_sec_a = (SELECT id FROM course_sections WHERE course_id = @course1_id AND section_name = 'A' LIMIT 1);
SET @t1_sec_b = (SELECT id FROM course_sections WHERE course_id = @course1_id AND section_name = 'B' LIMIT 1);
SET @t1_sec_c = (SELECT id FROM course_sections WHERE course_id = @course1_id AND section_name = 'C' LIMIT 1);
SET @t2_sec_a = (SELECT id FROM course_sections WHERE course_id = @course2_id AND section_name = 'A' LIMIT 1);
SET @t2_sec_b = (SELECT id FROM course_sections WHERE course_id = @course2_id AND section_name = 'B' LIMIT 1);
SET @t2_sec_c = (SELECT id FROM course_sections WHERE course_id = @course2_id AND section_name = 'C' LIMIT 1);

-- TEST COURSE 1 SCHEDULES
-- Section A: Monday & Wednesday 08:00-09:30
INSERT INTO section_schedules (section_id, day_of_week, start_time, end_time) 
VALUES 
(@t1_sec_a, 'M', '08:00', '09:30'),
(@t1_sec_a, 'W', '08:00', '09:30')
ON DUPLICATE KEY UPDATE day_of_week=day_of_week;

-- Section B: Tuesday & Thursday 10:00-11:30
INSERT INTO section_schedules (section_id, day_of_week, start_time, end_time) 
VALUES 
(@t1_sec_b, 'T', '10:00', '11:30'),
(@t1_sec_b, 'R', '10:00', '11:30')
ON DUPLICATE KEY UPDATE day_of_week=day_of_week;

-- Section C: Monday & Wednesday 13:00-14:30 (different time)
INSERT INTO section_schedules (section_id, day_of_week, start_time, end_time) 
VALUES 
(@t1_sec_c, 'M', '13:00', '14:30'),
(@t1_sec_c, 'W', '13:00', '14:30')
ON DUPLICATE KEY UPDATE day_of_week=day_of_week;

-- TEST COURSE 2 SCHEDULES
-- Section A: Monday & Wednesday 08:00-09:30 (SAME AS TEST101 Section A - CONFLICT!)
INSERT INTO section_schedules (section_id, day_of_week, start_time, end_time) 
VALUES 
(@t2_sec_a, 'M', '08:00', '09:30'),
(@t2_sec_a, 'W', '08:00', '09:30')
ON DUPLICATE KEY UPDATE day_of_week=day_of_week;

-- Section B: Tuesday & Thursday 14:00-15:30
INSERT INTO section_schedules (section_id, day_of_week, start_time, end_time) 
VALUES 
(@t2_sec_b, 'T', '14:00', '15:30'),
(@t2_sec_b, 'R', '14:00', '15:30')
ON DUPLICATE KEY UPDATE day_of_week=day_of_week;

-- Section C: Friday 10:00-11:30
INSERT INTO section_schedules (section_id, day_of_week, start_time, end_time) 
VALUES 
(@t2_sec_c, 'F', '10:00', '11:30')
ON DUPLICATE KEY UPDATE day_of_week=day_of_week;

-- Display the created courses and sections
SELECT 'COURSES AND SECTIONS CREATED:' AS Status;

SELECT 
    c.course_code,
    c.course_name,
    cs.section_name,
    cs.instructor,
    GROUP_CONCAT(CONCAT(ss.day_of_week, ' ', ss.start_time, '-', ss.end_time) SEPARATOR ', ') AS Schedule
FROM courses c
LEFT JOIN course_sections cs ON c.id = cs.course_id
LEFT JOIN section_schedules ss ON cs.id = ss.section_id
WHERE c.course_code IN ('TEST101', 'TEST102')
GROUP BY c.id, cs.id, cs.section_name
ORDER BY c.course_code, cs.section_name;

SELECT '' AS '';
SELECT '✅ TEST SETUP COMPLETE!' AS Status;
SELECT 'TEST101-A and TEST102-A have the SAME TIME (Mon/Wed 08:00-09:30)' AS ConflictInfo;
SELECT 'Try to enroll in both to test conflict detection!' AS Instructions;
