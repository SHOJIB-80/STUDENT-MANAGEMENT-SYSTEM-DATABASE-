-- Direct Insert: Add Sections and Schedules for TEST Courses

USE student_management_system;

-- First, get the course IDs
SELECT id, course_code, course_name FROM courses WHERE course_code IN ('TEST101', 'TEST102');

-- Get the ID for TEST101
SELECT @test101_id := id FROM courses WHERE course_code = 'TEST101' LIMIT 1;
SELECT @test102_id := id FROM courses WHERE course_code = 'TEST102' LIMIT 1;

-- Show what we got
SELECT CONCAT('TEST101 ID: ', @test101_id, ' | TEST102 ID: ', @test102_id) AS CourseIDs;

-- Delete old sections if any exist
DELETE FROM section_schedules WHERE section_id IN (SELECT id FROM course_sections WHERE course_id IN (@test101_id, @test102_id));
DELETE FROM course_sections WHERE course_id IN (@test101_id, @test102_id);

-- INSERT SECTIONS FOR TEST101
INSERT INTO course_sections (course_id, section_name, instructor, capacity) VALUES
(@test101_id, 'A', 'Dr. Smith', 30),
(@test101_id, 'B', 'Dr. Johnson', 30),
(@test101_id, 'C', 'Dr. Williams', 30);

-- INSERT SECTIONS FOR TEST102
INSERT INTO course_sections (course_id, section_name, instructor, capacity) VALUES
(@test102_id, 'A', 'Dr. Brown', 30),
(@test102_id, 'B', 'Dr. Davis', 30),
(@test102_id, 'C', 'Dr. Miller', 30);

-- Get section IDs for TEST101
SELECT @t1a := id FROM course_sections WHERE course_id = @test101_id AND section_name = 'A' LIMIT 1;
SELECT @t1b := id FROM course_sections WHERE course_id = @test101_id AND section_name = 'B' LIMIT 1;
SELECT @t1c := id FROM course_sections WHERE course_id = @test101_id AND section_name = 'C' LIMIT 1;

-- Get section IDs for TEST102
SELECT @t2a := id FROM course_sections WHERE course_id = @test102_id AND section_name = 'A' LIMIT 1;
SELECT @t2b := id FROM course_sections WHERE course_id = @test102_id AND section_name = 'B' LIMIT 1;
SELECT @t2c := id FROM course_sections WHERE course_id = @test102_id AND section_name = 'C' LIMIT 1;

-- INSERT SCHEDULES FOR TEST101 SECTIONS
-- TEST101-A: Monday & Wednesday 08:00-09:30
INSERT INTO section_schedules (section_id, day_of_week, start_time, end_time) VALUES
(@t1a, 'M', '08:00', '09:30'),
(@t1a, 'W', '08:00', '09:30');

-- TEST101-B: Tuesday & Thursday 10:00-11:30
INSERT INTO section_schedules (section_id, day_of_week, start_time, end_time) VALUES
(@t1b, 'T', '10:00', '11:30'),
(@t1b, 'R', '10:00', '11:30');

-- TEST101-C: Monday & Wednesday 13:00-14:30
INSERT INTO section_schedules (section_id, day_of_week, start_time, end_time) VALUES
(@t1c, 'M', '13:00', '14:30'),
(@t1c, 'W', '13:00', '14:30');

-- INSERT SCHEDULES FOR TEST102 SECTIONS
-- TEST102-A: Monday & Wednesday 08:00-09:30 (SAME AS TEST101-A - CONFLICT!)
INSERT INTO section_schedules (section_id, day_of_week, start_time, end_time) VALUES
(@t2a, 'M', '08:00', '09:30'),
(@t2a, 'W', '08:00', '09:30');

-- TEST102-B: Tuesday & Thursday 14:00-15:30
INSERT INTO section_schedules (section_id, day_of_week, start_time, end_time) VALUES
(@t2b, 'T', '14:00', '15:30'),
(@t2b, 'R', '14:00', '15:30');

-- TEST102-C: Friday 10:00-11:30
INSERT INTO section_schedules (section_id, day_of_week, start_time, end_time) VALUES
(@t2c, 'F', '10:00', '11:30');

-- VERIFY: Show all created sections with schedules
SELECT '=== VERIFICATION ===' AS Status;
SELECT 
    c.course_code,
    c.course_name,
    cs.section_name,
    cs.instructor,
    cs.capacity,
    ss.day_of_week,
    ss.start_time,
    ss.end_time
FROM courses c
JOIN course_sections cs ON c.id = cs.course_id
JOIN section_schedules ss ON cs.id = ss.section_id
WHERE c.course_code IN ('TEST101', 'TEST102')
ORDER BY c.course_code, cs.section_name, ss.day_of_week;

SELECT '' AS '';
SELECT '✅ SETUP COMPLETE!' AS Status;
SELECT 'TEST101-A: Mon/Wed 08:00-09:30' AS Section1;
SELECT 'TEST102-A: Mon/Wed 08:00-09:30 (SAME TIME - CONFLICT!)' AS Section2;
