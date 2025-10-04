# Point-Of-Sale

A lightweight POS system built with PHP and MySQL.

## Features

- Product catalog with search
- Shopping cart management
- Customer management
- Sales tracking
- Stock management
- Sales reporting

## Tech Stack

- PHP
- MySQL
- CSS3
- JavaScript
- Font Awesome

## Setup

1. Import database:
   ```bash
   # Option 1: Import SQL file
   mysql -u root -p < utility/database/pos.sql

   # Option 2: Run setup script
   php utility/database/setup.php
   ```

2. Configure database connection in `includes/db_connection.php`

3. Start server:
   ```bash
   php -S localhost:8000
   ```

## Structure

```
├── customer/      # Customer management
├── sale/          # Sales processing
├── stock/         # Inventory management
├── reporting/     # Sales reports
├── utility/       # Database & utilities
└── public/        # Assets (CSS, JS)
```
