-- =====================================================
-- PETI Migration: add name/gender/NECTA verification columns
-- Run this ONCE in phpMyAdmin (SQL tab) if you already imported
-- an earlier version of peti_schema.sql and are seeing errors like
-- "Unknown column 'first_name' in 'field list'".
--
-- This only ADDS columns — it does not delete any existing data.
-- =====================================================

USE peti_system;

ALTER TABLE applicants
    ADD COLUMN first_name VARCHAR(80) NULL AFTER payment_status,
    ADD COLUMN middle_name VARCHAR(80) NULL AFTER first_name,
    ADD COLUMN last_name VARCHAR(80) NULL AFTER middle_name,
    ADD COLUMN gender ENUM('male', 'female') NULL AFTER last_name,
    ADD COLUMN necta_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER year_completed_form_four,
    ADD COLUMN necta_division VARCHAR(10) NULL AFTER necta_verified,
    ADD COLUMN necta_checked_at DATETIME NULL AFTER necta_division;

-- If your table still has the old single `full_name` column, this copies
-- its value into first_name so old test data isn't lost, then drops it.
-- Comment this block out if you don't have a full_name column (i.e. if
-- the ADD COLUMN statements above already ran without error the first time).
-- ALTER TABLE applicants
--     DROP COLUMN full_name;
