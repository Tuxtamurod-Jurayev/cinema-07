@echo off
chcp 65001 >nul
title Cinema Telegram Bot (@kinolar_07_bot)

echo ======================================================
echo       KINO TELEGRAM BOT ISHGA TUSHIRILMOQDA
echo ======================================================

:: MySQL tekshirish
tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe">NUL
if "%ERRORLEVEL%"=="1" (
    echo [1/3] MySQL ishga tushirilmoqda...
    start /B "" "C:\xampp\mysql\bin\mysqld.exe" --standalone
    timeout /t 2 >nul
) else (
    echo [1/3] MySQL allaqachon faol.
)

:: PHP Web server tekshirish / ishga tushirish
echo [2/3] PHP Web Server tekshirilmoqda (127.0.0.1:8000)...
netstat -ano | findstr "8000" >nul
if "%ERRORLEVEL%"=="1" (
    start /B php -S 127.0.0.1:8000
    timeout /t 1 >nul
)

:: Polling worker ishga tushirish
echo [3/3] Telegram Polling boshlandi!
echo.
echo Bot xabarlarni qabul qilishga tayyor. Oynani yopmang!
echo To'xtatish uchun: Ctrl + C
echo.

php worker.php
pause
