/**
 * ==============================================================================
 * Library Management System - Frontend Controller (JavaScript)
 * ==============================================================================
 * 
 * ROLE:
 * This script acts as the "Controller" in our architecture. It:
 * 1. Manages State: Tracks active pages and modal visibility.
 * 2. Handles Events: Listens for clicks, typing, and form submissions.
 * 3. Communicates with Backend: Uses the Fetch API to talk to PHP endpoints.
 * 4. Updates UI: Dynamically modifies the DOM based on API responses.
 * 
 * DESIGN PATTERN:
 * - Single Page Application (SPA): Toggles CSS 'active' classes to switch views.
 * - AJAX/REST: Asynchronous communication with JSON data exchange.
 * ==============================================================================
 */

const API = 'api'; // Base directory for our PHP backend endpoints

// ── UTILITY: AJAX WRAPPER ────────────────────────────────────────────────────

/**
 * A central helper function to handle all HTTP requests (GET, POST, PUT, DELETE).
 * Simplifies error handling and URL construction.
 * 
 * @param {string} endpoint - The PHP file name (e.g., 'books.php').
 * @param {string} method - HTTP Verb (GET, POST, PUT, DELETE).
 * @param {object|null} body - Data to send to the server (stringified to JSON).
 * @param {object} params - Key-value pairs for URL query parameters.
 * @returns {Promise<any>} - The JSON data returned by the server.
 */
async function ajax(endpoint, method = 'GET', body = null, params = {}) {
  // 1. Construct the URL with query parameters (e.g., api/books.php?genre=Science)
  const url = new URL(`${API}/${endpoint}`, window.location.href);
  Object.entries(params).forEach(([k, v]) => { 
    if (v !== '' && v !== null && v !== undefined) url.searchParams.set(k, v); 
  });

  const opts = {
    method,
    headers: { 'Content-Type': 'application/json' },
  };
  
  // 2. Add JSON body if provided
  if (body) opts.body = JSON.stringify(body);

  // 3. Send the request
  const res  = await fetch(url, opts);
  
  // 4. Parse JSON response
  const data = await res.json();
  
  // 5. Check for HTTP errors (4xx, 5xx)
  if (!res.ok) throw new Error(data.error || `Request failed with status ${res.status}`);
  
  return data;
}

// ── TOAST NOTIFICATIONS ──────────────────────────────────────────────────────

/**
 * Displays a temporary popup message to provide feedback to the user.
 * 
 * @param {string} msg - The text to display.
 * @param {'success'|'error'|'info'} type - Sets the visual style (green/red/gold).
 */
function toast(msg, type = 'info') {
  const icons = { success: '✅', error: '❌', info: 'ℹ️' };
  const el = document.createElement('div');
  el.className = `toast ${type}`;
  el.innerHTML = `<span>${icons[type]}</span><span>${msg}</span>`;
  
  document.getElementById('toastContainer').appendChild(el);
  
  // Auto-remove after animation finishes
  setTimeout(() => el.remove(), 3500);
}

// ── NAVIGATION & SPA LOGIC ───────────────────────────────────────────────────

/**
 * Handles switching between different "pages" (sections) in the SPA.
 * Manages both visibility and triggering data load for the new page.
 * 
 * @param {string} page - The ID suffix of the page to show (e.g., 'books', 'loans').
 */
function navigate(page) {
  // 1. UI: Hide all pages and deactivate navigation links
  document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
  
  // 2. UI: Show the requested page section
  const targetPage = document.getElementById(`page-${page}`);
  if (targetPage) targetPage.classList.add('active');
  
  // 3. UI: Highlight the corresponding sidebar menu item
  const navItem = document.querySelector(`.nav-item[data-page="${page}"]`);
  if (navItem) navItem.classList.add('active');

  // 4. UX: Ensure sidebar closes on mobile after selection
  const sidebar = document.querySelector('.sidebar');
  if (sidebar) sidebar.classList.remove('open');

  // 5. DATA: Trigger the specific loading function for the new view
  const loaders = {
    'dashboard': loadDashboard,
    'books':     loadBooks,
    'borrowers': loadBorrowers,
    'loans':     loadLoans
  };
  if (loaders[page]) loaders[page]();
}

/** Toggles the sidebar visibility on mobile devices (Hamburger menu) */
function toggleSidebar() {
  const sidebar = document.querySelector('.sidebar');
  if (sidebar) sidebar.classList.toggle('open');
}

// ── MODAL HELPERS ────────────────────────────────────────────────────────────

/** Shows a modal by adding the 'open' class */
function openModal(id)  { document.getElementById(id).classList.add('open'); }

/** Hides a modal by removing the 'open' class */
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

/**
 * Generic confirmation handler to replace native 'confirm()'.
 * @param {string} title - Header text.
 * @param {string} msg - Subtext/Warning.
 * @param {Function} onConfirm - Callback if user clicks 'Proceed'.
 */
function showConfirm(title, msg, onConfirm) {
  document.getElementById('confirm-title').textContent = title;
  document.getElementById('confirm-msg').textContent   = msg;
  
  const proceedBtn = document.getElementById('confirm-proceed-btn');
  // Use a clone to clear old event listeners from previous calls
  const newBtn = proceedBtn.cloneNode(true);
  proceedBtn.parentNode.replaceChild(newBtn, proceedBtn);
  
  newBtn.addEventListener('click', () => {
    onConfirm();
    closeModal('confirm-modal');
  });
  
  openModal('confirm-modal');
}

// ════════════════════════════════════════════════════════════════════════════
// SECTION: DASHBOARD
// ════════════════════════════════════════════════════════════════════════════

/**
 * Fetches high-level statistics from the backend and updates the dashboard UI.
 * Includes total counts for books, members, and active library activity.
 */
async function loadDashboard() {
  try {
    const s = await ajax('stats.php');
    
    // Update numeric stat cards
    document.getElementById('stat-books').textContent     = s.totalBooks;
    document.getElementById('stat-avail').textContent     = s.availableCopies;
    document.getElementById('stat-borrowers').textContent = s.totalBorrowers;
    document.getElementById('stat-active').textContent    = s.activeLoans;
    document.getElementById('stat-overdue').textContent   = s.overdueLoans;
    document.getElementById('stat-returned').textContent  = s.returnedLoans;

    // Build the "Books by Genre" List dynamically
    const genreEl = document.getElementById('genre-list');
    genreEl.innerHTML = Object.entries(s.genres)
      .sort((a, b) => b[1] - a[1]) // Most popular genres first
      .map(([g, n]) => `
        <div style="display:flex;justify-content:space-between;align-items:center;
                    padding:8px 0;border-bottom:1px solid var(--border)">
          <span>${g}</span>
          <span class="badge badge-muted">${n} book${n !== 1 ? 's' : ''}</span>
        </div>`).join('');
  } catch (e) { 
    toast(e.message, 'error'); 
  }
}

// ════════════════════════════════════════════════════════════════════════════
// SECTION: BOOKS (CRUD Operations)
// ════════════════════════════════════════════════════════════════════════════

let editingBookId = null; // Tracks if we are currently editing an existing record

/**
 * Fetches the book list from the server based on current search and filter settings.
 * Renders the results into the books table.
 */
async function loadBooks() {
  const searchInput = document.getElementById('book-search');
  const genreFilter = document.getElementById('book-genre-filter');
  const availFilter = document.getElementById('book-avail-filter');
  if (!searchInput) return;

  const search = searchInput.value;
  const genre  = genreFilter.value;
  const avail  = availFilter.value;
  const tbody  = document.getElementById('books-tbody');
  
  // Show loading indicator
  tbody.innerHTML = '<tr><td colspan="8" class="loading">Searching...</td></tr>';

  try {
    const books = await ajax('books.php', 'GET', null, { search, genre, available: avail });
    
    if (!books.length) {
      tbody.innerHTML = '<tr><td colspan="8"><div class="empty-state"><div class="emoji">📚</div><p>No books matching your criteria</p></div></td></tr>';
      return;
    }

    // Map JSON results to HTML Table Rows
    tbody.innerHTML = books.map(b => {
      // Dynamic availability styling
      const cls = b.available === 0 ? 'avail-none' : b.available <= 1 ? 'avail-low' : 'avail-good';
      
      // Thumbnail logic
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
  } catch (e) { 
    toast(e.message, 'error'); 
  }
}

/** Resets the modal form and marks the state for Creating a new book record */
function openAddBook() {
  editingBookId = null;
  document.getElementById('book-modal-title').textContent = 'Add New Book';
  document.getElementById('book-form').reset();
  openModal('book-modal');
}

/** Fetches a specific book's data and populates the modal form for Editing */
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
  } catch (e) { 
    toast(e.message, 'error'); 
  }
}

/** Validates inputs and sends a POST (Create) or PUT (Update) request to the server */
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
  
  if (!data.title || !data.author) return toast('Title and author are required', 'error');

  try {
    if (editingBookId) {
      await ajax(`books.php?id=${editingBookId}`, 'PUT', data);
      toast('Book updated successfully', 'success');
    } else {
      await ajax('books.php', 'POST', data);
      toast('New book added to catalogue', 'success');
    }
    closeModal('book-modal');
    loadBooks(); 
  } catch (e) { 
    toast(e.message, 'error'); 
  }
}

/** Sends a DELETE request after user confirmation */
async function deleteBook(id, title) {
  showConfirm(
    'Delete Book?',
    `Are you sure you want to delete "${title}"? This action cannot be undone.`,
    async () => {
      try {
        await ajax(`books.php?id=${id}`, 'DELETE');
        toast('Book record removed', 'success');
        loadBooks();
      } catch (e) { 
        toast(e.message, 'error'); 
      }
    }
  );
}

// ════════════════════════════════════════════════════════════════════════════
// SECTION: BORROWERS
// ════════════════════════════════════════════════════════════════════════════

let editingBorrowerId = null;

/** Fetches and renders the list of registered library members */
async function loadBorrowers() {
  const searchInput = document.getElementById('borrower-search');
  if (!searchInput) return;

  const search = searchInput.value;
  const tbody  = document.getElementById('borrowers-tbody');
  tbody.innerHTML = '<tr><td colspan="6" class="loading">Loading members...</td></tr>';

  try {
    const list = await ajax('borrowers.php', 'GET', null, { search });
    if (!list.length) {
      tbody.innerHTML = '<tr><td colspan="6"><div class="empty-state"><div class="emoji">👥</div><p>No members found</p></div></td></tr>';
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
          <button class="btn btn-danger btn-sm" onclick="deleteBorrower(${b.id},'${esc(b.name)}')">Remove</button>
        </div>
      </td>
    </tr>`).join('');
  } catch (e) { 
    toast(e.message, 'error'); 
  }
}

/** Resets the member registration form */
function openAddBorrower() {
  editingBorrowerId = null;
  document.getElementById('borrower-modal-title').textContent = 'Register Borrower';
  document.getElementById('borrower-form').reset();
  openModal('borrower-modal');
}

/** Populates the form for editing an existing member */
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
  } catch (e) { 
    toast(e.message, 'error'); 
  }
}

/** Handles Creating or Updating a borrower account */
async function saveBorrower() {
  const data = {
    name:   document.getElementById('fb-name').value.trim(),
    email:  document.getElementById('fb-email').value.trim(),
    phone:  document.getElementById('fb-phone').value.trim(),
    status: document.getElementById('fb-status').value,
  };
  if (!data.name || !data.email) return toast('Name and email are required', 'error');

  try {
    if (editingBorrowerId) {
      await ajax(`borrowers.php?id=${editingBorrowerId}`, 'PUT', data);
      toast('Borrower profile updated', 'success');
    } else {
      await ajax('borrowers.php', 'POST', data);
      toast('Borrower registered successfully', 'success');
    }
    closeModal('borrower-modal');
    loadBorrowers();
  } catch (e) { 
    toast(e.message, 'error'); 
  }
}

/** Deletes a borrower record from the system */
async function deleteBorrower(id, name) {
  showConfirm(
    'Remove Borrower?',
    `Remove registration for "${name}"? This is only possible if they have no active loans.`,
    async () => {
      try {
        await ajax(`borrowers.php?id=${id}`, 'DELETE');
        toast('Borrower removed from registry', 'success');
        loadBorrowers();
      } catch (e) { 
        toast(e.message, 'error'); 
      }
    }
  );
}

// ════════════════════════════════════════════════════════════════════════════
// SECTION: LOANS & CIRCULATION
// ════════════════════════════════════════════════════════════════════════════

/** Fetches and renders the current library circulation (loans) */
async function loadLoans() {
  const filter = document.getElementById('loan-status-filter');
  if (!filter) return;

  const status = filter.value;
  const tbody  = document.getElementById('loans-tbody');
  tbody.innerHTML = '<tr><td colspan="7" class="loading">Fetching loan status...</td></tr>';

  try {
    const loans = await ajax('loans.php', 'GET', null, { status });
    if (!loans.length) {
      tbody.innerHTML = '<tr><td colspan="7"><div class="empty-state"><div class="emoji">📋</div><p>No loan records found for this category</p></div></td></tr>';
      return;
    }
    
    tbody.innerHTML = loans.map(l => {
      // Logic for status badges and conditional "Return" button
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
  } catch (e) { 
    toast(e.message, 'error'); 
  }
}

/** Fetches available books and active borrowers to populate the Checkout Modal */
async function openBorrowModal() {
  try {
    // We only want books that have > 0 copies and borrowers who are 'active'
    const books     = await ajax('books.php', 'GET', null, { available: '1' });
    const borrowers = await ajax('borrowers.php', 'GET', null, { status: 'active' });

    document.getElementById('fl-book').innerHTML =
      `<option value="">— Select Book —</option>` +
      books.map(b => `<option value="${b.id}">${esc(b.title)} (${b.available} avail.)</option>`).join('');

    document.getElementById('fl-borrower').innerHTML =
      `<option value="">— Select Borrower —</option>` +
      borrowers.map(b => `<option value="${b.id}">${esc(b.name)}</option>`).join('');

    openModal('loan-modal');
  } catch (e) { 
    toast(e.message, 'error'); 
  }
}

/** Creates a new loan record and decreases book availability */
async function saveLoan() {
  const bookId     = document.getElementById('fl-book').value;
  const borrowerId = document.getElementById('fl-borrower').value;
  if (!bookId || !borrowerId) return toast('Please select both a book and a borrower', 'error');

  try {
    await ajax('loans.php', 'POST', { bookId: +bookId, borrowerId: +borrowerId });
    toast('Book checked out (Due in 14 days)', 'success');
    closeModal('loan-modal');
    loadLoans();
  } catch (e) { 
    toast(e.message, 'error'); 
  }
}

/** Marks a book as returned and increases book availability */
async function returnBook(id) {
  showConfirm(
    'Confirm Return?',
    'Mark this book copy as returned to the library?',
    async () => {
      try {
        await ajax(`loans.php?id=${id}`, 'PUT');
        toast('Book returned successfully', 'success');
        loadLoans();
      } catch (e) { 
        toast(e.message, 'error'); 
      }
    }
  );
}

/** Forcefully removes a loan record (admin only action) */
async function deleteLoan(id) {
  showConfirm(
    'Delete Loan Record?',
    'Are you sure you want to delete this historical record? This cannot be undone.',
    async () => {
      try {
        await ajax(`loans.php?id=${id}`, 'DELETE');
        toast('Loan record deleted', 'success');
        loadLoans();
      } catch (e) { 
        toast(e.message, 'error'); 
      }
    }
  );
}

// ── HELPERS ──────────────────────────────────────────────────────────────────

/**
 * Escapes HTML special characters to prevent Cross-Site Scripting (XSS).
 * 
 * @param {string} str - The raw string.
 * @returns {string} - The sanitized string.
 */
function esc(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g,'&amp;')
    .replace(/</g,'&lt;')
    .replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;')
    .replace(/'/g,'&#039;');
}

// ── BOOTSTRAP (App Initialization) ──────────────────────────────────────────

/** This listener handles the initial page setup once the DOM is ready */
document.addEventListener('DOMContentLoaded', () => {
  // 1. Sidebar Navigation: Bind click events to all nav buttons
  document.querySelectorAll('.nav-item').forEach(n => {
    n.addEventListener('click', () => navigate(n.dataset.page));
  });

  // 2. Mobile Logic: Bind toggle button
  const toggleBtn = document.getElementById('sidebar-toggle');
  if (toggleBtn) toggleBtn.addEventListener('click', toggleSidebar);

  // 3. Mobile Logic: Close sidebar when clicking outside on small screens
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

  // 4. Search Logic: Debounced search for Books and Borrowers
  // Wait 300ms after the last keystroke before sending API request to save server resources.
  let searchTimer;
  
  const bookSearch = document.getElementById('book-search');
  if (bookSearch) {
    bookSearch.addEventListener('input', () => {
      clearTimeout(searchTimer); 
      searchTimer = setTimeout(loadBooks, 300);
    });
  }
  
  const borrowerSearch = document.getElementById('borrower-search');
  if (borrowerSearch) {
    borrowerSearch.addEventListener('input', () => {
      clearTimeout(searchTimer); 
      searchTimer = setTimeout(loadBorrowers, 300);
    });
  }

  // 5. Modal Logic: Dynamic backdrop close
  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', e => {
      if (e.target === overlay) overlay.classList.remove('open');
    });
  });

  // 6. Start: Navigate to the default dashboard view
  navigate('dashboard');
});

