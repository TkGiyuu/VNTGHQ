# VNTG HQ — MySQL Setup Guide

## Requirements
- XAMPP (Apache + MySQL) running on your computer
- PHP 8.0+ (included with XAMPP)

---

## Step 1: Start XAMPP
1. Open XAMPP Control Panel
2. Click **Start** next to **Apache**
3. Click **Start** next to **MySQL**

---

## Step 2: Create the Database (One-time only)
Visit this URL in your browser:
```
http://localhost/vntghq/setup.php
```
This automatically creates the `vntghq` database and all 6 tables:
- `users` — accounts, social login links
- `cart` — cart items per user
- `favourites` — saved/wishlisted items
- `orders` — placed orders
- `order_items` — individual items in each order
- `feedback` — user ratings and messages

**⚠️ Delete or rename `setup.php` after running it.**

---

## Step 3: (Optional) Change Database Credentials
If your MySQL has a different username or password, open `db.php` and update:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');   // your MySQL username
define('DB_PASS', '');       // your MySQL password (blank for XAMPP default)
define('DB_NAME', 'vntghq');
```

---

## Step 4: (Optional) Google / Facebook Social Login
Social login uses Firebase Authentication only for the OAuth popup.
The user data is then saved to **MySQL** (not Firebase).

To enable it:
1. Create a free project at https://console.firebase.google.com
2. Register a Web App and copy the config
3. Enable Google and/or Facebook in Authentication → Sign-in method
4. Add `localhost` to Authorized Domains
5. Paste your config into `firebase-config.js`

Without this step, username/password login works perfectly.

---

## What's stored in MySQL

| Table | Data |
|---|---|
| `users` | Username, name, email, hashed password, photo |
| `cart` | Items added to cart (synced on every change) |
| `favourites` | Wishlisted items (managed via localStorage) |
| `orders` | Full order: shipping, payment method, total |
| `order_items` | Each product line in an order |
| `feedback` | Star rating + message submitted from Profile |

---

## Viewing Your Data
Open **phpMyAdmin** at: `http://localhost/phpmyadmin`
Select the `vntghq` database to see all tables and rows.
