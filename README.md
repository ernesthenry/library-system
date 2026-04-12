# LibraryMS — Library Book Management System

A full-stack Library Management System built with **PHP REST API**, **AJAX**, and **JSON**. This project is designed as an educational tool to demonstrate modern web development practices.

---

## 🎓 Learning & Presentation
This project includes resources to help you understand and explain the architecture:
- **Presentation_Guide.md**: A dedicated guide with diagrams explaining the Request/Response cycle.
- **Educational Comments**: The source code is heavily commented (HTML, JS, PHP) to explain the logic and technical flow.

---

## Project Structure

```
library-system/
├── index.html              # Main frontend (SPA)
├── Presentation_Guide.md   # [NEW] Visual guide to system communication
├── setup.sql               # Database initialization script
├── api/
│   ├── config.php          # Database configuration & helpers (Commented)
│   ├── books.php           # REST API: Books CRUD (Commented)
│   ├── borrowers.php       # REST API: Borrowers CRUD
│   ├── loans.php           # REST API: Loan management
│   └── stats.php           # Dashboard statistics
├── assets/
│   ├── css/style.css       # Stylesheet
│   └── js/app.js           # AJAX logic (Commented)
└── index.html              # Main frontend (SPA)
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
- **MySQL 8.0+**
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
1. Move the `library-system` folder into your server's public directory (`htdocs` or `www`).
2. Start the **Apache** and **MySQL** services from your control panel.
3. Open **[http://localhost/library-system/](http://localhost/library-system/)** in your browser.

### 5. Database Setup (MySQL)
1. **Create the Database**: Open your MySQL server and create a database:
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

## 💡 How JSON is Used

JSON (JavaScript Object Notation) is the primary data exchange format for this system. It bridges the gap between the Browser and the Server:

1.  **Sending Data (JS → PHP)**: When a user submits a form, JavaScript gathers the data into an object and uses `JSON.stringify()` to turn it into a string before sending it to the server.
2.  **Reading Data (PHP)**: The server receives the raw string. Since it's JSON, PHP uses `json_decode(file_get_contents('php://input'), true)` to turn it back into a PHP array.
3.  **Responding (PHP → JS)**: After processing data (like fetching books), PHP uses `json_encode()` to convert the database results into a JSON string and sends it back.
4.  **Consuming Data (JS)**: The browser receives the JSON string, and our `fetch()` logic automatically converts it back into a JavaScript object for rendering.

---

## Features

- **Full CRUD** for Books, Borrowers, and Loans
- **Socially Documented**: Comprehensive logic comments for students/developers.
- **AJAX-powered** — no page reloads; all data fetched dynamically using the `fetch` API.
- **Real-time search** with debounced input filtering to optimize server requests.
- **Loan management** — check out, return, auto-detect overdue status.
- **Availability tracking** — automatic copy management on borrow/return actions.
- **Dashboard stats** — live totals and genre breakdown.
- **Responsive Design**: Mobile-friendly sidebar and navigation.

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

- **REST API design** — proper HTTP methods and status codes.
- **Asynchronous communication** — handling background data fetching.
- **PDO Security** — prepared statements to prevent SQL injection.
- **Single Page Application** — dynamic navigation without page reloads.
- **JSON Data Exchange** — structured passing of data between Client and Server.
 Create, Read, Update, Delete on 3 resources
- **Single Page Application** — dynamic navigation without page reloads
