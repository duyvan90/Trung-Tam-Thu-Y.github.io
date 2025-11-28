-- Add 'waiting' status to bookings table enum
-- Run this SQL in phpMyAdmin to update the enum

USE petcare_db;

-- Update the enum to include 'waiting' status
ALTER TABLE bookings 
MODIFY COLUMN status ENUM('pending', 'confirmed', 'waiting', 'completed', 'cancelled') 
DEFAULT 'pending';

-- Verify the change
DESCRIBE bookings;

