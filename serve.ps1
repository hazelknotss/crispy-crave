# Crispy Crave - local dev server for your phone on the same Wi-Fi.
# Default port 8888 avoids WSL/Docker Apache often bound to 8080 (which shows "Apache/2.4 Debian" and 403).
# Usage:  powershell -ExecutionPolicy Bypass -File .\serve.ps1
#         powershell -ExecutionPolicy Bypass -File .\serve.ps1 -Port 9000
param(
    [int]$Port = 8888
)

$ErrorActionPreference = 'Stop'
$root = $PSScriptRoot
if (-not $root) {
    $root = (Get-Location).Path
}

function Resolve-PhpExe {
    $fromPath = Get-Command php -ErrorAction SilentlyContinue
    if ($fromPath) {
        return $fromPath.Source
    }
    $fixed = @(
        'C:\xampp\php\php.exe',
        'C:\wamp64\bin\php\php8.3.0\php.exe',
        'C:\wamp64\bin\php\php8.2.0\php.exe'
    )
    foreach ($p in $fixed) {
        if (Test-Path -LiteralPath $p) {
            return $p
        }
    }
    $laragon = @(
        "$env:LOCALAPPDATA\laragon\bin\php",
        'C:\laragon\bin\php'
    )
    foreach ($base in $laragon) {
        if (-not (Test-Path -LiteralPath $base)) {
            continue
        }
        $exe = Get-ChildItem -Path $base -Filter 'php.exe' -Recurse -ErrorAction SilentlyContinue |
            Sort-Object FullName -Descending |
            Select-Object -First 1
        if ($exe) {
            return $exe.FullName
        }
    }
    return $null
}

$phpExe = Resolve-PhpExe
if (-not $phpExe) {
    Write-Host 'PHP was not found. Add PHP to PATH, or install XAMPP / Laragon / WAMP.' -ForegroundColor Red
    exit 1
}

Write-Host ''
Write-Host '  IMPORTANT:' -ForegroundColor Yellow
Write-Host '  If the browser says "Apache/2.4.67 (Debian)" you are NOT using this PHP server.' -ForegroundColor Yellow
Write-Host '  Something else (often WSL or Docker) owns that port. Use ONLY the URLs below.' -ForegroundColor Yellow
Write-Host ''
Write-Host "  PHP: $phpExe" -ForegroundColor DarkGray
Write-Host "  Root: $root" -ForegroundColor DarkGray
Write-Host "  Listening on all interfaces, port $Port (PHP built-in, not Apache)" -ForegroundColor Cyan
Write-Host ''
Write-Host '  On this computer:' -ForegroundColor Green
Write-Host "    http://127.0.0.1:$Port/" -ForegroundColor White
Write-Host ''
Write-Host '  On your phone (same Wi-Fi), open one of these in the browser app:' -ForegroundColor Green
try {
    Get-NetIPAddress -AddressFamily IPv4 -ErrorAction Stop |
        Where-Object {
            $_.IPAddress -notmatch '^(127\.|169\.254\.)'
        } |
        Sort-Object InterfaceMetric -ErrorAction SilentlyContinue |
        ForEach-Object {
            Write-Host ("    http://{0}:{1}/" -f $_.IPAddress, $Port) -ForegroundColor Yellow
        }
} catch {
    Write-Host '    (Could not list IPs - run ipconfig and use your Wi-Fi IPv4 address.)' -ForegroundColor Yellow
}
Write-Host ''
Write-Host '  URLs are not PowerShell commands. Paste them into Chrome / Samsung Internet / etc.' -ForegroundColor DarkGray
Write-Host "  Tip: to open this PC in a browser from here, run:  Start-Process `"http://127.0.0.1:$Port/`"" -ForegroundColor DarkGray
Write-Host ''
Write-Host '  Start MySQL in XAMPP, OR if you use Docker from this repo:  serve-docker-db.bat' -ForegroundColor DarkGray
Write-Host '  Test:  php db\ping-mysql.php  (set DB_* first if not using serve-docker-db.bat)' -ForegroundColor DarkGray
Write-Host '  If the phone cannot connect to this site, allow TCP port' $Port 'in Windows Firewall.' -ForegroundColor DarkGray
Write-Host ''
Write-Host '  If the phone shows 403 Forbidden (Apache on another device):' -ForegroundColor Yellow
Write-Host '    Use the http://YOUR-PC-IP:PORT shown above (this script uses PHP, not Apache).' -ForegroundColor DarkGray
Write-Host '    Or fix Apache on the machine that serves that site.' -ForegroundColor DarkGray
Write-Host ''
Write-Host '  Ctrl+C to stop.' -ForegroundColor DarkGray
Write-Host ''

Set-Location -LiteralPath $root
& $phpExe -S "0.0.0.0:$Port" -t $root
