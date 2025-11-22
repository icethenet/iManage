@echo off
echo Creating landing_pages table...
php tools\create_landing_pages_table.php
if %ERRORLEVEL% EQU 0 (
    echo.
    echo Success! Table created.
    echo.
    echo Now you can use the GrapesJS feature:
    echo 1. Login to iManage
    echo 2. Share an image
    echo 3. Visit the share page
    echo 4. Look for the "Design Landing Page" button
) else (
    echo.
    echo Error creating table. Make sure MySQL is running.
)
pause
