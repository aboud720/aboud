# Database Configuration

## Setup Instructions

1. **Copy the example configuration file:**
   ```bash
   cp config/database.example.php config/database.php
   ```

2. **Edit the database credentials:**
   Open `config/database.php` and update the following constants with your database information:
   - `DB_HOST`: Database host (default: localhost)
   - `DB_USER`: Your database username
   - `DB_PASS`: Your database password
   - `DB_NAME`: Your database name
   - `DB_CHARSET`: Character set (default: utf8mb4)

3. **Security Note:**
   - The `database.php` file is excluded from version control via `.gitignore`
   - Never commit actual credentials to the repository
   - Only commit the `database.example.php` template file

## Usage

To use the database connection in your PHP files:

```php
<?php
// Include the database configuration
require_once __DIR__ . '/../config/database.php';

// Get the database connection
$db = getDBConnection();

// Example query
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([1]);
$user = $stmt->fetch();
?>
```

## Features

- **Singleton Pattern:** Ensures only one database connection is created and reused
- **PDO with Prepared Statements:** Protection against SQL injection
- **Error Handling:** Exceptions enabled for better error management
- **UTF-8 Support:** Full Unicode support with utf8mb4 charset
