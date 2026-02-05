-- Migration script to add scheduling system
-- Run this in phpMyAdmin or MySQL console

USE student_management_system;

-- ===== TABLE: course_sections =====
-- Stores different sections of courses
CREATE TABLE IF NOT EXISTS course_sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    section_name VARCHAR(50) NOT NULL,
    instructor VARCHAR(100),
    capacity INT DEFAULT 30,
    current_enrollment INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_section (course_id, section_name),
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ===== TABLE: section_schedules =====
-- Stores the day/time schedule for each section
-- One section can have multiple schedule slots (e.g., MWF or TR)
CREATE TABLE IF NOT EXISTS section_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section_id INT NOT NULL,
    day_of_week VARCHAR(1) NOT NULL, -- S=Sunday, M=Monday, T=Tuesday, W=Wednesday, R=Thursday, F=Friday
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (section_id) REFERENCES course_sections(id) ON DELETE CASCADE,
    INDEX idx_section_day (section_id, day_of_week)
) ENGINE=InnoDB;

-- ===== UPDATED: enrollments TABLE =====
-- Add section_id to track which section student enrolled in
-- Check if section_id column exists before adding
ALTER TABLE enrollments ADD COLUMN IF NOT EXISTS section_id INT DEFAULT NULL;
ALTER TABLE enrollments ADD FOREIGN KEY (section_id) REFERENCES course_sections(id) ON DELETE SET NULL;

-- Add index for faster queries
ALTER TABLE enrollments ADD INDEX IF NOT EXISTS idx_student_section (student_id, section_id);

-- ===== View: Schedule Conflicts =====
-- Helper view to identify time slots
DROP VIEW IF EXISTS time_slots;
CREATE VIEW time_slots AS
SELECT 
    'S' AS day_char, 'Sunday' AS day_name
UNION ALL SELECT 'M', 'Monday'
UNION ALL SELECT 'T', 'Tuesday'
UNION ALL SELECT 'W', 'Wednesday'
UNION ALL SELECT 'R', 'Thursday'
UNION ALL SELECT 'F', 'Friday';

-- Test query to verify setup
SELECT 'Migration Complete' AS status;
