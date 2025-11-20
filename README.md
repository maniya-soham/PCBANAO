# PCBANAO P2 (PHP demo)

A lightweight Amazon-style marketplace for PC parts with:
- User: signup/login, browse, cart/checkout, Build-PC wizard (compatibility filters), save builds, order history.
- Seller: login, add/update/delete products, upload images, see orders containing their items, revenue summary.
- Admin: approve sellers, manage orders & statuses (packed/shipped/delivered/canceled), direct table viewer.

## Quick Start (SQLite, no config)
1) Install PHP 8+ with pdo_sqlite extension enabled.
2) Unzip and run:  
   `php -S localhost:8000 -t pcbanaop2`
3) Open http://localhost:8000
4) Demo accounts after first launch (auto seeded):
   - Admin: `admin@pcbanao.local` / `admin123`
   - Seller: `seller@pcbanao.local` / `seller123`

## Switch to MySQL
Set environment variables and DB_DRIVER:
```
export DB_DRIVER=mysql
export DB_HOST=127.0.0.1
export DB_NAME=pcbanaop2
export DB_USER=root
export DB_PASS=secret
```
Create schema by importing `schema.sql` manually, then start PHP.

## Notes
- Uploaded images saved to `/uploads`.
- Product attributes (JSON) power compatibility and sorting (socket, ram_type, watt, length). Add more as needed.
- Security: CSRF on forms, password hashing, basic role checks. For production, add validation, file type checks, pagination, etc.
