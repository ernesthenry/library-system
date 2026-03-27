# LibraryMS — Library Book Management System

A full-stack Library Management System built with **PHP REST API**, **AJAX**, and **JSON** data storage.

---

## Project Structure

```
library-system/
├── index.html              # Main frontend (SPA)
├── setup.sql               # Database initialization script
├── api/
│   ├── config.php          # Database configuration & helpers
│   ├── books.php           # REST API: Books CRUD
│   ├── borrowers.php       # REST API: Borrowers CRUD
│   ├── loans.php           # REST API: Loan management
│   └── stats.php           # Dashboard statistics
├── assets/
│   ├── css/style.css       # Stylesheet
│   └── js/app.js           # AJAX logic
└── index.html              # Main frontend (SPA)

---

## Setup Instructions

### 1. Clone the Repository
```bash
git clone https://github.com/ernesthenry/library-system.git
cd library-system
```

### 2. Requirements
- **PHP 8.0+** (with `php -S` built-in server, or XAMPP/WAMP/LAMP)
- A modern web browser

### 3. Run Locally (PHP Built-in Server)
If you have PHP installed (Windows/Mac/Linux), you can run it directly:
1. Open terminal/command prompt in the `library-system` folder.
2. Run the built-in server:
   ```bash
   php -S localhost:8000
   ```
3. Visit **[http://localhost:8000](http://localhost:8000)** in your browser.

### 4. Run with XAMPP / WAMP / MAMP
1. Move the `library-system` folder into your server's public directory:
   - **XAMPP**: `C:\xampp\htdocs\`
   - **WAMP**: `C:\wamp64\www\`
   - **MAMP**: `/Applications/MAMP/htdocs/`
2. Start the **Apache** and **MySQL** services from your control panel.
3. Open **[http://localhost/library-system/](http://localhost/library-system/)** in your browser.

### 5. Database Setup (MySQL)
1. **Create the Database**: Open your MySQL server (via phpMyAdmin or terminal) and create a database:
   ```sql
   CREATE DATABASE library_db;
   ```
2. **Import Schema**: Import the `setup.sql` file provided in the root directory:
   ```bash
   mysql -u root -p library_db < setup.sql
   ```
3. **Configure Connection**: Open `api/config.php` and update your database credentials (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`).

---

## REST API Endpoints

### Books — `api/books.php`
| Method | URL | Description |
|--------|-----|-------------|
| GET    | `api/books.php` | Get all books |
| GET    | `api/books.php?id=1` | Get book by ID |
| GET    | `api/books.php?search=clean&genre=Programming&available=1` | Filter books |
| POST   | `api/books.php` | Create new book |
| PUT    | `api/books.php?id=1` | Update book |
| DELETE | `api/books.php?id=1` | Delete book |

#### POST/PUT Body (JSON)
```json
{
  "title": "Clean Code",
  "author": "Robert C. Martin",
  "isbn": "978-0132350884",
  "genre": "Programming",
  "year": 2008,
  "copies": 3
}
```

### Borrowers — `api/borrowers.php`
| Method | URL | Description |
|--------|-----|-------------|
| GET    | `api/borrowers.php` | Get all borrowers |
| GET    | `api/borrowers.php?search=alice` | Search borrowers |
| POST   | `api/borrowers.php` | Register borrower |
| PUT    | `api/borrowers.php?id=1` | Update borrower |
| DELETE | `api/borrowers.php?id=1` | Delete borrower |

### Loans — `api/loans.php`
| Method | URL | Description |
|--------|-----|-------------|
| GET    | `api/loans.php` | Get all loans |
| GET    | `api/loans.php?status=overdue` | Filter by status |
| POST   | `api/loans.php` | Check out book |
| PUT    | `api/loans.php?id=1` | Return book |
| DELETE | `api/loans.php?id=1` | Delete loan record |

#### POST Body (checkout)
```json
{
  "bookId": 1,
  "borrowerId": 2
}
```

### Statistics — `api/stats.php`
| Method | URL | Description |
|--------|-----|-------------|
| GET    | `api/stats.php` | Dashboard summary stats |

---

## Features

- **Full CRUD** for Books, Borrowers, and Loans
- **AJAX-powered** — no page reloads; all data fetched dynamically
- **Real-time search** with debounced input filtering
- **Loan management** — check out, return, auto-detect overdue
- **Availability tracking** — copies decrement/increment on borrow/return
- **Dashboard stats** — totals, genre breakdown, loan status summary
- **Toast notifications** for all API responses
- **MySQL Database** — robust relational storage with InnoDB support
- **Filter & search** with URL query parameters passed via AJAX

---

## Technologies Used

| Layer | Technology |
|-------|-----------|
| Frontend | HTML5, CSS3, JavaScript (ES6+) |
| Communication | AJAX (`fetch` API), JSON |
| Backend | PHP 8 REST API (PDO) |
| Storage | MySQL 8.0+ |
| Architecture | REST (GET/POST/PUT/DELETE) |

---

## Concepts Demonstrated

- **REST API design** — proper HTTP methods and status codes
- **Asynchronous communication** — async/await AJAX calls
- **Parameter passing** — query strings, request body (JSON)
- **Server-client interaction** — PHP processes, JSON response
- **CRUD operations** — Create, Read, Update, Delete on 3 resources
- **Single Page Application** — dynamic navigation without page reloads
