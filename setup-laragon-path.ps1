# ============================================
# Laragon PATH Setup Script for AuditReady
# ============================================
# Questo script aggiunge PHP e Composer di Laragon al PATH
# Eseguire: .\setup-laragon-path.ps1
# ============================================

Write-Host "Configurazione PATH per Laragon..." -ForegroundColor Cyan

# Trova la versione PHP installata
$phpPath = Get-ChildItem "C:\laragon\bin\php" -Directory | Select-Object -First 1 -ExpandProperty FullName
$composerPath = "C:\laragon\bin\composer"

if (-not (Test-Path $phpPath)) {
    Write-Host "ERRORE: PHP non trovato in C:\laragon\bin\php" -ForegroundColor Red
    exit 1
}

if (-not (Test-Path $composerPath)) {
    Write-Host "ERRORE: Composer non trovato in C:\laragon\bin\composer" -ForegroundColor Red
    exit 1
}

Write-Host "PHP trovato: $phpPath" -ForegroundColor Green
Write-Host "Composer trovato: $composerPath" -ForegroundColor Green

# Aggiungi al PATH della sessione corrente
$env:PATH += ";$phpPath;$composerPath"

Write-Host "`nPATH aggiornato per questa sessione PowerShell" -ForegroundColor Yellow

# Test
Write-Host "`nTestando PHP..." -ForegroundColor Cyan
php -v

Write-Host "`nTestando Composer..." -ForegroundColor Cyan
composer --version

Write-Host "`n============================================" -ForegroundColor Cyan
Write-Host "IMPORTANTE: Questa modifica è valida solo per questa sessione PowerShell" -ForegroundColor Yellow
Write-Host "Per aggiungere permanentemente al PATH di Windows:" -ForegroundColor Yellow
Write-Host "1. Apri 'Variabili d'ambiente' (Win + R, sysdm.cpl, Avanzate)" -ForegroundColor White
Write-Host "2. Modifica la variabile PATH" -ForegroundColor White
Write-Host "3. Aggiungi: $phpPath" -ForegroundColor White
Write-Host "4. Aggiungi: $composerPath" -ForegroundColor White
Write-Host "5. Riavvia il terminale" -ForegroundColor White
Write-Host "============================================" -ForegroundColor Cyan
