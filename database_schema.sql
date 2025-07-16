-- Jaipur Metro Complaint Portal Database Schema
CREATE DATABASE IF NOT EXISTS jaipur_metro_complaints;
USE jaipur_metro_complaints;

-- Users table for storing user registration information
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(15),
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Complaints table for storing all complaints
CREATE TABLE IF NOT EXISTS complaints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    complaint_type ENUM('Train Delay', 'Cleanliness Issue', 'Staff Misbehavior', 'Technical Glitch', 'Safety Issue', 'Ticketing Issue', 'Others') NOT NULL,
    description TEXT NOT NULL,
    image_path VARCHAR(255),
    status ENUM('Pending', 'In Progress', 'Resolved', 'Rejected') DEFAULT 'Pending',
    admin_response TEXT,
    priority ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium',
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert default admin user
INSERT INTO users (full_name, email, password, is_admin) VALUES 
('Admin User', 'admin@jaipurmetro.com', '$2y$12$BDTPnUQgDBUAuvNrv.NXxOf7K4bZGCBUlJOcR0/XgWrIXbbpIuHDG', TRUE);
-- Default password is 'password' - change after first login

-- Create uploads directory structure
-- Note: This should be created in the file system as well