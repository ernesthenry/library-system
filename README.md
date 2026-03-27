# LibraryMS — Library Book Management System

A full-stack Library Management System built with **PHP REST API**, **AJAX**, and **JSON** data storage.

---

## Project Structure

```
library-system/
├── index.html              # Main frontend (SPA)
├── api/
│   ├── config.php          # Shared helpers & data access
│   ├── books.php           # REST API: Books CRUD
│   ├── borrowers.php       # REST API: Borrowers CRUD
│   ├── loans.php           # REST API: Loan management
│   └── stats.php           # Dashboard statistics
├── data/
│   ├── books.json          # Persistent book data
│   ├── borrowers.json      # Persistent borrower data
│   └── loans.json          # Persistent loan records
└── assets/
    ├── css/style.css       # Stylesheet
    └── js/app.js           # AJAX logic
```

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
```bash
cd library-system
php -S localhost:8000
```
Then open **http://localhost:8000** in your browser.

### 4. Run with XAMPP / WAMP
1. Copy the `library-system/` folder to your `htdocs/` (XAMPP) or `www/` (WAMP) directory
2. Start Apache
3. Visit **http://localhost/library-system/**

### 5. File Permissions (Linux/Mac)
```bash
chmod 664 data/*.json
```

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
- **JSON file storage** — no database required
- **Filter & search** with URL query parameters passed via AJAX

---

## Technologies Used

| Layer | Technology |
|-------|-----------|
| Frontend | HTML5, CSS3, JavaScript (ES6+) |
| Communication | AJAX (`fetch` API), JSON |
| Backend | PHP 8 REST API |
| Storage | JSON flat files |
| Architecture | REST (GET/POST/PUT/DELETE) |

---

## Concepts Demonstrated

- **REST API design** — proper HTTP methods and status codes
- **Asynchronous communication** — async/await AJAX calls
- **Parameter passing** — query strings, request body (JSON)
- **Server-client interaction** — PHP processes, JSON response
- **CRUD operations** — Create, Read, Update, Delete on 3 resources
- **Single Page Application** — dynamic navigation without page reloads
