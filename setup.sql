-- ==============================================================================
-- Library Management System - Database Schema & Seed Data
-- ==============================================================================
-- 
-- DATABASE NAME: library_db
-- 
-- SCHEMA OVERVIEW:
-- 1. books: The central catalogue of physical inventory.
-- 2. borrowers: Registered members allowed to check out books.
-- 3. loans: Transactional table linking books and borrowers (Circulation).
-- 
-- CONSTRAINTS:
-- - Foreign keys ensure that you cannot delete a book or borrower if they
--   are referenced in an active loan record.
-- ==============================================================================

-- CREATE DATABASE IF NOT EXISTS library_db;
-- USE library_db;

-- ── 1. BOOKS TABLE ───────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS books (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  author VARCHAR(255) NOT NULL,
  isbn VARCHAR(20),
  genre VARCHAR(100),
  year INT,
  copies INT DEFAULT 1,
  coverUrl VARCHAR(255),
  createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── 2. BORROWERS TABLE ───────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS borrowers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) UNIQUE NOT NULL,
  phone VARCHAR(20),
  memberSince DATE DEFAULT (CURRENT_DATE),
  status ENUM('active', 'suspended') DEFAULT 'active',
  createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── 3. LOANS TABLE (Transactions) ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS loans (
  id INT AUTO_INCREMENT PRIMARY KEY,
  bookId INT NOT NULL,
  borrowerId INT NOT NULL,
  borrowedDate DATE NOT NULL,
  dueDate DATE NOT NULL,
  returnedDate DATE,
  status ENUM('active', 'returned', 'overdue') DEFAULT 'active',
  createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  -- Ensure records are linked to existing entities
  FOREIGN KEY (bookId) REFERENCES books(id) ON DELETE RESTRICT,
  FOREIGN KEY (borrowerId) REFERENCES borrowers(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ── 4. SEED DATA (Aura of Excellence) ─────────────────────────────────────────

-- Sample Books
INSERT INTO books (title, author, isbn, genre, year, copies, coverUrl) VALUES 
('The Great Gatsby', 'F. Scott Fitzgerald', '978-0743273565', 'Classic', 1925, 3, 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1490528560i/4671.jpg'),
('1984', 'George Orwell', '978-0451524935', 'Dystopian', 1949, 5, 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1348927776i/15460912.jpg'),
('The Hobbit', 'J.R.R. Tolkien', '978-0547928227', 'Fantasy', 1937, 2, 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1546071216i/5907.jpg'),
('Educated', 'Tara Westover', '978-0399590504', 'Memoir', 2018, 4, 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1506046619i/35133922.jpg');

-- Sample Borrowers
INSERT INTO borrowers (name, email, phone) VALUES 
('Alice Freeman', 'alice@example.com', '555-0101'),
('Robert Vance', 'robert@example.com', '555-0102');

-- Sample Loan (Active)
-- Note: Manually setting IDs here assuming they start at 1
INSERT INTO loans (bookId, borrowerId, borrowedDate, dueDate, status) VALUES 
(1, 1, '2023-10-01', '2023-10-15', 'active');
