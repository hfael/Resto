@echo off
REM Zip only project files needed for debug

set ZIPNAME=resto_debug.zip

powershell -command "Compress-Archive -Path public, src, config, docker, docker-compose.yml -DestinationPath %ZIPNAME% -Force"

echo Archive créée : %ZIPNAME%
pause
