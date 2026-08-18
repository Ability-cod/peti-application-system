-- =====================================================
-- PETI (Perfect Education and Training Institute)
-- COMPLETE FINAL DATABASE SCHEMA
--
-- Use this ONE file for a brand-new hosting database instead of
-- piecing together the schema + migration files from chat history.
-- It creates every table in its final, correct form (as of the
-- current working system), including all changes made along the way:
-- three separate name fields + gender + marital status, NECTA
-- format-only fields, certificate upload, single guardian table,
-- Tsh 10,000 fee, payment proof fields (payer phone/network/
-- transaction ID), payment_settings, and message attachments.
--
-- Run this ONCE on a fresh database. Do not run it against your
-- existing XAMPP database — it already has this schema from the
-- individual pieces you applied over time.
--
-- After running this, still run install/seed.php once in the
-- browser to create the default admin/principal accounts (passwords
-- must be hashed by PHP, not written directly in SQL).
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
-- manually by admin. Only one should be 'open' at a time.
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
    marital_status ENUM('married', 'not_married') NULL,
    year_completed_form_four VARCHAR(10) NULL,

    -- NECTA index number format-check audit trail (no live verification)
    necta_verified TINYINT(1) NOT NULL DEFAULT 0,
    necta_division VARCHAR(10) NULL,
    necta_checked_at DATETIME NULL,

    -- Full application (filled after registration, before payment)
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

    -- Form Four certificate upload (served only via serve_certificate.php)
    certificate_path VARCHAR(255) NULL,

    -- Application workflow
    profile_status ENUM('not_submitted', 'submitted') NOT NULL DEFAULT 'not_submitted',
    review_status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    reviewed_by INT NULL,
    reviewed_at DATETIME NULL,

    -- Course selection (after payment confirmed)
    course_id INT NULL,
    course_selected_at DATETIME NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (window_id) REFERENCES admission_windows(id),
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- Guardian / Mdhamini (single record per applicant)
-- ---------------------------------------------------
CREATE TABLE guardian (
    id INT AUTO_INCREMENT PRIMARY KEY,
    applicant_id INT NOT NULL UNIQUE,
    full_name VARCHAR(150) NOT NULL,
    address VARCHAR(255) NOT NULL,
    contact VARCHAR(100) NOT NULL,
    FOREIGN KEY (applicant_id) REFERENCES applicants(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- Payments (Tsh 10,000 application fee)
-- control_number is generated internally for uniqueness but is not
-- shown to applicants anymore; matching relies on payer-submitted
-- transaction_id / payer_phone / payer_network instead.
-- ---------------------------------------------------
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    applicant_id INT NOT NULL,
    control_number VARCHAR(30) NOT NULL UNIQUE,
    amount DECIMAL(10,2) NOT NULL DEFAULT 10000.00,
    payer_phone VARCHAR(20) NULL,
    payer_network VARCHAR(30) NULL,
    transaction_id VARCHAR(50) NULL,
    status ENUM('pending', 'paid') NOT NULL DEFAULT 'pending',
    channel VARCHAR(50) NULL COMMENT 'e.g. M-Pesa, Tigo Pesa/Mixx by Yas, Airtel Money, HaloPesa',
    confirmed_by INT NULL,
    confirmed_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (applicant_id) REFERENCES applicants(id) ON DELETE CASCADE,
    FOREIGN KEY (confirmed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- Payment settings (single row, id=1) — the phone number
-- applicants are told to pay to, managed via admin/payment_settings.php
-- ---------------------------------------------------
CREATE TABLE payment_settings (
    id INT PRIMARY KEY DEFAULT 1,
    network_name VARCHAR(100) NULL,
    lipa_number VARCHAR(50) NOT NULL DEFAULT '',
    instructions TEXT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- Messages sent from staff to applicants (auto-sent on
-- approve/reject, plus manual extra messages)
-- ---------------------------------------------------
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    applicant_id INT NOT NULL,
    sender_id INT NOT NULL,
    subject VARCHAR(200) NOT NULL,
    body TEXT NOT NULL,
    attachment_path VARCHAR(255) NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (applicant_id) REFERENCES applicants(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- Seed: Courses offered at PETI (10 official Advanced Certificate
-- courses with real CNO numbers, plus 3 additional courses)
-- ---------------------------------------------------
INSERT INTO courses (name, code, description) VALUES
('Advanced Certificate in International Hotels Management', 'ACIHM', 'CNO 309 — 2-year Advanced Certificate, Tsh 850,000'),
('Advanced Certificate in International Tourism and Airlines Management', 'ACITAM', 'CNO 310 — 2-year Advanced Certificate, Tsh 850,000'),
('Advanced Certificate in Front and Reception Management', 'ACHIFOR', 'CNO 311 — 2-year Advanced Certificate, Tsh 850,000'),
('Advanced Certificate in Food & Beverage Services Management', 'ACFBSM', 'CNO 312 — 2-year Advanced Certificate, Tsh 850,000'),
('Advanced Certificate in Tour Guiding and Administration', 'ACTGA', 'CNO 313 — 2-year Advanced Certificate, Tsh 850,000'),
('Advanced Certificate in Hotel Hospitality & Housekeeping Management', 'ACHHHM', 'CNO 317 — 2-year Advanced Certificate, Tsh 850,000'),
('Advanced Certificate in Hotel & Tourism and Financial Management', 'ACHTFM', 'CNO 38 — 2-year Advanced Certificate, Tsh 850,000'),
('Advanced Certificate in Journalism', 'ACJ', 'CNO 319 — 2-year Advanced Certificate, Tsh 850,000'),
('Advanced Certificate in Full Secretarial Course', 'ACFS', 'CNO 320 — 2-year Advanced Certificate, Tsh 850,000'),
('Advanced Certificate in Nursery School Teaching', 'ACNST', 'CNO 321 — 2-year Advanced Certificate, Tsh 850,000'),
('Event Decoration', 'DECO', 'Certificate course in event and venue decoration'),
('Tailoring and Fashion Design', 'TAIL', 'Certificate course in tailoring and fashion design'),
('Computer Studies', 'COMP', 'Certificate course in computer studies and ICT skills');

-- ---------------------------------------------------
-- Seed: One open admission window to start with
-- ---------------------------------------------------
INSERT INTO admission_windows (name, status, opened_at) VALUES
('2026 Intake - Round 1', 'open', NOW());

-- ---------------------------------------------------
-- Seed: Payment settings row — UPDATE the phone number below to
-- your real one before (or right after) going live.
-- ---------------------------------------------------
INSERT INTO payment_settings (id, lipa_number, instructions) VALUES
(1, '0755748329', 'Tigo Pesa, Airtel Money, and HaloPesa users can also send directly to this number using their own network''s cross-network transfer option.');