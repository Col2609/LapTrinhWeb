@echo off
chcp 65001 > nul
echo ============================================
echo         KHOI DONG SERVER CHAT APP
echo ============================================
echo.

REM Thay doi cac thong so ket noi MySQL tai day
set DB_USER=root
set DB_PASS=123456
set DB_NAME=appchat

REM Thay doi duong dan den MySQL neu can
set MYSQL_PATH="C:\Program Files\MySQL\MySQL Server 9.2\bin\mysql.exe"

echo [%time%] Dang tao database...
%MYSQL_PATH% -u%DB_USER% -p%DB_PASS% < config/create_database.sql

echo [%time%] Dang tao admin mac dinh...
php config/create_admin.php
echo.

echo [%time%] Dang khoi dong server...
echo ============================================
php -S localhost:8000 