/* ============================================================
   Arockia Electricals - App JavaScript
   ============================================================ */

// Dark Mode
const themeToggle = document.getElementById('themeToggle');
const htmlEl = document.documentElement;
const savedTheme = localStorage.getItem('theme') || 'light';
htmlEl.setAttribute('data-bs-theme', savedTheme);
if (themeToggle) {
    themeToggle.querySelector('i').className = savedTheme === 'dark' ? 'bi bi-sun fs-5' : 'bi bi-moon-stars fs-5';
    themeToggle.addEventListener('click', () => {
        const current = htmlEl.getAttribute('data-bs-theme');
        const next = current === 'dark' ? 'light' : 'dark';
        htmlEl.setAttribute('data-bs-theme', next);
        localStorage.setItem('theme', next);
        themeToggle.querySelector('i').className = next === 'dark' ? 'bi bi-sun fs-5' : 'bi bi-moon-stars fs-5';
    });
}

// Sidebar Toggle
const sidebarToggle = document.getElementById('sidebarToggle');
const sidebarCloseBtn = document.getElementById('sidebarCloseBtn');
const sidebar = document.getElementById('sidebar');

// Initialize sidebar state
const savedSidebarState = localStorage.getItem('sidebarState') || 'expanded';
if (savedSidebarState === 'collapsed' && window.innerWidth > 991) {
    document.querySelector('.wrapper').classList.add('sidebar-collapsed');
    document.querySelector('.main-content').classList.add('expanded');
}

function toggleMobileSidebar(show) {
    if (show) {
        sidebar.classList.add('show');
        document.body.style.overflow = 'hidden';
        document.body.style.position = 'fixed';
        document.body.style.width = '100%';
        let overlay = document.querySelector('.sidebar-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay';
            document.body.appendChild(overlay);
            overlay.offsetHeight;
            overlay.classList.add('show');
            overlay.addEventListener('click', () => toggleMobileSidebar(false));
        } else {
            overlay.classList.add('show');
        }
    } else {
        sidebar.classList.remove('show');
        document.body.style.overflow = '';
        document.body.style.position = '';
        document.body.style.width = '';
        const overlay = document.querySelector('.sidebar-overlay');
        if (overlay) {
            overlay.classList.remove('show');
            setTimeout(() => overlay.remove(), 300);
        }
    }
}

if (sidebarToggle) {
    sidebarToggle.addEventListener('click', () => {
        if (window.innerWidth <= 991) {
            toggleMobileSidebar(true);
        } else {
            const wrapper = document.querySelector('.wrapper');
            const mainContent = document.querySelector('.main-content');
            
            wrapper.classList.toggle('sidebar-collapsed');
            mainContent.classList.toggle('expanded');
            
            if (wrapper.classList.contains('sidebar-collapsed')) {
                localStorage.setItem('sidebarState', 'collapsed');
            } else {
                localStorage.setItem('sidebarState', 'expanded');
            }
        }
    });
}
if (sidebarCloseBtn) {
    sidebarCloseBtn.addEventListener('click', () => toggleMobileSidebar(false));
}

// Auto-dismiss alerts
document.querySelectorAll('.alert').forEach(alert => {
    setTimeout(() => {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    }, 5000);
});

// Initialize DataTables
function initDataTable(selector, options = {}) {
    const defaults = {
        language: { search: '', searchPlaceholder: 'Search...', lengthMenu: 'Show _MENU_' },
        pageLength: 15,
        responsive: true,
        order: [[0, 'desc']],
    };
    $(selector).DataTable({ ...defaults, ...options });
}

// Toast notification
function showToast(message, type = 'success') {
    const toastEl = document.createElement('div');
    const icon = type === 'success' ? 'check-circle-fill' : (type === 'error' ? 'x-circle-fill' : 'info-circle-fill');
    const bg = type === 'success' ? 'bg-success' : (type === 'error' ? 'bg-danger' : 'bg-info');
    toastEl.innerHTML = `
        <div class="toast align-items-center text-white ${bg} border-0 show" role="alert" style="min-width:280px">
            <div class="d-flex">
                <div class="toast-body"><i class="bi bi-${icon} me-2"></i>${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>`;

    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.style.cssText = 'position:fixed;bottom:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:8px;';
        document.body.appendChild(container);
    }
    container.appendChild(toastEl);
    setTimeout(() => { toastEl.remove(); }, 4000);
}

// Confirm dialog
function confirmAction(message, callback) {
    if (confirm(message)) callback();
}

// CSRF token helper
function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
}

// Modal-based AJAX Delete (with CSRF protection)
function ajaxDelete(url, id, rowSelector, message) {
    message = message || 'Are you sure you want to delete this record? This cannot be undone.';
    const modal = document.getElementById('deleteModal');
    const msgEl = document.getElementById('deleteModalMessage');
    const confirmBtn = document.getElementById('deleteModalConfirm');
    if (!modal || !confirmBtn) {
        // Fallback if modal not available
        if (!confirm(message)) return;
        _doDelete(url, id, rowSelector);
        return;
    }
    if (msgEl) msgEl.textContent = message;
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
    const handler = () => {
        confirmBtn.removeEventListener('click', handler);
        bsModal.hide();
        _doDelete(url, id, rowSelector);
    };
    confirmBtn.addEventListener('click', handler);
}

function _doDelete(url, id, rowSelector) {
    const btn = document.getElementById('deleteModalConfirm');
    if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Deleting...'; }
    fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id=${encodeURIComponent(id)}&action=delete&csrf_token=${encodeURIComponent(getCsrfToken())}`
    })
    .then(r => r.json())
    .then(data => {
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-trash me-1"></i>Delete'; }
        if (data.success) {
            const row = document.querySelector(rowSelector);
            if (row) { row.style.transition = 'opacity 0.3s'; row.style.opacity = '0'; setTimeout(() => row.remove(), 300); }
            showToast(data.message || 'Deleted successfully');
        } else {
            showToast(data.message || 'Error deleting record', 'error');
        }
    })
    .catch(() => {
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-trash me-1"></i>Delete'; }
        showToast('Network error. Please try again.', 'error');
    });
}


// Format currency
function formatCurrency(amount) {
    return '₹' + parseFloat(amount || 0).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

// Number format
function numFmt(n) {
    return parseFloat(n || 0).toFixed(2);
}

// PWA Service Worker registration
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/service-worker.js', { scope: '/' })
            .then(reg => console.log('[SW] Registered, scope:', reg.scope))
            .catch(err => console.warn('[SW] Registration failed:', err));
    });
}

// PWA Install prompt
let deferredPrompt;
window.addEventListener('beforeinstallprompt', e => {
    e.preventDefault();
    deferredPrompt = e;
    const btn = document.getElementById('pwaInstallBtn');
    if (btn) { btn.style.display = 'inline-flex'; }
});
document.addEventListener('click', e => {
    if (e.target && e.target.id === 'pwaInstallBtn' && deferredPrompt) {
        deferredPrompt.prompt();
        deferredPrompt.userChoice.then(() => { deferredPrompt = null; });
    }
});
