-- Archery Score Recording System - Database Migration
-- This file contains all necessary tables and setup

USE archery_score_db;

-- =============================================
-- EXISTING TABLES (from Query.md) - CREATE FIRST
-- =============================================

-- Archer Table (created FIRST - referenced by users table)
CREATE TABLE IF NOT EXISTS archer (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    gender ENUM('M', 'F') NOT NULL,
    date_of_birth DATE NOT NULL,
    phone_number VARCHAR(15),
    email VARCHAR(100) UNIQUE,
    equipment ENUM('RECURVE', 'COMPOUND', 'RECURVE_BAREBOW', 'COMPOUND_BAREBOW', 'LONGBOW') DEFAULT 'RECURVE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (last_name, first_name),
    INDEX idx_gender (gender),
    INDEX idx_equipment (equipment),
    INDEX idx_dob (date_of_birth)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Manager/Recorder table (created SECOND - referenced by users table)
CREATE TABLE IF NOT EXISTS manager (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE,
    phone_number VARCHAR(15),
    permissions JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (last_name, first_name),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- USER/AUTHENTICATION TABLES - CREATE AFTER ARCHER/MANAGER
-- =============================================

-- Users table (for authentication - both archers and managers)
-- Created AFTER archer and manager tables so foreign keys work
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    role ENUM('archer', 'manager') NOT NULL DEFAULT 'archer',
    archer_id INT UNIQUE,
    manager_id INT UNIQUE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    FOREIGN KEY (archer_id) REFERENCES archer(id) ON DELETE SET NULL,
    FOREIGN KEY (manager_id) REFERENCES manager(id) ON DELETE SET NULL,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sessions table (for tracking active sessions)
CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id INT NOT NULL,
    user_role ENUM('archer', 'manager') NOT NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
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
    status ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (archer_id) REFERENCES archer(id) ON DELETE CASCADE,
    FOREIGN KEY (competition_id) REFERENCES competition(id) ON DELETE CASCADE,
    FOREIGN KEY (round_id) REFERENCES round(id),
    UNIQUE KEY unique_registration (archer_id, competition_id),
    INDEX idx_archer_id (archer_id),
    INDEX idx_competition_id (competition_id),
    INDEX idx_round_id (round_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Score Table
CREATE TABLE IF NOT EXISTS score (
    id INT AUTO_INCREMENT PRIMARY KEY,
    archer_id INT NOT NULL,
    registration_id INT,
    round_id INT NOT NULL,
    arrow_scores JSON NOT NULL COMMENT 'Array of 6 arrow scores: [10,9,8,7,10,9]',
    total_points INT,
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
    INDEX idx_total_points (total_points)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Category Table (for classification)
CREATE TABLE IF NOT EXISTS category (
    id INT AUTO_INCREMENT PRIMARY KEY,
    age_group VARCHAR(20) NOT NULL,
    gender ENUM('M', 'F') NOT NULL,
    equipment ENUM('RECURVE', 'COMPOUND', 'RECURVE_BAREBOW', 'COMPOUND_BAREBOW', 'LONGBOW') NOT NULL,
    description VARCHAR(100),
    UNIQUE KEY unique_category (age_group, gender, equipment),
    INDEX idx_age_group (age_group)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- SAMPLE DATA
-- =============================================

-- Insert sample categories
INSERT INTO category (age_group, gender, equipment, description) VALUES
('U14', 'M', 'RECURVE', 'Under 14 Male Recurve'),
('U14', 'F', 'RECURVE', 'Under 14 Female Recurve'),
('U16', 'M', 'RECURVE', 'Under 16 Male Recurve'),
('U16', 'F', 'RECURVE', 'Under 16 Female Recurve'),
('U18', 'M', 'RECURVE', 'Under 18 Male Recurve'),
('U18', 'F', 'RECURVE', 'Under 18 Female Recurve'),
('U21', 'M', 'RECURVE', 'Under 21 Male Recurve'),
('U21', 'F', 'RECURVE', 'Under 21 Female Recurve'),
('Open', 'M', 'RECURVE', 'Open Male Recurve'),
('Open', 'F', 'RECURVE', 'Open Female Recurve'),
('50+', 'M', 'RECURVE', '50+ Male Recurve'),
('50+', 'F', 'RECURVE', '50+ Female Recurve'),
('60+', 'M', 'RECURVE', '60+ Male Recurve'),
('60+', 'F', 'RECURVE', '60+ Female Recurve'),
('70+', 'M', 'RECURVE', '70+ Male Recurve'),
('70+', 'F', 'RECURVE', '70+ Female Recurve') ON DUPLICATE KEY UPDATE id=id;

-- Insert sample archers
INSERT INTO archer (first_name, last_name, gender, date_of_birth, phone_number, email, equipment) VALUES
('Aiden', 'Dinh', 'M', '1998-03-15', '0412345678', 'aiden.dinh@archery.com', 'RECURVE'),
('Thien Anh', 'Doan', 'M', '1997-06-22', '0487654321', 'thienanh.doan@archery.com', 'COMPOUND'),
('Dat Phong', 'Luu', 'M', '1996-09-10', '0498765432', 'datphong.luu@archery.com', 'RECURVE'),
('Vo', 'Huy', 'M', '1999-01-05', '0456789123', 'vo.huy@archery.com', 'RECURVE_BAREBOW'),
('Nguy Do Gia', 'Huy', 'M', '1995-11-20', '0423456789', 'nguy.huy@archery.com', 'COMPOUND'),
('Wilson', 'Doan', 'M', '1994-07-30', '0434567890', 'wilson.doan@archery.com', 'RECURVE') ON DUPLICATE KEY UPDATE id=id;

-- Insert sample rounds
INSERT INTO round (name, round_type, description) VALUES
('WA 900', 'WA90', 'World Archery 900 round'),
('WA 720', 'WA720', 'World Archery 720 round'),
('WA 70m', 'WA70', 'World Archery 70m round'),
('Olympic Round', 'OLYMPIC', 'Olympic standard round') ON DUPLICATE KEY UPDATE id=id;

-- =============================================
-- INDEXES FOR PERFORMANCE
-- =============================================

-- Additional indexes for auth performance
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_archer_id (archer_id);
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_manager_id (manager_id);