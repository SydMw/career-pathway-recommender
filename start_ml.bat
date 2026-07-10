@echo off
title Career Pathway Recommender - AI Engine
echo.
echo  =============================================
echo   Career Pathway Recommender - AI Engine
echo  =============================================
echo.
echo  Starting the AI recommendation engine...
echo  Keep this window open while using the system.
echo  Close it only when you are done.
echo.
cd /d "%~dp0"

:: Open the login page in the default browser
start "" "http://localhost/career_system/public/login.php"

:: Start the ML API
python ml/api.py
pause
