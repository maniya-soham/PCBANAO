<?php
// Path to your SQLite database file
$dbFile = __DIR__ . "/storage/app.db";

try {
    // Connect to SQLite
    $pdo = new PDO("sqlite:" . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // SQL query to create Amazon-style addresses table
    $sql = "
    CREATE TABLE IF NOT EXISTS addresses (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        label TEXT,                   -- Example: Home, Work
        recipient_name TEXT NOT NULL, -- Who will receive the parcel
        phone TEXT NOT NULL,          -- Contact number
        street_address TEXT NOT NULL, -- House / Flat / Apartment
        area TEXT,                    -- Area / Colony / Locality
        city TEXT NOT NULL,
        state TEXT NOT NULL,
        postal_code TEXT NOT NULL,    -- PIN code
        country TEXT DEFAULT 'India',
        is_default INTEGER DEFAULT 0, -- 1 if default address
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(user_id) REFERENCES users(id)
    );";

    // Run query
    $pdo->exec($sql);

    echo "<h2>✅ Addresses table created successfully in app.db</h2>";
} catch (Exception $e) {
    echo "<h2>❌ Error: " . $e->getMessage() . "</h2>";
}
