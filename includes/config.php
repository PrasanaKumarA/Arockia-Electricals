<?php
// ============================================================
// Arockia Electricals - Application Configuration
// ============================================================

// --- Database Configuration ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'arockia_electricals');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// --- Application Settings ---
define('APP_NAME', 'Arockia Electricals');
define('APP_TAGLINE', 'Your Trusted Electrical Partner');
define('APP_URL', 'http://localhost/Arockia-Electricals');
define('APP_VERSION', '1.0.0');
define('APP_CURRENCY', '₹');
define('APP_CURRENCY_CODE', 'INR');

// --- GST Settings ---
define('GST_ENABLED', true);
define('DEFAULT_GST_RATE', 18); // percentage

// --- Invoice Settings ---
define('INVOICE_PREFIX', 'INV');
define('PURCHASE_PREFIX', 'PUR');

// --- Session Settings ---
define('SESSION_TIMEOUT', 1800); // 30 minutes in seconds
define('SESSION_NAME', 'arockia_session');

// --- SMTP / Email Settings ---
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('SMTP_FROM_EMAIL', 'your-email@gmail.com');
define('SMTP_FROM_NAME', APP_NAME);

// --- Company Details (for PDF invoice) ---
define('COMPANY_NAME', 'Arockia Electricals');
define('COMPANY_ADDRESS', '123 Main Street, Coimbatore - 641001, Tamil Nadu');
define('COMPANY_PHONE', '+91 98765 43210');
define('COMPANY_EMAIL', 'info@arockiaelectricals.com');
define('COMPANY_GSTIN', '33AABCA1234A1Z5');
define('COMPANY_WEBSITE', 'www.arockiaelectricals.com');

// --- WhatsApp Settings ---
define('WHATSAPP_NUMBER', '919876543210'); // with country code, no +

// --- low stock alert threshold ---
define('LOW_STOCK_THRESHOLD', 5);

// --- Timezone ---
date_default_timezone_set('Asia/Kolkata');

// --- Error Reporting (set to 0 in production) ---
error_reporting(E_ALL);
ini_set('display_errors', 1);
