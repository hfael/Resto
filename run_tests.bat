@echo off
if not exist .venv\Scripts\python.exe (
    echo Virtualenv not found. Run scripts\setup_venv_windows.bat first.
    exit /b 1
)
.venv\Scripts\python.exe -m pytest -q %*
