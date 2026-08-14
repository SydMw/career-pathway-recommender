@echo off
title Career Pathway Recommender - How the AI Works
echo.
echo  =============================================
echo   Career Pathway Recommender - How the AI Works
echo  =============================================
echo.
echo  This window only reads the trained model.
echo  It does not retrain anything.
echo.
cd /d "%~dp0"

python ml/model_facts.py

echo.
echo  =============================================
echo   Example 1 - a student with a clear strength
echo  =============================================
python ml/explain_prediction.py 88 60 85 55 45 technology

echo.
echo  =============================================
echo   Example 2 - a student with flat scores
echo  =============================================
python ml/explain_prediction.py 40 40 40 40 40 humanities

echo.
pause
