-- Delete all existing data
DELETE FROM enrollments;
DELETE FROM section_schedules;
DELETE FROM course_sections;
DELETE FROM courses;

-- Insert new courses
INSERT INTO courses (course_code, course_name, credits, max_students) VALUES
('CS101', 'Introduction to Programming', 3, 100),
('CS102', 'Data Structures', 3, 80),
('CS201', 'Web Development', 4, 60),
('MATH101', 'Calculus I', 4, 120),
('MATH102', 'Linear Algebra', 3, 100),
('ENG101', 'English Composition', 3, 50);

-- Get course IDs
SET @cs101 = (SELECT id FROM courses WHERE course_code = 'CS101');
SET @cs102 = (SELECT id FROM courses WHERE course_code = 'CS102');
SET @cs201 = (SELECT id FROM courses WHERE course_code = 'CS201');
SET @math101 = (SELECT id FROM courses WHERE course_code = 'MATH101');
SET @math102 = (SELECT id FROM courses WHERE course_code = 'MATH102');
SET @eng101 = (SELECT id FROM courses WHERE course_code = 'ENG101');

-- ADD SECTIONS FOR CS101
INSERT INTO course_sections (course_id, section_name, instructor, capacity) VALUES
(@cs101, 'A', 'Dr. Smith', 30),
(@cs101, 'B', 'Dr. Johnson', 30),
(@cs101, 'C', 'Dr. Williams', 40);

-- ADD SECTIONS FOR CS102
INSERT INTO course_sections (course_id, section_name, instructor, capacity) VALUES
(@cs102, 'A', 'Dr. Brown', 25),
(@cs102, 'B', 'Dr. Davis', 25);

-- ADD SECTIONS FOR CS201
INSERT INTO course_sections (course_id, section_name, instructor, capacity) VALUES
(@cs201, 'A', 'Dr. Miller', 30),
(@cs201, 'B', 'Dr. Wilson', 30);

-- ADD SECTIONS FOR MATH101
INSERT INTO course_sections (course_id, section_name, instructor, capacity) VALUES
(@math101, 'A', 'Dr. Lee', 40),
(@math101, 'B', 'Dr. Park', 40),
(@math101, 'C', 'Dr. Chen', 40);

-- ADD SECTIONS FOR MATH102
INSERT INTO course_sections (course_id, section_name, instructor, capacity) VALUES
(@math102, 'A', 'Dr. Martinez', 35),
(@math102, 'B', 'Dr. Garcia', 35);

-- ADD SECTIONS FOR ENG101
INSERT INTO course_sections (course_id, section_name, instructor, capacity) VALUES
(@eng101, 'A', 'Dr. Taylor', 25),
(@eng101, 'B', 'Dr. Anderson', 25);

-- Get section IDs and add schedules
-- CS101 SECTIONS
SET @cs101a = (SELECT id FROM course_sections WHERE course_id = @cs101 AND section_name = 'A');
SET @cs101b = (SELECT id FROM course_sections WHERE course_id = @cs101 AND section_name = 'B');
SET @cs101c = (SELECT id FROM course_sections WHERE course_id = @cs101 AND section_name = 'C');

INSERT INTO section_schedules (section_id, day_of_week, start_time, end_time) VALUES
(@cs101a, 'M', '09:00', '10:30'),
(@cs101a, 'W', '09:00', '10:30'),
(@cs101a, 'F', '09:00', '09:50'),
(@cs101b, 'T', '10:00', '11:30'),
(@cs101b, 'R', '10:00', '11:30'),
(@cs101c, 'M', '13:00', '14:30'),
(@cs101c, 'W', '13:00', '14:30');

-- CS102 SECTIONS
SET @cs102a = (SELECT id FROM course_sections WHERE course_id = @cs102 AND section_name = 'A');
SET @cs102b = (SELECT id FROM course_sections WHERE course_id = @cs102 AND section_name = 'B');

INSERT INTO section_schedules (section_id, day_of_week, start_time, end_time) VALUES
(@cs102a, 'M', '11:00', '12:30'),
(@cs102a, 'W', '11:00', '12:30'),
(@cs102b, 'T', '13:00', '14:30'),
(@cs102b, 'R', '13:00', '14:30');

-- CS201 SECTIONS
SET @cs201a = (SELECT id FROM course_sections WHERE course_id = @cs201 AND section_name = 'A');
SET @cs201b = (SELECT id FROM course_sections WHERE course_id = @cs201 AND section_name = 'B');

INSERT INTO section_schedules (section_id, day_of_week, start_time, end_time) VALUES
(@cs201a, 'M', '14:00', '15:30'),
(@cs201a, 'W', '14:00', '15:30'),
(@cs201a, 'F', '14:00', '14:50'),
(@cs201b, 'T', '14:00', '15:30'),
(@cs201b, 'R', '14:00', '15:30');

-- MATH101 SECTIONS
SET @math101a = (SELECT id FROM course_sections WHERE course_id = @math101 AND section_name = 'A');
SET @math101b = (SELECT id FROM course_sections WHERE course_id = @math101 AND section_name = 'B');
SET @math101c = (SELECT id FROM course_sections WHERE course_id = @math101 AND section_name = 'C');

INSERT INTO section_schedules (section_id, day_of_week, start_time, end_time) VALUES
(@math101a, 'M', '10:00', '11:30'),
(@math101a, 'W', '10:00', '11:30'),
(@math101b, 'T', '11:00', '12:30'),
(@math101b, 'R', '11:00', '12:30'),
(@math101c, 'M', '15:00', '16:30'),
(@math101c, 'W', '15:00', '16:30');

-- MATH102 SECTIONS
SET @math102a = (SELECT id FROM course_sections WHERE course_id = @math102 AND section_name = 'A');
SET @math102b = (SELECT id FROM course_sections WHERE course_id = @math102 AND section_name = 'B');

INSERT INTO section_schedules (section_id, day_of_week, start_time, end_time) VALUES
(@math102a, 'T', '09:00', '10:30'),
(@math102a, 'R', '09:00', '10:30'),
(@math102b, 'M', '11:00', '12:30'),
(@math102b, 'W', '11:00', '12:30');

-- ENG101 SECTIONS
SET @eng101a = (SELECT id FROM course_sections WHERE course_id = @eng101 AND section_name = 'A');
SET @eng101b = (SELECT id FROM course_sections WHERE course_id = @eng101 AND section_name = 'B');

INSERT INTO section_schedules (section_id, day_of_week, start_time, end_time) VALUES
(@eng101a, 'T', '08:00', '09:30'),
(@eng101a, 'R', '08:00', '09:30'),
(@eng101b, 'M', '12:00', '13:30'),
(@eng101b, 'W', '12:00', '13:30');

-- DISPLAY RESULTS
SELECT '===========================================' AS Status;
SELECT '✅ DATABASE UPDATED SUCCESSFULLY!' AS Result;
SELECT '===========================================' AS Status;
SELECT '' AS '';
SELECT 'COURSES AND SECTIONS WITH SCHEDULES:' AS Info;
SELECT '' AS '';

SELECT 
    c.course_code,
    c.course_name,
    cs.section_name,
    cs.instructor,
    cs.capacity,
    GROUP_CONCAT(CONCAT(ss.day_of_week, ' ', ss.start_time, '-', ss.end_time) SEPARATOR ', ') AS Schedule
FROM courses c
LEFT JOIN course_sections cs ON c.id = cs.course_id
LEFT JOIN section_schedules ss ON cs.id = ss.section_id
GROUP BY c.id, cs.id
ORDER BY c.course_code, cs.section_name;
