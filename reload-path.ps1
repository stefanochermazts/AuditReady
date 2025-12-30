# AuditReady - Reload PATH in Current PowerShell Session
# Usage: .\reload-path.ps1

Write-Host ""
Write-Host "=== Reloading PATH Environment Variable ===" -ForegroundColor Cyan
Write-Host ""

$machinePath = [System.Environment]::GetEnvironmentVariable("Path", "Machine")
$userPath = [System.Environment]::GetEnvironmentVariable("Path", "User")

$env:Path = $machinePath + ";" + $userPath

Write-Host "[OK] PATH reloaded successfully!" -ForegroundColor Green
Write-Host ""

# Verify Laragon paths
$laravelPaths = $env:Path -split ';' | Where-Object { $_ -like "*laragon*" }
if ($laravelPaths) {
    Write-Host "Laragon paths found:" -ForegroundColor Green
    foreach ($path in $laravelPaths) {
        Write-Host "  - $path" -ForegroundColor White
    }
} else {
    Write-Host "[WARNING] No Laragon paths found" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Testing PHP:" -ForegroundColor White
if (Get-Command php -ErrorAction SilentlyContinue) {
    $phpVersion = php -v 2>&1 | Select-Object -First 1
    Write-Host "  [OK] $phpVersion" -ForegroundColor Green
} else {
    Write-Host "  [ERROR] PHP not found" -ForegroundColor Red
}

Write-Host ""
Write-Host "Testing Composer:" -ForegroundColor White
if (Get-Command composer -ErrorAction SilentlyContinue) {
    $composerVersion = composer --version 2>&1 | Select-Object -First 1
    Write-Host "  [OK] $composerVersion" -ForegroundColor Green
} else {
    Write-Host "  [ERROR] Composer not found" -ForegroundColor Red
}

Write-Host ""
