-- Database initialization script for MySQL
-- Create the database if it doesn't exist
CREATE DATABASE IF NOT EXISTS library_db;
USE library_db;

-- 1. Table for Books
CREATE TABLE IF NOT EXISTS books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    isbn VARCHAR(50),
    genre VARCHAR(100),
    year INT,
    copies INT DEFAULT 1,
    available INT DEFAULT 1,
    INDEX (title),
    INDEX (author)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Table for Borrowers
CREATE TABLE IF NOT EXISTS borrowers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(50),
    memberSince DATE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    INDEX (name),
    INDEX (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Table for Loans
CREATE TABLE IF NOT EXISTS loans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bookId INT NOT NULL,
    borrowerId INT NOT NULL,
    borrowedDate DATE,
    dueDate DATE,
    returnedDate DATE NULL,
    status ENUM('active', 'overdue', 'returned') DEFAULT 'active',
    FOREIGN KEY (bookId) REFERENCES books(id) ON DELETE RESTRICT,
    FOREIGN KEY (borrowerId) REFERENCES borrowers(id) ON DELETE RESTRICT,
    INDEX (status),
    INDEX (dueDate)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Sample Data
INSERT INTO books (title, author, isbn, genre, year, copies, available) VALUES
('Clean Code', 'Robert C. Martin', '978-0132350884', 'Programming', 2008, 5, 5),
('Refactoring', 'Martin Fowler', '978-0201485677', 'Programming', 1999, 3, 3),
('The Great Gatsby', 'F. Scott Fitzgerald', '978-0743273565', 'Fiction', 1925, 2, 2),
('Don Quixote', 'Miguel de Cervantes', '978-0060934347', 'Classic', 1605, 1, 1),
('1984', 'George Orwell', '978-0451524935', 'Dystopian', 1949, 4, 4);

INSERT INTO borrowers (name, email, phone, memberSince, status) VALUES
('Alice Johnson', 'alice@example.com', '123-456-7890', '2023-01-15', 'active'),
('Bob Smith', 'bob@example.com', '098-765-4321', '2023-02-20', 'active'),
('Charlie Brown', 'charlie@example.com', '555-555-5555', '2023-03-05', 'active');
