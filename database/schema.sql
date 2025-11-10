-- Archery Score Recording Database Schema
-- MariaDB/MySQL Database

-- Create Database
CREATE DATABASE IF NOT EXISTS archery_score_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE archery_score_db;

-- ============================================
-- Core Tables
-- ============================================

-- Archer Table
CREATE TABLE IF NOT EXISTS archer (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    gender ENUM('M', 'F') NOT NULL,
    date_of_birth DATE NOT NULL,
    phone_number VARCHAR(15),
    email VARCHAR(100) UNIQUE,
    address VARCHAR(255),
    equipment ENUM('RECURVE', 'COMPOUND', 'RECURVE_BAREBOW', 'COMPOUND_BAREBOW', 'LONGBOW') DEFAULT 'RECURVE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (last_name, first_name),
    INDEX idx_gender (gender),
    INDEX idx_equipment (equipment),
    INDEX idx_dob (date_of_birth)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Round Table
CREATE TABLE IF NOT EXISTS round (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    round_type VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_round_type (round_type),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Round Range Table
CREATE TABLE IF NOT EXISTS round_range (
    id INT AUTO_INCREMENT PRIMARY KEY,
    round_id INT NOT NULL,
    range_number INT NOT NULL,
    distance_meters INT NOT NULL,
    number_of_ends INT NOT NULL,
    target_face_size_cm INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (round_id) REFERENCES round(id) ON DELETE CASCADE,
    UNIQUE KEY unique_round_range (round_id, range_number),
    INDEX idx_round_id (round_id),
    INDEX idx_distance (distance_meters)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Competition Table
CREATE TABLE IF NOT EXISTS competition (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    competition_date DATE NOT NULL,
    location VARCHAR(150),
    description TEXT,
    is_club_championship BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_date (competition_date),
    INDEX idx_name (name),
    INDEX idx_championship (is_club_championship)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Registration Table
CREATE TABLE IF NOT EXISTS registration (
    id INT AUTO_INCREMENT PRIMARY KEY,
    archer_id INT NOT NULL,
    competition_id INT NOT NULL,
    equipment_used ENUM('RECURVE', 'COMPOUND', 'RECURVE_BAREBOW', 'COMPOUND_BAREBOW', 'LONGBOW'),
    round_id INT,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (archer_id) REFERENCES archer(id) ON DELETE CASCADE,
    FOREIGN KEY (competition_id) REFERENCES competition(id) ON DELETE CASCADE,
    FOREIGN KEY (round_id) REFERENCES round(id),
    UNIQUE KEY unique_registration (archer_id, competition_id),
    INDEX idx_archer_id (archer_id),
    INDEX idx_competition_id (competition_id),
    INDEX idx_round_id (round_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Score Table
CREATE TABLE IF NOT EXISTS score (
    id INT AUTO_INCREMENT PRIMARY KEY,
    archer_id INT NOT NULL,
    registration_id INT,
    round_id INT NOT NULL,
    range_number INT NOT NULL DEFAULT 1,
    end_number INT NOT NULL DEFAULT 1,
    arrow_scores JSON NOT NULL COMMENT 'Array of 6 arrow scores: [10,9,8,7,10,9]',
    end_total INT,
    score_date DATE NOT NULL,
    score_time TIME,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (archer_id) REFERENCES archer(id) ON DELETE CASCADE,
    FOREIGN KEY (registration_id) REFERENCES registration(id) ON DELETE SET NULL,
    FOREIGN KEY (round_id) REFERENCES round(id),
    INDEX idx_archer_id (archer_id),
    INDEX idx_registration_id (registration_id),
    INDEX idx_round_id (round_id),
    INDEX idx_score_date (score_date),
    INDEX idx_end_total (end_total)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Equivalent Round Table
CREATE TABLE IF NOT EXISTS equivalent_round (
    id INT AUTO_INCREMENT PRIMARY KEY,
    base_round_id INT NOT NULL,
    equivalent_round_id INT NOT NULL,
    gender ENUM('M', 'F'),
    equipment ENUM('RECURVE', 'COMPOUND', 'RECURVE_BAREBOW', 'COMPOUND_BAREBOW', 'LONGBOW'),
    age_category VARCHAR(50),
    effective_from DATE NOT NULL,
    effective_to DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (base_round_id) REFERENCES round(id),
    FOREIGN KEY (equivalent_round_id) REFERENCES round(id),
    INDEX idx_effective_dates (effective_from, effective_to),
    INDEX idx_base_round (base_round_id),
    INDEX idx_equivalent_round (equivalent_round_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Sample Data
-- ============================================

-- Insert Sample Rounds
INSERT INTO round (name, round_type, description) VALUES
('WA 900', 'WA90', 'World Archery 900 round - 5 ends at 60m, 50m, and 40m'),
('WA 720', 'WA720', 'World Archery 720 round - 12 ends at 70m'),
('WA 70m', 'WA70', 'World Archery 70m round - 12 ends at 70m'),
('WA 60m', 'WA60', 'World Archery 60m round - 12 ends at 60m'),
('Olympic Round', 'OLYMPIC', 'Olympic standard round - 12 ends at 70m'),
('Field Round', 'FIELD', 'Field archery round'),
('Short Metric I', 'SHORT_METRIC', '6 ends at 50m and 30m'),
('Long Metric', 'LONG_METRIC', '6 ends at 90m, 70m, and 50m'),
('Indoor 18m', 'INDOOR', '10 ends at 18m'),
('Indoor 25m', 'INDOOR', '10 ends at 25m');

-- Insert Round Ranges
-- WA 900 (5 ends at 60m, 50m, 40m)
INSERT INTO round_range (round_id, range_number, distance_meters, number_of_ends, target_face_size_cm) VALUES
(1, 1, 60, 5, 122),
(1, 2, 50, 5, 122),
(1, 3, 40, 5, 122);

-- WA 720 (12 ends at 70m, split into 2 ranges of 6 ends each)
INSERT INTO round_range (round_id, range_number, distance_meters, number_of_ends, target_face_size_cm) VALUES
(2, 1, 70, 6, 122),
(2, 2, 70, 6, 122);

-- WA 70m
INSERT INTO round_range (round_id, range_number, distance_meters, number_of_ends, target_face_size_cm) VALUES
(3, 1, 70, 6, 122),
(3, 2, 70, 6, 122);

-- WA 60m
INSERT INTO round_range (round_id, range_number, distance_meters, number_of_ends, target_face_size_cm) VALUES
(4, 1, 60, 6, 122),
(4, 2, 60, 6, 122);

-- Olympic Round
INSERT INTO round_range (round_id, range_number, distance_meters, number_of_ends, target_face_size_cm) VALUES
(5, 1, 70, 6, 122),
(5, 2, 70, 6, 122);

-- Short Metric I
INSERT INTO round_range (round_id, range_number, distance_meters, number_of_ends, target_face_size_cm) VALUES
(7, 1, 50, 6, 80),
(7, 2, 30, 6, 80);

-- Long Metric
INSERT INTO round_range (round_id, range_number, distance_meters, number_of_ends, target_face_size_cm) VALUES
(8, 1, 90, 6, 122),
(8, 2, 70, 6, 122),
(8, 3, 50, 6, 122);

-- Indoor 18m
INSERT INTO round_range (round_id, range_number, distance_meters, number_of_ends, target_face_size_cm) VALUES
(9, 1, 18, 10, 40);

-- Indoor 25m
INSERT INTO round_range (round_id, range_number, distance_meters, number_of_ends, target_face_size_cm) VALUES
(10, 1, 25, 10, 60);

-- Insert Project Team Members as Archers
INSERT INTO archer (first_name, last_name, gender, date_of_birth, phone_number, email, address, equipment) VALUES
('Aiden', 'Dinh', 'M', '2003-05-15', '0912345678', 'aiden.dinh@student.edu.vn', '123 Le Loi St, District 1, HCMC', 'RECURVE'),
('Thien Anh', 'Doan', 'M', '2002-08-22', '0923456789', 'thien.anh.doan@student.edu.vn', '456 Nguyen Hue St, District 1, HCMC', 'COMPOUND'),
('Dat Phong', 'Luu', 'M', '2003-03-10', '0934567890', 'dat.phong.luu@student.edu.vn', '789 Tran Hung Dao St, District 5, HCMC', 'RECURVE_BAREBOW'),
('Vo', 'Huy', 'M', '2002-11-30', '0945678901', 'vo.huy@student.edu.vn', '321 Hai Ba Trung St, District 3, HCMC', 'COMPOUND'),
('Nguy Do Gia', 'Huy', 'M', '2003-07-18', '0956789012', 'nguy.do.gia.huy@student.edu.vn', '654 Pasteur St, District 1, HCMC', 'RECURVE');

-- Additional Sample Archers
INSERT INTO archer (first_name, last_name, gender, date_of_birth, phone_number, email, address, equipment) VALUES
('Minh', 'Nguyen', 'M', '1995-04-12', '0967890123', 'minh.nguyen@email.vn', '111 Ly Thai To St, Hanoi', 'RECURVE'),
('Thu', 'Tran', 'F', '1998-09-25', '0978901234', 'thu.tran@email.vn', '222 Ba Trieu St, Hanoi', 'COMPOUND'),
('Linh', 'Pham', 'F', '2000-12-08', '0989012345', 'linh.pham@email.vn', '333 Hoang Dieu St, Da Nang', 'RECURVE'),
('Khang', 'Le', 'M', '1997-06-20', '0990123456', 'khang.le@email.vn', '444 Tran Phu St, Da Nang', 'LONGBOW'),
('Anh', 'Hoang', 'F', '2001-02-14', '0901234567', 'anh.hoang@email.vn', '555 Nguyen Trai St, Hanoi', 'RECURVE_BAREBOW');

-- Insert Vietnamese University Competitions
INSERT INTO competition (name, competition_date, location, description, is_club_championship) VALUES
('Swinburne University Open Championship 2025', '2025-03-15', 'Swinburne Campus, Saigon South', 'Annual archery championship for Swinburne students and guests', TRUE),
('FPT University Archery Competition', '2025-04-10', 'FPT University Sports Complex, HCMC', 'Inter-university archery competition hosted by FPT', FALSE),
('HCMUT Spring Tournament', '2025-03-25', 'Ho Chi Minh University of Technology, District 10', 'Spring semester archery tournament', TRUE),
('VNU-HCM Inter-University Cup', '2025-05-05', 'Vietnam National University Sports Center', 'Inter-university competition for VNU system schools', FALSE),
('UEH Archery Challenge', '2025-04-20', 'University of Economics HCMC, District 3', 'Economic university archery challenge', FALSE),
('HCMUS Science Shootout', '2025-06-12', 'HCMC University of Science, Thu Duc', 'Science-themed archery competition', TRUE),
('RMIT Vietnam Archery Open', '2025-05-18', 'RMIT Campus, District 7, HCMC', 'Open competition for RMIT and guests', FALSE),
('Hanoi University Championship', '2025-07-08', 'Hanoi University Sports Ground', 'Northern region university championship', TRUE),
('Da Nang University Games', '2025-08-15', 'Da Nang Sports Complex', 'Central Vietnam university archery games', FALSE),
('Swinburne Club Championship - Fall', '2025-09-20', 'Swinburne Campus, Saigon South', 'Fall semester club championship', TRUE),
('National Inter-University Archery Cup', '2025-10-10', 'National Sports Training Center, Hanoi', 'National level university competition', FALSE),
('HCMC Beginner Practice Session', '2025-02-28', 'Archery Range, District 2', 'Practice session for new archers', FALSE);

-- Insert Sample Registrations
INSERT INTO registration (archer_id, competition_id, equipment_used, round_id) VALUES
-- Swinburne University Open Championship 2025
(1, 1, 'RECURVE', 2),
(2, 1, 'COMPOUND', 2),
(3, 1, 'RECURVE_BAREBOW', 2),
(4, 1, 'COMPOUND', 2),
(5, 1, 'RECURVE', 2),
(6, 1, 'RECURVE', 2),
(7, 1, 'COMPOUND', 2),
(8, 1, 'RECURVE', 2),

-- FPT University Competition
(1, 2, 'RECURVE', 1),
(2, 2, 'COMPOUND', 1),
(3, 2, 'RECURVE_BAREBOW', 1),
(6, 2, 'RECURVE', 1),
(7, 2, 'COMPOUND', 1),

-- HCMUT Spring Tournament
(4, 3, 'COMPOUND', 3),
(5, 3, 'RECURVE', 3),
(8, 3, 'RECURVE', 3),
(9, 3, 'LONGBOW', 3),

-- VNU-HCM Inter-University Cup
(1, 4, 'RECURVE', 2),
(3, 4, 'RECURVE_BAREBOW', 2),
(5, 4, 'RECURVE', 2),
(7, 4, 'COMPOUND', 2),
(10, 4, 'RECURVE_BAREBOW', 2);

-- Insert Sample Scores (End by End)
-- Aiden Dinh at Swinburne Championship (Range 1, 6 ends)
INSERT INTO score (archer_id, registration_id, round_id, range_number, end_number, arrow_scores, end_total, score_date) VALUES
(1, 1, 2, 1, 1, '[10,10,9,9,8,8]', 54, '2025-03-15'),
(1, 1, 2, 1, 2, '[10,9,9,8,8,7]', 51, '2025-03-15'),
(1, 1, 2, 1, 3, '[10,10,10,9,9,8]', 56, '2025-03-15'),
(1, 1, 2, 1, 4, '[9,9,9,8,8,7]', 50, '2025-03-15'),
(1, 1, 2, 1, 5, '[10,10,9,9,8,8]', 54, '2025-03-15'),
(1, 1, 2, 1, 6, '[10,9,9,9,8,7]', 52, '2025-03-15');

-- Aiden Dinh at Swinburne Championship (Range 2, 6 ends)
INSERT INTO score (archer_id, registration_id, round_id, range_number, end_number, arrow_scores, end_total, score_date) VALUES
(1, 1, 2, 2, 1, '[10,10,9,8,8,8]', 53, '2025-03-15'),
(1, 1, 2, 2, 2, '[10,9,9,9,8,8]', 53, '2025-03-15'),
(1, 1, 2, 2, 3, '[10,10,10,9,8,7]', 54, '2025-03-15'),
(1, 1, 2, 2, 4, '[9,9,8,8,8,7]', 49, '2025-03-15'),
(1, 1, 2, 2, 5, '[10,10,9,9,9,8]', 55, '2025-03-15'),
(1, 1, 2, 2, 6, '[10,9,9,8,8,8]', 52, '2025-03-15');

-- Thien Anh Doan at Swinburne Championship (Range 1)
INSERT INTO score (archer_id, registration_id, round_id, range_number, end_number, arrow_scores, end_total, score_date) VALUES
(2, 2, 2, 1, 1, '[10,10,10,9,9,9]', 57, '2025-03-15'),
(2, 2, 2, 1, 2, '[10,10,9,9,9,8]', 55, '2025-03-15'),
(2, 2, 2, 1, 3, '[10,10,10,10,9,9]', 58, '2025-03-15'),
(2, 2, 2, 1, 4, '[10,9,9,9,9,8]', 54, '2025-03-15'),
(2, 2, 2, 1, 5, '[10,10,10,9,9,8]', 56, '2025-03-15'),
(2, 2, 2, 1, 6, '[10,10,9,9,8,8]', 54, '2025-03-15');

-- Thien Anh Doan at Swinburne Championship (Range 2)
INSERT INTO score (archer_id, registration_id, round_id, range_number, end_number, arrow_scores, end_total, score_date) VALUES
(2, 2, 2, 2, 1, '[10,10,10,10,9,9]', 58, '2025-03-15'),
(2, 2, 2, 2, 2, '[10,10,9,9,9,9]', 56, '2025-03-15'),
(2, 2, 2, 2, 3, '[10,10,10,9,9,9]', 57, '2025-03-15'),
(2, 2, 2, 2, 4, '[10,10,10,9,9,8]', 56, '2025-03-15'),
(2, 2, 2, 2, 5, '[10,10,9,9,9,9]', 56, '2025-03-15'),
(2, 2, 2, 2, 6, '[10,10,10,10,9,8]', 57, '2025-03-15');

-- Dat Phong Luu at Swinburne Championship (Range 1)
INSERT INTO score (archer_id, registration_id, round_id, range_number, end_number, arrow_scores, end_total, score_date) VALUES
(3, 3, 2, 1, 1, '[9,9,8,8,7,7]', 48, '2025-03-15'),
(3, 3, 2, 1, 2, '[9,8,8,7,7,6]', 45, '2025-03-15'),
(3, 3, 2, 1, 3, '[10,9,9,8,7,7]', 50, '2025-03-15'),
(3, 3, 2, 1, 4, '[9,9,8,8,7,6]', 47, '2025-03-15'),
(3, 3, 2, 1, 5, '[9,9,8,8,8,7]', 49, '2025-03-15'),
(3, 3, 2, 1, 6, '[10,9,8,8,7,7]', 49, '2025-03-15');

-- Dat Phong Luu at Swinburne Championship (Range 2)
INSERT INTO score (archer_id, registration_id, round_id, range_number, end_number, arrow_scores, end_total, score_date) VALUES
(3, 3, 2, 2, 1, '[9,9,8,8,8,7]', 49, '2025-03-15'),
(3, 3, 2, 2, 2, '[10,9,9,8,7,7]', 50, '2025-03-15'),
(3, 3, 2, 2, 3, '[9,9,8,8,8,7]', 49, '2025-03-15'),
(3, 3, 2, 2, 4, '[9,8,8,8,7,7]', 47, '2025-03-15'),
(3, 3, 2, 2, 5, '[10,9,9,8,8,7]', 51, '2025-03-15'),
(3, 3, 2, 2, 6, '[9,9,8,8,8,8]', 50, '2025-03-15');

-- More sample scores for other archers
INSERT INTO score (archer_id, registration_id, round_id, range_number, end_number, arrow_scores, end_total, score_date) VALUES
(4, 4, 2, 1, 1, '[10,10,9,9,9,8]', 55, '2025-03-15'),
(4, 4, 2, 1, 2, '[10,9,9,9,8,8]', 53, '2025-03-15'),
(4, 4, 2, 1, 3, '[10,10,10,9,9,8]', 56, '2025-03-15'),
(5, 5, 2, 1, 1, '[10,9,9,8,8,7]', 51, '2025-03-15'),
(5, 5, 2, 1, 2, '[10,10,9,9,8,8]', 54, '2025-03-15'),
(5, 5, 2, 1, 3, '[9,9,9,8,8,7]', 50, '2025-03-15');

-- Practice scores (no registration)
INSERT INTO score (archer_id, registration_id, round_id, range_number, end_number, arrow_scores, end_total, score_date, notes) VALUES
(1, NULL, 9, 1, 1, '[10,10,10,9,9,9]', 57, '2025-02-10', 'Indoor practice session'),
(1, NULL, 9, 1, 2, '[10,10,9,9,9,8]', 55, '2025-02-10', 'Indoor practice session'),
(2, NULL, 9, 1, 1, '[10,10,10,10,10,9]', 59, '2025-02-12', 'Personal training'),
(3, NULL, 10, 1, 1, '[9,9,8,8,7,7]', 48, '2025-02-15', 'Technique practice');

-- Database User Setup (Optional - Run separately with root privileges)
-- CREATE USER IF NOT EXISTS 'archery_user'@'localhost' IDENTIFIED BY 'archery_password_2025';
-- GRANT ALL PRIVILEGES ON archery_score_db.* TO 'archery_user'@'localhost';
-- FLUSH PRIVILEGES;

