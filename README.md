# LUXE Shop — Setup Guide

## Requirements
- PHP 8.0+
- MySQL 5.7+ or MariaDB 10.3+
- A web server: Apache (with mod_rewrite) or Nginx

---

## File Structure
```
luxe-shop/
├── index.html          ← Shop frontend
├── style.css           ← Shop styles
├── script.js           ← Shop JS (calls PHP API)
├── config.php          ← DB credentials
├── auth.php            ← Admin session helper
├── database.sql        ← Run once to create tables + seed data
│
├── api/
│   ├── products.php    ← REST API: GET/POST/PUT/DELETE products
│   └── orders.php      ← REST API: GET/POST/PUT/DELETE orders
│
└── admin/
    ├── login.php       ← Admin login page
    ├── logout.php      ← Clears session
    ├── index.php       ← Dashboard (stats, recent orders, low stock)
    ├── products.php    ← Manage products (add/edit/delete/filter)
    ├── orders.php      ← Manage orders (update status/filter)
    ├── admin.css       ← Admin styles
    └── partials/
        └── nav.php     ← Shared navigation bar
```

---

## Setup Steps

### 1. Create the database
Open MySQL/phpMyAdmin and run the contents of `database.sql`.
This creates the `luxe_shop` database, all tables, and seeds sample data.

### 2. Configure database credentials
Edit `config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'luxe_shop');
define('DB_USER', 'your_mysql_username');
define('DB_PASS', 'your_mysql_password');
```

### 3. Place files on your server
Copy the entire `luxe-shop/` folder to your web root (e.g. `/var/www/html/` or `htdocs/`).

### 4. Configure Apache (if using .htaccess)
Create a `.htaccess` in the root:
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^ index.html [L]
```

### 5. Open the site
- **Shop:** `http://localhost/`
- **Admin:** `http://localhost/admin/`
  - Username: `admin`
  - Password: `admin123`

---

## API Endpoints

| Method | URL | Description |
|--------|-----|-------------|
| GET | /api/products.php | List all products |
| GET | /api/products.php?category=clothing | Filter by category |
| GET | /api/products.php?id=1 | Single product |
| POST | /api/products.php | Create product |
| PUT | /api/products.php?id=1 | Update product |
| DELETE | /api/products.php?id=1 | Delete product |
| GET | /api/orders.php | List all orders |
| GET | /api/orders.php?id=1 | Order + items |
| POST | /api/orders.php | Checkout (creates order) |
| PUT | /api/orders.php?id=1 | Update order status |

### POST /api/orders.php — Checkout payload
```json
{
  "customer_name": "Jane Doe",
  "customer_email": "jane@example.com",
  "items": [
    { "product_id": 1, "quantity": 2 },
    { "product_id": 4, "quantity": 1 }
  ]
}
```

---

## Changing Admin Password
Run this in PHP to generate a new hash:
```php
echo password_hash('your_new_password', PASSWORD_DEFAULT);
```
Then update the `admin_users` table in MySQL.
