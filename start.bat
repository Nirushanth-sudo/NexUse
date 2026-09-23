@echo off
REM ---------------------------------------------------------------
REM  NexUse — start the development server
REM
REM  Serves the application at http://localhost:8000
REM
REM  Only public/ is exposed to the web. Every request goes through
REM  public/index.php (the front controller), which is what the MVC
REM  structure relies on. public/router.php lets PHP's built-in
REM  server deliver real files (CSS, JS, uploads) directly.
REM
REM  Press Ctrl+C in this window to stop it.
REM ---------------------------------------------------------------

cd /d "%~dp0"

echo.
echo   NexUse is starting...
echo   Open http://localhost:8000 in your browser.
echo   Press Ctrl+C to stop.
echo.

php -S localhost:8000 -t "%~dp0public" "%~dp0public\router.php"
