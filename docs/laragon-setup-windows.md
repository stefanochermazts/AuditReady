# Configurazione Laragon su Windows per AuditReady

## Problema

Laragon installa PHP e Composer, ma spesso non vengono aggiunti automaticamente al PATH di Windows, rendendoli non disponibili dal prompt dei comandi.

## Soluzione Rapida (Sessione Corrente)

Esegui lo script `setup-laragon-path.ps1` nella root del progetto:

```powershell
.\setup-laragon-path.ps1
```

Questo aggiunge PHP e Composer al PATH solo per la sessione PowerShell corrente.

## Soluzione Permanente

### Metodo 1: Tramite Interfaccia Windows (Raccomandato)

1. **Apri Variabili d'Ambiente**:
   - Premi `Win + R`
   - Digita `sysdm.cpl` e premi Invio
   - Vai alla tab **Avanzate**
   - Clicca **Variabili d'ambiente**

2. **Modifica PATH**:
   - Nella sezione **Variabili di sistema**, trova `Path`
   - Clicca **Modifica**
   - Clicca **Nuovo** e aggiungi:
     ```
     C:\laragon\bin\php\php-8.3.28-Win32-vs16-x64
     ```
   - Clicca **Nuovo** e aggiungi:
     ```
     C:\laragon\bin\composer
     ```
   - Clicca **OK** su tutte le finestre

3. **Riavvia il Terminale**:
   - Chiudi tutti i terminali aperti
   - Apri un nuovo PowerShell/CMD
   - Verifica con:
     ```powershell
     php -v
     composer --version
     ```

### Metodo 2: Tramite PowerShell (Amministratore)

```powershell
# Apri PowerShell come Amministratore (Win + X, A)

# Trova la versione PHP installata
$phpVersion = Get-ChildItem "C:\laragon\bin\php" -Directory | Select-Object -First 1 -ExpandProperty Name
$phpPath = "C:\laragon\bin\php\$phpVersion"
$composerPath = "C:\laragon\bin\composer"

# Aggiungi al PATH di sistema
[Environment]::SetEnvironmentVariable(
    "Path",
    [Environment]::GetEnvironmentVariable("Path", "Machine") + ";$phpPath;$composerPath",
    "Machine"
)

Write-Host "PATH aggiornato. Riavvia il terminale." -ForegroundColor Green
```

### Metodo 3: Usa Laragon Terminal

Laragon include un terminale pre-configurato:

1. Apri **Laragon**
2. Clicca **Terminal** nella barra degli strumenti
3. Questo terminale ha già PHP e Composer nel PATH

## Verifica Configurazione

Dopo aver configurato il PATH, verifica:

```powershell
# Verifica PHP
php -v
# Dovrebbe mostrare: PHP 8.3.28 (o versione installata)

# Verifica Composer
composer --version
# Dovrebbe mostrare: Composer version 2.8.4 (o versione installata)

# Verifica percorsi
where php
where composer
```

## Percorsi Standard Laragon

Laragon installa tipicamente in:

- **PHP**: `C:\laragon\bin\php\php-{versione}-Win32-vs16-x64`
- **Composer**: `C:\laragon\bin\composer`
- **MySQL**: `C:\laragon\bin\mysql\mysql-{versione}\bin`
- **PostgreSQL**: `C:\laragon\bin\postgresql\pgsql-{versione}\bin`

## Troubleshooting

### PHP non trovato

```powershell
# Verifica che PHP sia installato
Test-Path C:\laragon\bin\php

# Lista versioni PHP disponibili
Get-ChildItem C:\laragon\bin\php -Directory
```

### Composer non trovato

```powershell
# Verifica che Composer sia installato
Test-Path C:\laragon\bin\composer\composer.bat
```

### PATH non si aggiorna

1. **Riavvia il computer** (a volte necessario)
2. **Verifica PATH**:
   ```powershell
   $env:PATH -split ';' | Select-String laragon
   ```
3. **Usa Laragon Terminal** come alternativa temporanea

### Versione PHP diversa

Se hai una versione PHP diversa, aggiorna il percorso nel PATH:

```powershell
# Trova la versione installata
Get-ChildItem C:\laragon\bin\php -Directory | Select-Object Name
```

Poi usa il percorso completo nella configurazione PATH.

## Script Setup Automatico

Lo script `setup-laragon-path.ps1` nella root del progetto:

- Trova automaticamente la versione PHP installata
- Aggiunge PHP e Composer al PATH della sessione corrente
- Testa che tutto funzioni
- Fornisce istruzioni per configurazione permanente

Esegui ogni volta che apri un nuovo terminale, oppure configura permanentemente come descritto sopra.

## Alternative

### Usa Laragon Terminal

Laragon include un terminale pre-configurato che ha già tutto nel PATH:
- Apri Laragon
- Clicca **Terminal**
- PHP e Composer sono già disponibili

### Usa Git Bash (se installato)

Git Bash spesso ha PHP e Composer configurati se installati tramite Laragon.

### Usa WSL (Windows Subsystem for Linux)

Se preferisci un ambiente Linux:
```bash
# Installa PHP e Composer in WSL
sudo apt update
sudo apt install php php-cli composer
```

## Best Practices

1. **Configura PATH permanentemente** per evitare di dover eseguire lo script ogni volta
2. **Usa Laragon Terminal** per sviluppo rapido
3. **Verifica versioni** prima di iniziare a lavorare:
   ```powershell
   php -v
   composer --version
   ```

## Riferimenti

- [Laragon Documentation](https://laragon.org/docs/)
- [Windows PATH Configuration](https://docs.microsoft.com/en-us/windows/win32/procthread/environment-variables)

---

**Nota**: Dopo aver configurato il PATH, riavvia sempre il terminale per applicare le modifiche.
