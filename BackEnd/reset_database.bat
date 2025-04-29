@echo off
chcp 65001 > nul
echo ============================================
echo         XOA VA TAO LAI DATABASE
echo ============================================
echo.

REM Thay doi cac thong so ket noi MySQL tai day
set DB_USER=root
set DB_PASS=123456
set DB_NAME=appchat

REM Thay doi duong dan den MySQL neu can
set MYSQL_PATH="C:\Program Files\MySQL\MySQL Server 9.2\bin\mysql.exe"

echo [%time%] Dang xoa database...
%MYSQL_PATH% -u%DB_USER% -p%DB_PASS% -e "DROP DATABASE IF EXISTS %DB_NAME%;"

echo [%time%] Dang tao lai database...
%MYSQL_PATH% -u%DB_USER% -p%DB_PASS% < config/create_database.sql

echo [%time%] Dang tao admin mac dinh...
php config/create_admin.php

echo.
echo ============================================
echo         DA XOA VA TAO LAI DATABASE THANH CONG!
echo ============================================
echo.
pause 