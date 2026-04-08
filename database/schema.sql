
-- PetFounds Database Schema

CREATE DATABASE IF NOT EXISTS petfounds_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE petfounds_db;

-- USERS TABLE
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    avatar_url VARCHAR(255) DEFAULT 'https://i.pravatar.cc/150?img=68',
    bio TEXT,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email)
);

-- PET REPORTS TABLE
CREATE TABLE IF NOT EXISTS pet_reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type ENUM('lost', 'found') NOT NULL,
    pet_name VARCHAR(100),
    species VARCHAR(50) NOT NULL,
    species_detail VARCHAR(100) NULL,
    location VARCHAR(255) NOT NULL,
    event_date DATE NULL,
    description TEXT NOT NULL,
    image_url VARCHAR(255),
    likes_count INT DEFAULT 0,
    status ENUM('active', 'resolved') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_type (type),
    INDEX idx_created_at (created_at),
    INDEX idx_event_date (event_date)
);

-- LIKES TABLE
CREATE TABLE IF NOT EXISTS likes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    report_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (report_id) REFERENCES pet_reports(id) ON DELETE CASCADE,
    UNIQUE KEY unique_like (user_id, report_id)
);

-- MESSAGES TABLE
CREATE TABLE IF NOT EXISTS messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    report_id INT,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (report_id) REFERENCES pet_reports(id) ON DELETE SET NULL,
    INDEX idx_sender (sender_id),
    INDEX idx_receiver (receiver_id),
    INDEX idx_created_at (created_at)
);

-- CONTACTS TABLE (Chat Contacts)
CREATE TABLE IF NOT EXISTS chat_contacts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user1_id INT NOT NULL,
    user2_id INT NOT NULL,
    last_message_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user1_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (user2_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_contact (user1_id, user2_id)
);

-- DUMMY DATA
INSERT INTO users (name, email, password, avatar_url, bio) VALUES
('Admin PetFounds', 'admin@petfounds.pro', SHA2('admin123', 256), 'https://i.pravatar.cc/150?img=68', 'Admin Platform PetFounds'),
('Alex Turner', 'alex@example.com', SHA2('password123', 256), 'https://i.pravatar.cc/150?img=11', 'Pecinta kucing dan anjing'),
('Sarena Design', 'sarena@example.com', SHA2('password123', 256), 'https://i.pravatar.cc/150?img=33', 'Penggemar hewan peliharaan');

INSERT INTO pet_reports (user_id, type, pet_name, species, location, description, image_url) VALUES
(2, 'lost', 'Milo si Persia', 'Kucing', 'Borobudur, Magelang', 'Hilang kucing persia medium abu-abu. Kalung merah lonceng perak. Terakhir terlihat melompat keluar dari area homestay.', 'https://images.unsplash.com/photo-1513360371669-4adf3dd7dff8?auto=format&fit=crop&q=80&w=600'),
(3, 'found', 'Unknown', 'Anjing', 'Alun-Alun Magelang', 'Ditemukan anjing Golden Retriever berkeliaran di dekat patung. Sangat jinak. Saat ini amankan di ruko. Hubungi jika ini milik Anda.', 'https://images.unsplash.com/photo-1552053831-71594a27632d?auto=format&fit=crop&q=80&w=600');

