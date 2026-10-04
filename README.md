# Library Excel Import

PHP application that imports a library catalog from Excel into MySQL. The active sheet of an `.xls` or `.xlsx` file is saved to the `books` table.

## Requirements

- PHP 8.0 or newer, with the `fileinfo` and `mbstring` extensions
- MySQL or MariaDB
- Composer

## Setup

```bash
git clone https://github.com/Tessnimhdj/library_project.git
cd library_project
composer install
mysql -u root < database/schema.sql
```

Set the database host, name, username, and password in `config.php`, then open `index.php` from your PHP server.

If `books` already exists without a unique inventory number, add `UNIQUE KEY uq_inventory_number (inventory_number)` before importing.

## Excel file

The first row is a header and is ignored. Columns must be in this order:

| Column | Field | Limit |
| --- | --- | --- |
| A | Inventory number | 64 characters |
| B | Title | 255 characters |
| C | Author | 255 characters |
| D | Notes | Text |

Files are limited to 5 MB. An empty inventory number is skipped. A repeated number in the same file keeps the first row. Importing an existing number updates that book.

## License

MIT License.
