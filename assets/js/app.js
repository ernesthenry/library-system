// Library Management System - Frontend Logic (JavaScript)
/**
 * ARCHITECTURE OVERVIEW:
 * This script acts as the "Controller". It:
 * 1. Listens for user events (clicks, searches).
 * 2. Communicates with the PHP backend via the Fetch API (AJAX).
 * 3. Receives JSON data and dynamically updates the HTML (DOM manipulation).
 */

const API = 'api'; // Base directory for our PHP backend files

// ── Utility: AJAX wrapper ────────────────────────────────────────────────────
/**
 * A central helper function to handle all HTTP requests.
 * @param {string} endpoint - The PHP file name (e.g., 'books.php').
 * @param {string} method - GET, POST, PUT, or DELETE.
 * @param {object} body - Data to send to the server (for POST/PUT).
 * @param {object} params - Query parameters (for GET).
 */
async function ajax(endpoint, method = 'GET', body = null, params = {}) {
  // Construct the URL with query parameters if provided
  const url = new URL(`${API}/${endpoint}`, window.location.href);
  Object.entries(params).forEach(([k, v]) => { if (v !== '') url.searchParams.set(k, v); });

  const opts = {
    method,
    headers: { 'Content-Type': 'application/json' },
  };
  
  // If we are sending data, stringify it into JSON
  if (body) opts.body = JSON.stringify(body);

  // Send the request using the native Fetch API
  const res  = await fetch(url, opts);
  const data = await res.json(); // The server always responds with JSON
  
  // Check if the server returned an error (e.g., 404 or 500)
  if (!res.ok) throw new Error(data.error || 'Request failed');
  return data;
}

// ── Toast notifications ──────────────────────────────────────────────────────
/** High-level feedback for the user (Success/Error messages) */
function toast(msg, type = 'info') {
  const icons = { success: '✅', error: '❌', info: 'ℹ️' };
  const el = document.createElement('div');
  el.className = `toast ${type}`;
  el.innerHTML = `<span>${icons[type]}</span><span>${msg}</span>`;
  document.getElementById('toastContainer').appendChild(el);
  setTimeout(() => el.remove(), 3500); // Auto-hide after 3.5 seconds
}

// ── Navigation ───────────────────────────────────────────────────────────────
/** Handles switching between different pages without refreshing the browser */
function navigate(page) {
  // Hide all pages and deactivate all nav items
  document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
  
  // Show the requested page
  document.getElementById(`page-${page}`).classList.add('active');
  const navItem = document.querySelector(`.nav-item[data-page="${page}"]`);
  if (navItem) navItem.classList.add('active');

  // Close sidebar on navigate (important for mobile UX)
  const sidebar = document.querySelector('.sidebar');
  if (sidebar) sidebar.classList.remove('open');

  // Trigger data loading for the specific page
  if (page === 'dashboard') loadDashboard();
  if (page === 'books')     loadBooks();
  if (page === 'borrowers') loadBorrowers();
  if (page === 'loans')     loadLoans();
}

/** Toggles the sidebar visibility on mobile devices */
function toggleSidebar() {
  const sidebar = document.querySelector('.sidebar');
  if (sidebar) sidebar.classList.toggle('open');
}

// ── Modal helpers ────────────────────────────────────────────────────────────
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

// ════════════════════════════════════════════════════════════════════════════
// DASHBOARD LOGIC
// ════════════════════════════════════════════════════════════════════════════
async function loadDashboard() {
  try {
    // GET request to stats.php to get overview numbers
    const s = await ajax('stats.php');
    
    // Update the stat cards in index.html
    document.getElementById('stat-books').textContent     = s.totalBooks;
    document.getElementById('stat-avail').textContent     = s.availableCopies;
    document.getElementById('stat-borrowers').textContent = s.totalBorrowers;
    document.getElementById('stat-active').textContent    = s.activeLoans;
    document.getElementById('stat-overdue').textContent   = s.overdueLoans;
    document.getElementById('stat-returned').textContent  = s.returnedLoans;

    // Build the "Books by Genre" list dynamically
    const genreEl = document.getElementById('genre-list');
    genreEl.innerHTML = Object.entries(s.genres)
      .sort((a, b) => b[1] - a[1]) // Sort by count descending
      .map(([g, n]) => `
        <div style="display:flex;justify-content:space-between;align-items:center;
                    padding:8px 0;border-bottom:1px solid var(--border)">
          <span>${g}</span>
          <span class="badge badge-muted">${n} book${n !== 1 ? 's' : ''}</span>
        </div>`).join('');
  } catch (e) { toast(e.message, 'error'); }
}

// ════════════════════════════════════════════════════════════════════════════
// BOOKS LOGIC (CRUD)
// ════════════════════════════════════════════════════════════════════════════
let editingBookId = null; // Tracks if we are currently editing an existing book

/** Fetches books from the server and renders the table */
async function loadBooks() {
  const searchInput = document.getElementById('book-search');
  const genreFilter = document.getElementById('book-genre-filter');
  const availFilter = document.getElementById('book-avail-filter');
  if (!searchInput) return;

  const search = searchInput.value;
  const genre  = genreFilter.value;
  const avail  = availFilter.value;
  const tbody  = document.getElementById('books-tbody');
  
  // Show loading state
  tbody.innerHTML = '<tr><td colspan="7" class="loading">Loading…</td></tr>';

  try {
    // GET request with search and filter parameters
    const books = await ajax('books.php', 'GET', null, { search, genre, available: avail });
    
    if (!books.length) {
      tbody.innerHTML = '<tr><td colspan="7"><div class="empty-state"><div class="emoji">📚</div><p>No books found</p></div></td></tr>';
      return;
    }

    // Map each book object into an HTML table row
    tbody.innerHTML = books.map(b => {
      const cls = b.available === 0 ? 'avail-none' : b.available <= 1 ? 'avail-low' : 'avail-good';
      const cover = b.coverUrl 
        ? `<img src="${b.coverUrl}" class="book-cover-thumb" onerror="this.outerHTML='<div class=\'book-cover-placeholder\'>📚</div>'" />`
        : `<div class="book-cover-placeholder">📚</div>`;
      
      return `<tr>
        <td>${cover}</td>
        <td><strong>${esc(b.title)}</strong></td>
        <td>${esc(b.author)}</td>
        <td><span style="font-family:monospace;font-size:0.8rem">${esc(b.isbn)}</span></td>
        <td><span class="badge badge-muted">${esc(b.genre)}</span></td>
        <td>${b.year}</td>
        <td><span class="avail-count ${cls}">${b.available}/${b.copies}</span></td>
        <td>
          <div class="actions">
            <button class="btn btn-ghost btn-sm" onclick="editBook(${b.id})">Edit</button>
            <button class="btn btn-danger btn-sm" onclick="deleteBook(${b.id},'${esc(b.title)}')">Delete</button>
          </div>
        </td>
      </tr>`;
    }).join('');
  } catch (e) { toast(e.message, 'error'); }
}

/** Prepares the modal for a "New Book" entry */
function openAddBook() {
  editingBookId = null;
  document.getElementById('book-modal-title').textContent = 'Add New Book';
  document.getElementById('book-form').reset();
  openModal('book-modal');
}

/** Fetches a single book's data to populate the edit form */
async function editBook(id) {
  try {
    const b = await ajax(`books.php?id=${id}`);
    editingBookId = id;
    document.getElementById('book-modal-title').textContent = 'Edit Book';
    document.getElementById('f-title').value  = b.title;
    document.getElementById('f-author').value = b.author;
    document.getElementById('f-isbn').value   = b.isbn;
    document.getElementById('f-genre').value  = b.genre;
    document.getElementById('f-year').value     = b.year;
    document.getElementById('f-copies').value   = b.copies;
    document.getElementById('f-coverUrl').value = b.coverUrl || '';
    openModal('book-modal');
  } catch (e) { toast(e.message, 'error'); }
}

/** Sends data to the server to Create or Update a book */
async function saveBook() {
  const data = {
    title:  document.getElementById('f-title').value.trim(),
    author: document.getElementById('f-author').value.trim(),
    isbn:   document.getElementById('f-isbn').value.trim(),
    genre:    document.getElementById('f-genre').value.trim(),
    year:     document.getElementById('f-year').value,
    copies:   document.getElementById('f-copies').value,
    coverUrl: document.getElementById('f-coverUrl').value.trim(),
  };
  if (!data.title || !data.author) return toast('Title and author required', 'error');

  try {
    if (editingBookId) {
      // PUT request for updates
      await ajax(`books.php?id=${editingBookId}`, 'PUT', data);
      toast('Book updated', 'success');
    } else {
      // POST request for new entries
      await ajax('books.php', 'POST', data);
      toast('Book added', 'success');
    }
    closeModal('book-modal');
    loadBooks(); // Refresh the list
  } catch (e) { toast(e.message, 'error'); }
}

/** Sends a DELETE request to the server */
async function deleteBook(id, title) {
  if (!confirm(`Delete "${title}"?`)) return;
  try {
    await ajax(`books.php?id=${id}`, 'DELETE');
    toast('Book deleted', 'success');
    loadBooks();
  } catch (e) { toast(e.message, 'error'); }
}

// ════════════════════════════════════════════════════════════════════════════
// BORROWERS LOGIC
// ════════════════════════════════════════════════════════════════════════════
let editingBorrowerId = null;

async function loadBorrowers() {
  const searchInput = document.getElementById('borrower-search');
  if (!searchInput) return;

  const search = searchInput.value;
  const tbody  = document.getElementById('borrowers-tbody');
  tbody.innerHTML = '<tr><td colspan="6" class="loading">Loading…</td></tr>';

  try {
    const list = await ajax('borrowers.php', 'GET', null, { search });
    if (!list.length) {
      tbody.innerHTML = '<tr><td colspan="6"><div class="empty-state"><div class="emoji">👥</div><p>No borrowers found</p></div></td></tr>';
      return;
    }
    tbody.innerHTML = list.map(b => `<tr>
      <td><strong>${esc(b.name)}</strong></td>
      <td>${esc(b.email)}</td>
      <td>${esc(b.phone)}</td>
      <td>${b.memberSince}</td>
      <td><span class="badge ${b.status === 'active' ? 'badge-success' : 'badge-muted'}">${b.status}</span></td>
      <td>
        <div class="actions">
          <button class="btn btn-ghost btn-sm" onclick="editBorrower(${b.id})">Edit</button>
          <button class="btn btn-danger btn-sm" onclick="deleteBorrower(${b.id},'${esc(b.name)}')">Delete</button>
        </div>
      </td>
    </tr>`).join('');
  } catch (e) { toast(e.message, 'error'); }
}

function openAddBorrower() {
  editingBorrowerId = null;
  document.getElementById('borrower-modal-title').textContent = 'Register Borrower';
  document.getElementById('borrower-form').reset();
  openModal('borrower-modal');
}

async function editBorrower(id) {
  try {
    const b = await ajax(`borrowers.php?id=${id}`);
    editingBorrowerId = id;
    document.getElementById('borrower-modal-title').textContent = 'Edit Borrower';
    document.getElementById('fb-name').value   = b.name;
    document.getElementById('fb-email').value  = b.email;
    document.getElementById('fb-phone').value  = b.phone;
    document.getElementById('fb-status').value = b.status;
    openModal('borrower-modal');
  } catch (e) { toast(e.message, 'error'); }
}

async function saveBorrower() {
  const data = {
    name:   document.getElementById('fb-name').value.trim(),
    email:  document.getElementById('fb-email').value.trim(),
    phone:  document.getElementById('fb-phone').value.trim(),
    status: document.getElementById('fb-status').value,
  };
  if (!data.name || !data.email) return toast('Name and email required', 'error');

  try {
    if (editingBorrowerId) {
      await ajax(`borrowers.php?id=${editingBorrowerId}`, 'PUT', data);
      toast('Borrower updated', 'success');
    } else {
      await ajax('borrowers.php', 'POST', data);
      toast('Borrower registered', 'success');
    }
    closeModal('borrower-modal');
    loadBorrowers();
  } catch (e) { toast(e.message, 'error'); }
}

async function deleteBorrower(id, name) {
  if (!confirm(`Remove borrower "${name}"?`)) return;
  try {
    await ajax(`borrowers.php?id=${id}`, 'DELETE');
    toast('Borrower removed', 'success');
    loadBorrowers();
  } catch (e) { toast(e.message, 'error'); }
}

// ════════════════════════════════════════════════════════════════════════════
// LOANS LOGIC
// ════════════════════════════════════════════════════════════════════════════
async function loadLoans() {
  const filter = document.getElementById('loan-status-filter');
  if (!filter) return;

  const status = filter.value;
  const tbody  = document.getElementById('loans-tbody');
  tbody.innerHTML = '<tr><td colspan="7" class="loading">Loading…</td></tr>';

  try {
    const loans = await ajax('loans.php', 'GET', null, { status });
    if (!loans.length) {
      tbody.innerHTML = '<tr><td colspan="7"><div class="empty-state"><div class="emoji">📋</div><p>No loans found</p></div></td></tr>';
      return;
    }
    tbody.innerHTML = loans.map(l => {
      const badgeCls = l.status === 'returned' ? 'badge-muted' : l.status === 'overdue' ? 'badge-danger' : 'badge-success';
      const returnBtn = l.status !== 'returned'
        ? `<button class="btn btn-success btn-sm" onclick="returnBook(${l.id})">Return</button>` : '';
      return `<tr>
        <td><strong>${esc(l.bookTitle)}</strong></td>
        <td>${esc(l.borrowerName)}</td>
        <td>${l.borrowedDate}</td>
        <td>${l.dueDate}</td>
        <td>${l.returnedDate || '—'}</td>
        <td><span class="badge ${badgeCls}">${l.status}</span></td>
        <td>
          <div class="actions">
            ${returnBtn}
            <button class="btn btn-danger btn-sm" onclick="deleteLoan(${l.id})">Delete</button>
          </div>
        </td>
      </tr>`;
    }).join('');
  } catch (e) { toast(e.message, 'error'); }
}

/** Populates the book and borrower dropdowns when checking out a book */
async function openBorrowModal() {
  try {
    const books     = await ajax('books.php', 'GET', null, { available: '1' });
    const borrowers = await ajax('borrowers.php', 'GET', null, { status: 'active' });

    document.getElementById('fl-book').innerHTML =
      `<option value="">— Select Book —</option>` +
      books.map(b => `<option value="${b.id}">${esc(b.title)} (${b.available} avail.)</option>`).join('');

    document.getElementById('fl-borrower').innerHTML =
      `<option value="">— Select Borrower —</option>` +
      borrowers.map(b => `<option value="${b.id}">${esc(b.name)}</option>`).join('');

    openModal('loan-modal');
  } catch (e) { toast(e.message, 'error'); }
}

async function saveLoan() {
  const bookId     = document.getElementById('fl-book').value;
  const borrowerId = document.getElementById('fl-borrower').value;
  if (!bookId || !borrowerId) return toast('Select book and borrower', 'error');

  try {
    await ajax('loans.php', 'POST', { bookId: +bookId, borrowerId: +borrowerId });
    toast('Book checked out (due in 14 days)', 'success');
    closeModal('loan-modal');
    loadLoans();
  } catch (e) { toast(e.message, 'error'); }
}

async function returnBook(id) {
  if (!confirm('Mark this book as returned?')) return;
  try {
    await ajax(`loans.php?id=${id}`, 'PUT');
    toast('Book returned successfully', 'success');
    loadLoans();
  } catch (e) { toast(e.message, 'error'); }
}

async function deleteLoan(id) {
  if (!confirm('Delete this loan record?')) return;
  try {
    await ajax(`loans.php?id=${id}`, 'DELETE');
    toast('Loan record deleted', 'success');
    loadLoans();
  } catch (e) { toast(e.message, 'error'); }
}

// ── Helpers ──────────────────────────────────────────────────────────────────
/** Escape HTML special characters to prevent XSS attacks */
function esc(str) {
  if (!str) return '';
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Boot ─────────────────────────────────────────────────────────────────────
/** This code runs as soon as the HTML has fully loaded */
document.addEventListener('DOMContentLoaded', () => {
  // Bind click events to Sidebar navigation items
  document.querySelectorAll('.nav-item').forEach(n => {
    n.addEventListener('click', () => navigate(n.dataset.page));
  });

  // Mobile sidebar toggle button
  const toggleBtn = document.getElementById('sidebar-toggle');
  if (toggleBtn) toggleBtn.addEventListener('click', toggleSidebar);

  // Close sidebar when clicking outside on mobile
  document.addEventListener('click', e => {
    const sidebar = document.querySelector('.sidebar');
    const toggle = document.getElementById('sidebar-toggle');
    if (sidebar && toggle && window.innerWidth <= 992 && 
        sidebar.classList.contains('open') && 
        !sidebar.contains(e.target) && 
        !toggle.contains(e.target)) {
      sidebar.classList.remove('open');
    }
  });

  // Debounced search: Wait 300ms after user stops typing before sending API request
  let searchTimer;
  const bookSearch = document.getElementById('book-search');
  if (bookSearch) {
    bookSearch.addEventListener('input', () => {
      clearTimeout(searchTimer); searchTimer = setTimeout(loadBooks, 300);
    });
  }
  
  const borrowerSearch = document.getElementById('borrower-search');
  if (borrowerSearch) {
    borrowerSearch.addEventListener('input', () => {
      clearTimeout(searchTimer); searchTimer = setTimeout(loadBorrowers, 300);
    });
  }

  // Close modals when clicking the dark background overlay
  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', e => {
      if (e.target === overlay) overlay.classList.remove('open');
    });
  });

  // Start at the dashboard
  navigate('dashboard');
});
