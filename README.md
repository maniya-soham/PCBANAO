PCBANAO P2 — Custom PC Building & Component Marketplace (PHP Demo)

A lightweight platform that allows users to explore PC components, build compatible systems, place orders, and manage products.
Designed as a fully self-built project for learning, demonstration, and portfolio use.

Features

User Panel

Register and log in
Browse PC components by category
Add items to cart and checkout
Build-PC wizard with compatibility filters
Save builds and view order history

Seller Panel

Secure seller login
Add, update, or delete products
Upload product images
View orders containing their products
Revenue summary with basic analytics

Admin Panel

Approve seller registrations
Manage all orders and update statuses (packed, shipped, delivered, canceled)
Manage users, sellers, products, and categories
Direct database table viewer for debugging
Quick Start (SQLite – Zero Configuration)
Install PHP 8+ with pdo_sqlite extension enabled.

Extract the project folder and run:
php -S localhost:8000 -t pcbanaop2

Open http://localhost:8000

Default demo accounts (auto-generated on first run):

Admin	admin@pcbanao.local -	admin123
Seller	seller@pcbanao.local  -	seller123

Switching to MySQL

Set environment variables:

export DB_DRIVER=mysql
export DB_HOST=127.0.0.1
export DB_NAME=pcbanaop2
export DB_USER=root
export DB_PASS=secret


Import schema.sql into MySQL manually, then start the PHP server.

Additional Notes

Uploaded images are stored in the /uploads directory.
Product attributes are stored in JSON (e.g., socket, ram_type, watt, length).
Included security features: password hashing, basic role-based access, CSRF tokens.
For production, consider adding validation, file type filtering, pagination, and more comprehensive security checks.
