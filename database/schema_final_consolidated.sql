-- =====================================================
-- PETI (Perfect Education and Training Institute)
-- Online Application System - Database Schema
-- =====================================================

CREATE DATABASE IF NOT EXISTS peti_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE peti_system;

-- ---------------------------------------------------
-- Staff users: Admin and Principal (Mkuu wa Chuo)
-- ---------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    role ENUM('admin', 'principal') NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- Courses offered by PETI
-- ---------------------------------------------------
CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    code VARCHAR(20) NOT NULL UNIQUE,
    description TEXT,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- Admission windows (application rounds), opened/closed
-- manually by admin
-- ---------------------------------------------------
CREATE TABLE admission_windows (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    status ENUM('open', 'closed') NOT NULL DEFAULT 'closed',
    opened_at DATETIME NULL,
    closed_at DATETIME NULL,
    created_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- Applicants (Form Four graduates)
-- Login username = form_four_index_number
-- ---------------------------------------------------
CREATE TABLE applicants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    form_four_index_number VARCHAR(30) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    window_id INT NOT NULL,

    -- Payment
    payment_status ENUM('pending', 'paid') NOT NULL DEFAULT 'pending',

    -- Collected at registration
    first_name VARCHAR(80) NULL,
    middle_name VARCHAR(80) NULL,
    last_name VARCHAR(80) NULL,
    gender ENUM('male', 'female') NULL,
    year_completed_form_four VARCHAR(10) NULL,

    -- NECTA verification audit trail (best-effort check done at registration)
    necta_verified TINYINT(1) NOT NULL DEFAULT 0,
    necta_division VARCHAR(10) NULL,
    necta_checked_at DATETIME NULL,

    -- Full application (filled after payment confirmed)
    email VARCHAR(150) NULL,
    phone VARCHAR(20) NULL,
    nationality VARCHAR(80) NULL,

    -- Residential address
    residence_region VARCHAR(80) NULL,
    residence_district VARCHAR(80) NULL,
    residence_ward VARCHAR(80) NULL,
    residence_street VARCHAR(80) NULL,
    postal_address VARCHAR(150) NULL,

    -- Birth details
    date_of_birth DATE NULL,
    birth_region VARCHAR(80) NULL,
    birth_district VARCHAR(80) NULL,
    birth_ward VARCHAR(80) NULL,
    birth_village_street VARCHAR(80) NULL,

    -- Application workflow
    profile_status ENUM('not_submitted', 'submitted') NOT NULL DEFAULT 'not_submitted',
    review_status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    reviewed_by INT NULL,
    reviewed_at DATETIME NULL,

    -- Course selection (after approval)
    course_id INT NULL,
    course_selected_at DATETIME NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (window_id) REFERENCES admission_windows(id),
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- Parents / Guardians (father and mother)
-- ---------------------------------------------------
CREATE TABLE parents_guardians (
    id INT AUTO_INCREMENT PRIMARY KEY,
    applicant_id INT NOT NULL,
    relation ENUM('father', 'mother') NOT NULL,
    full_name VARCHAR(150) NULL,
    phone VARCHAR(20) NULL,
    email VARCHAR(150) NULL,
    occupation VARCHAR(100) NULL,
    region VARCHAR(80) NULL,
    district VARCHAR(80) NULL,
    ward VARCHAR(80) NULL,
    village_street VARCHAR(80) NULL,
    postal_address VARCHAR(150) NULL,

    FOREIGN KEY (applicant_id) REFERENCES applicants(id) ON DELETE CASCADE,
    UNIQUE KEY unique_relation_per_applicant (applicant_id, relation)
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- Payments (control numbers)
-- ---------------------------------------------------
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    applicant_id INT NOT NULL,
    control_number VARCHAR(30) NOT NULL UNIQUE,
    amount DECIMAL(10,2) NOT NULL DEFAULT 5000.00,
    status ENUM('pending', 'paid') NOT NULL DEFAULT 'pending',
    channel VARCHAR(50) NULL COMMENT 'e.g. CRDB, M-Pesa, HaloPesa, Mixx by Yas, Airtel Money',
    confirmed_by INT NULL,
    confirmed_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (applicant_id) REFERENCES applicants(id) ON DELETE CASCADE,
    FOREIGN KEY (confirmed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- Messages sent from staff to applicants
-- ---------------------------------------------------
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    applicant_id INT NOT NULL,
    sender_id INT NOT NULL,
    subject VARCHAR(200) NOT NULL,
    body TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (applicant_id) REFERENCES applicants(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- Seed: Courses offered at PETI
-- ---------------------------------------------------
INSERT INTO courses (name, code, description) VALUES
('Journalism', 'JOUR', 'Diploma/Certificate in Journalism and Mass Communication'),
('Wildlife and National Parks Management', 'WNPM', 'Management of wildlife and national parks / game reserves'),
('Hotel and Tourism Management', 'HTM', 'Management of hotels and tourism establishments'),
('Pre-Primary (Early Childhood) Teaching', 'PPT', 'Teaching methodology for pre-primary/early childhood education'),
('German Language', 'GER', 'Certificate course in the German language'),
('Esperanto Language', 'ESP', 'Certificate course in the Esperanto language');

-- ---------------------------------------------------
-- Seed: One open admission window to start with
-- ---------------------------------------------------
INSERT INTO admission_windows (name, status, opened_at) VALUES
('2026 Intake - Round 1', 'open', NOW());

-- NOTE: Default admin and principal accounts are created by
-- running install/seed.php in the browser ONCE after importing
-- this schema (so passwords are hashed correctly by PHP).
--
-- NOTE: A ready-to-test applicant (payment already marked PAID) can be
-- created by running install/seed_test_applicant.php in the browser.
