# ============================================
# Configurazione PATH Permanente per Laragon
# ============================================
# Questo script aggiunge PHP e Composer di Laragon al PATH utente
# Eseguire: .\setup-path-permanent.ps1
# ============================================

Write-Host "Configurazione PATH permanente per Laragon..." -ForegroundColor Cyan
Write-Host ""

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
Write-Host ""

# Ottieni PATH utente corrente
$currentPath = [Environment]::GetEnvironmentVariable("Path", "User")

# Aggiungi PHP se non presente
if ($currentPath -notlike "*$phpPath*") {
    $currentPath += ";$phpPath"
    Write-Host "Aggiungo PHP al PATH..." -ForegroundColor Yellow
} else {
    Write-Host "PHP già presente nel PATH" -ForegroundColor Yellow
}

# Aggiungi Composer se non presente
if ($currentPath -notlike "*$composerPath*") {
    $currentPath += ";$composerPath"
    Write-Host "Aggiungo Composer al PATH..." -ForegroundColor Yellow
} else {
    Write-Host "Composer già presente nel PATH" -ForegroundColor Yellow
}

# Salva PATH aggiornato
[Environment]::SetEnvironmentVariable("Path", $currentPath, "User")

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "PATH configurato permanentemente!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Percorsi aggiunti al PATH utente:" -ForegroundColor Yellow
Write-Host "  - $phpPath" -ForegroundColor White
Write-Host "  - $composerPath" -ForegroundColor White
Write-Host ""
Write-Host "IMPORTANTE:" -ForegroundColor Red
Write-Host "  Riavvia il terminale PowerShell per applicare le modifiche!" -ForegroundColor Red
Write-Host ""
Write-Host "Dopo il riavvio, verifica con:" -ForegroundColor Yellow
Write-Host "  php -v" -ForegroundColor White
Write-Host "  composer --version" -ForegroundColor White
Write-Host ""
