-- AI-Powered Career and Pathway Recommendation System
-- Database schema (MySQL)

CREATE DATABASE IF NOT EXISTS career_system CHARACTER SET utf8mb4;
USE career_system;

-- D1: User Accounts
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('student', 'admin') NOT NULL DEFAULT 'student',
    failed_login_attempts INT NOT NULL DEFAULT 0,
    locked_until TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Reference list of CBC pathways the model can predict
CREATE TABLE pathways (
    pathway_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT
);

INSERT INTO pathways (name, description) VALUES
('STEM', 'Science, Technology, Engineering and Mathematics pathway'),
('Social Sciences', 'Humanities, business and social science pathway'),
('Arts and Sports Science', 'Creative arts, performing arts and sports science pathway');

-- D2: Academic & Interest Records
CREATE TABLE academic_records (
    record_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    math_score DECIMAL(5,2) NOT NULL,
    english_score DECIMAL(5,2) NOT NULL,
    science_score DECIMAL(5,2) NOT NULL,
    humanities_score DECIMAL(5,2) NOT NULL,
    creative_arts_score DECIMAL(5,2) NOT NULL,
    interests VARCHAR(255) NOT NULL COMMENT 'comma separated interest tags',
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- D3: Recommendation History
CREATE TABLE recommendations (
    recommendation_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    record_id INT NOT NULL,
    pathway_id INT NOT NULL,
    confidence DECIMAL(5,2) NOT NULL COMMENT 'model confidence as percentage',
    explanation TEXT NOT NULL,
    model_used VARCHAR(50) NOT NULL DEFAULT 'DecisionTree',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (record_id) REFERENCES academic_records(record_id) ON DELETE CASCADE,
    FOREIGN KEY (pathway_id) REFERENCES pathways(pathway_id)
);

CREATE INDEX idx_academic_user ON academic_records(user_id);
CREATE INDEX idx_recommendation_user ON recommendations(user_id);

-- D4: Student feedback on a recommendation's quality — a real signal on
-- whether a recommendation was actually good, independent of the model's
-- own confidence score
CREATE TABLE feedback (
    feedback_id INT AUTO_INCREMENT PRIMARY KEY,
    recommendation_id INT NOT NULL,
    rating TINYINT NOT NULL COMMENT '1 (poor) to 5 (excellent)',
    comments TEXT,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (recommendation_id) REFERENCES recommendations(recommendation_id) ON DELETE CASCADE
);
