@echo off

REM Use Docker MySQL from this repo's docker-compose.yml (host port 3307).

REM Start stack first:  docker compose up -d

cd /d "%~dp0"

set "DB_HOST=127.0.0.1"

set "DB_PORT=3307"

set "DB_NAME=chicken_ordering"

set "DB_USER=chicken"

set "DB_PASS=chicken_secret"

powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0serve.ps1" %*

