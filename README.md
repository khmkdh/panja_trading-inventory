# 🔧 Panja Trading — Inventory Management System

A web-based Inventory Management System built for **Panja Trading**, a bike repair workshop. The system handles spare parts tracking, sales recording, workshop usage, and order management — all with role-based access control for streamlined operations.

---

## 📋 Features

- 🔐 **Authentication** — Secure login/logout with role-based access
- 📦 **Inventory Management** — Track parts, categories, and stock levels
- 🛒 **Sales Management** — Record and manage sales transactions
- 🔩 **Workshop Usage Tracking** — Log parts used during bike repairs
- 🚲 **Bike & Customer Records** — Manage bike entries and customer data
- 📊 **Reports & Dashboard** — Overview of stock, sales, and usage
- 🏪 **Warehouse Management** — Separate warehouse item tracking
- 🖨️ **Bill Printing** — Generate and print invoices

---

## 🛠️ Tech Stack

| Layer      | Technology        |
|------------|-------------------|
| Backend    | PHP               |
| Database   | MySQL (via XAMPP) |
| Frontend   | HTML, CSS         |
| Server     | Apache (XAMPP)    |

---

## 🚀 Getting Started (Local Setup)

### Prerequisites
- [XAMPP](https://www.apachefriends.org/) installed
- PHP 7.4+
- MySQL

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/khmkdh/panja_trading-inventory.git
   ```

2. **Move to XAMPP's htdocs folder**
   ```
   C:\xampp\htdocs\panja_inventory\
   ```

3. **Import the database**
   - Open [phpMyAdmin](http://localhost/phpmyadmin)
   - Create a new database (e.g., `panja_inventory`)
   - Import the provided SQL file

4. **Configure the database connection**
   - Open `config.php`
   - Update the DB credentials:
     ```php
     $host = "localhost";
     $user = "root";
     $password = "";
     $database = "panja_inventory";
     ```

5. **Start Apache & MySQL** in XAMPP Control Panel

6. **Visit** `http://localhost/panja_inventory/login.php`

---

## 📁 Project Structure

```
panja_inventory/
├── config.php              # Database configuration
├── login.php / logout.php  # Authentication
├── dashboard.php           # Main dashboard
├── inventory.php           # Inventory overview
├── items.php               # Item management
├── parts.php               # Parts management
├── category.php            # Category management
├── sales.php               # Sales records
├── customer.php            # Customer records
├── bikes.php               # Bike records
├── workshop_usage.php      # Workshop usage log
├── add_workshop_usage.php  # Add workshop usage
├── warehouse_items.php     # Warehouse items
├── add_stock.php           # Add stock
├── view_stock.php          # View stock
├── get_stock.php           # Fetch stock (API)
├── report.php              # Reports
├── print_bill.php          # Bill printing
├── search.php              # Search functionality
├── Storage.php             # Storage helper
└── styles.css              # Stylesheet
```

---

## 👤 Author

**Khyati** — [GitHub](https://github.com/khmkdh) | [Portfolio](https://khmkdh-portfolio-e08606.netlify.app)

---

## 📄 License

This project is for internal/educational use for Panja Trading.