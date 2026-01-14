@echo off
echo Setting up Material Market E-Commerce Database Tables...
echo.

REM Navigate to XAMPP MySQL bin directory
cd C:\xampp\mysql\bin

REM Execute SQL script
mysql -u root -P 3307 constructa < "C:\xampp\htdocs\Constructa\sql\create_orders_tables.sql"

if %ERRORLEVEL% == 0 (
    echo.
    echo SUCCESS: Database tables created successfully!
    echo - material_orders table created
    echo - material_order_items table created
) else (
    echo.
    echo ERROR: Failed to create database tables.
    echo Please check your MySQL connection and try again.
)

echo.
pause
