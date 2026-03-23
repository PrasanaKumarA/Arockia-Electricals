# ⚡ Arockia Electricals — Inventory & Billing System

A **production-ready web-based Inventory Management and Billing System** built for real-world business usage.

Designed for **Arockia Electricals**, this system helps manage products, stock, sales, invoices, and customer communication efficiently.

---

## 🛠️ Tech Stack

- **Backend:** Core PHP (Procedural with PDO)
- **Frontend:** HTML5, CSS3, Bootstrap 5, JavaScript (AJAX)
- **Database:** MySQL
- **Libraries:** FPDF (PDF), PHPMailer (Email)
- **Extras:** PWA (Progressive Web App), GitHub Actions (CI/CD)

---

## 🚀 Features

### 🔐 Authentication
- Secure login/logout
- Password hashing (bcrypt)
- Session timeout (30 mins)

### 📊 Dashboard
- Total products
- Today’s sales
- Total profit
- Low stock alerts
- Chart-based analytics

### 📦 Product Management
- Add / Edit / Delete products
- Categories: UPS, Battery, Solar, Appliance
- Stock tracking with minimum stock alerts
- Search & filter

### 🚚 Supplier Management
- Manage supplier details
- AJAX-based fast operations

### 👥 Customer Management
- Store customer details
- Quick access during billing

### 🛒 Purchase Module
- Add supplier purchases
- Automatically increases stock

### 🧾 Sales & Billing
- Multi-product invoice system
- Auto calculation (GST, discount, total)
- Automatic stock reduction
- Unique invoice generation

### 📄 Invoice System
- Professional invoice view
- PDF generation (FPDF)
- Print-ready format

### 💬 WhatsApp Integration
- One-click WhatsApp message
- Pre-filled invoice summary

### 📧 Email Integration
- Send invoice PDF via email
- SMTP-based (PHPMailer)

### 📈 Reports
- Sales report (daily/monthly)
- Profit report
- Stock report
- CSV export support

### 📱 PWA Support
- Install as mobile/desktop app
- Offline caching (static assets)
- Fast loading experience

### 🔁 CI/CD
- GitHub Actions auto deploy
- FTP deployment to InfinityFree

---

## ⚡ Quick Start (XAMPP)

### ✅ Prerequisites
- XAMPP (PHP 7.4+ / 8.x)

---

### 🧩 Step 1 — Import Database
1. Start Apache & MySQL
2. Open: http://localhost/phpmyadmin
3. Create DB: `arockia_electricals`
4. Import: `arockia_electricals.sql`

---

### 📁 Step 2 — Setup Project

Copy project to:
```
C:\xampp\htdocs\arockia-electricals
```

Open in browser:
```
http://localhost/arockia-electricals
```

---

### 🔑 Step 3 — Login

| Field | Value |
|------|------|
| Email | admin@arockia.com |
| Password | admin123 |

⚠️ Change password after first login

---

## ⚙️ Configuration

Edit:
```
includes/config.php
```

### 🔌 Database
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'arockia_electricals');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### 🏢 Company Info
```php
define('COMPANY_NAME', 'Arockia Electricals');
define('COMPANY_PHONE', '+91XXXXXXXXXX');
define('COMPANY_GSTIN', 'YOUR_GST_NUMBER');
```

---

## 📄 PDF Setup (FPDF)

Download FPDF:
👉 http://www.fpdf.org

Place:
```
libs/fpdf/fpdf.php
```

---

## 📧 Email Setup (PHPMailer)

1. Download PHPMailer:
👉 https://github.com/PHPMailer/PHPMailer

2. Place inside:
```
libs/phpmailer/
```

3. Configure SMTP:
```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'app-password');
```

---

## 💬 WhatsApp Setup

No setup needed ✅

Set your number:
```php
define('WHATSAPP_NUMBER', '919XXXXXXXXX');
```

---

## 📱 PWA Setup

- `manifest.json`
- `service-worker.js`

Replace icons:
```
assets/icons/icon-192.png
assets/icons/icon-512.png
```

---

## 🔁 CI/CD Deployment (GitHub → InfinityFree)

### Step 1 — Add GitHub Secrets

| Name | Value |
|------|------|
| FTP_SERVER | ftpupload.net |
| FTP_USERNAME | your username |
| FTP_PASSWORD | your password |

---

### Step 2 — Auto Deploy

```bash
git add .
git commit -m "deploy"
git push
```

✅ Automatically deployed to `/htdocs/`

---

## 📁 Project Structure

```
arockia-electricals/
├── index.php
├── arockia_electricals.sql
├── manifest.json
├── service-worker.js
├── .github/workflows/deploy.yml
├── includes/
├── assets/
├── auth/
├── products/
├── suppliers/
├── customers/
├── purchase/
├── sales/
├── reports/
└── libs/
```

---

## 🔐 Security Features

- Password hashing (bcrypt)
- PDO prepared statements
- Input sanitization
- Session timeout
- Session regeneration

---

## 🌐 Deployment Notes (InfinityFree)

- Update DB credentials
- Upload inside `/htdocs/`
- Set correct domain in config
- SMTP must use App Password

---

## 🌙 UI Features

- Dark mode 🌙
- Responsive design 📱
- Clean dashboard 📊

---

## 📊 Feature Summary

| Module | Features |
|--------|---------|
| Auth | Login, Logout |
| Dashboard | Stats, Charts |
| Products | CRUD, Alerts |
| Suppliers | Manage suppliers |
| Customers | Manage customers |
| Purchase | Stock In |
| Sales | Billing + Invoice |
| PDF | Invoice export |
| WhatsApp | Share invoice |
| Email | Send invoice |
| Reports | Sales, Profit, Stock |
| PWA | Installable app |
| CI/CD | Auto deploy |

---

## 👨‍💻 Author

Developed for **Arockia Electricals**  
Built with ❤️ by **Prasana Kumar**

---

## ⭐ Support

If you like this project:
👉 Star ⭐ the repo  
👉 Share it  
👉 Use it in real business 🔥