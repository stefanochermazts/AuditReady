# Strategia 2FA con Microsoft Authenticator

## Panoramica

AuditReady implementa autenticazione a due fattori (2FA) obbligatoria utilizzando **TOTP (Time-based One-Time Password)** standard, completamente compatibile con **Microsoft Authenticator** e altri authenticator apps.

## Compatibilità Microsoft Authenticator

### Standard TOTP (RFC 6238)

Microsoft Authenticator supporta lo standard **TOTP (RFC 6238)**, che è lo stesso standard utilizzato da:
- Google Authenticator
- Authy
- 1Password
- LastPass Authenticator
- E molti altri

### Formato QR Code

Il QR code generato da AuditReady segue il formato standard:
```
otpauth://totp/AuditReady:user@example.com?secret=JBSWY3DPEHPK3PXP&issuer=AuditReady
```

Questo formato è **completamente leggibile** da Microsoft Authenticator.

## Implementazione

### Libreria Utilizzata

**`pragmarx/google2fa-laravel`**: 
- Supporta TOTP standard (RFC 6238)
- Compatibile con Microsoft Authenticator
- Genera QR code standard
- Verifica codici TOTP

### Setup Utente

1. **Generazione Secret**:
   ```php
   $google2fa = app('pragmarx.google2fa');
   $secret = $google2fa->generateSecretKey();
   ```

2. **Generazione QR Code**:
   ```php
   $qrCodeUrl = $google2fa->getQRCodeUrl(
       'AuditReady',
       $user->email,
       $user->two_factor_secret
   );
   ```

3. **Verifica Codice**:
   ```php
   $valid = $google2fa->verifyKey(
       $user->two_factor_secret,
       $request->input('code')
   );
   ```

### Flusso di Configurazione

```
1. Utente accede al profilo
   ↓
2. Clicca "Abilita 2FA"
   ↓
3. Sistema genera secret TOTP
   ↓
4. Mostra QR code all'utente
   ↓
5. Utente scansiona con Microsoft Authenticator
   ↓
6. Utente inserisce codice di verifica
   ↓
7. Sistema verifica e abilita 2FA
   ↓
8. 2FA obbligatoria per login successivi
```

### Flusso di Login

```
1. Utente inserisce email/password
   ↓
2. Credenziali verificate
   ↓
3. Sistema verifica se 2FA è abilitata
   ↓
4. Se sì → Reindirizza a pagina verifica 2FA
   ↓
5. Utente inserisce codice da Microsoft Authenticator
   ↓
6. Sistema verifica codice TOTP
   ↓
7. Se valido → Login completato
   ↓
8. Se non valido → Mostra errore, permette retry
```

## Ruoli con 2FA Obbligatoria

Secondo l'analisi funzionale, 2FA è **obbligatoria** (non opzionale) per:

- ✅ **Organization Owner**
- ✅ **Audit Manager**
- ✅ **Contributor** (ruoli con upload/export)
- ⚠️ **Viewer** (opzionale ma raccomandato)
- ⚠️ **External Uploader** (gestito via token, non 2FA)

### Enforcement

```php
// Middleware RequireTwoFactor
if ($user->hasRole(['owner', 'audit_manager', 'contributor'])) {
    if (!$user->two_factor_secret) {
        return redirect()->route('2fa.setup')
            ->with('error', '2FA è obbligatoria per il tuo ruolo');
    }
    
    if (!session('2fa_verified')) {
        return redirect()->route('2fa.verify');
    }
}
```

## UI/UX per Microsoft Authenticator

### Istruzioni per Utente

```
1. Apri Microsoft Authenticator sul tuo dispositivo
2. Tocca il pulsante "+" o "Aggiungi account"
3. Seleziona "Account personale" o "Altro"
4. Scansiona il QR code mostrato sullo schermo
5. Inserisci il codice a 6 cifre mostrato nell'app
```

### Messaggi di Errore

- **Codice non valido**: "Il codice inserito non è valido. Assicurati di usare il codice più recente da Microsoft Authenticator."
- **Codice scaduto**: "Il codice è scaduto. Inserisci il nuovo codice da Microsoft Authenticator."
- **Troppi tentativi**: "Troppi tentativi falliti. Riprova tra 60 secondi."

## Recovery Codes

### Generazione Recovery Codes

```php
$recoveryCodes = collect(range(1, 8))->map(function () {
    return Str::random(10);
})->all();

$user->update([
    'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes))
]);
```

### Utilizzo Recovery Codes

Se l'utente perde accesso a Microsoft Authenticator, può usare un recovery code per:
1. Disabilitare 2FA temporaneamente
2. Configurare nuovo dispositivo
3. Recuperare accesso all'account

**Importante**: Recovery codes sono one-time use e devono essere salvati in modo sicuro.

## Sicurezza

### Storage Secret

- **Cifratura**: Secret TOTP cifrati a riposo (AES-256)
- **Database**: Colonna `two_factor_secret` cifrata
- **Backup**: Secret inclusi in backup cifrati

### Rate Limiting

- **Verifica 2FA**: Max 5 tentativi per minuto
- **Lockout**: Account bloccato dopo 10 tentativi falliti
- **Unlock**: Richiede intervento admin o recovery code

### Session Management

- **2FA Verified Flag**: Session flag `2fa_verified` dopo verifica
- **Invalidazione**: Flag invalidato su logout, cambio password, sospetta attività

## Testing

### Test Compatibilità Microsoft Authenticator

1. **Genera QR code** con secret di test
2. **Scansiona con Microsoft Authenticator** reale
3. **Verifica codice** generato dall'app
4. **Conferma compatibilità** ✅

### Test Scenari

- ✅ Login con codice valido
- ✅ Login con codice scaduto (dopo 30 secondi)
- ✅ Login con codice errato
- ✅ Rate limiting dopo troppi tentativi
- ✅ Recovery code funzionante
- ✅ Disabilitazione 2FA

## Best Practices

### 1. Backup Secret

- **Non fare screenshot** del QR code (rischio sicurezza)
- **Salvare recovery codes** in password manager
- **Non condividere** secret con altri

### 2. Multi-Device

- Microsoft Authenticator supporta **backup cloud** (opzionale)
- Utente può aggiungere stesso account su più dispositivi
- Ogni dispositivo genera stesso codice TOTP

### 3. Time Sync

- TOTP richiede sincronizzazione orario
- Microsoft Authenticator gestisce automaticamente
- Server deve avere NTP configurato correttamente

## Troubleshooting

### Problema: Codice non accettato

**Possibili cause**:
1. Clock drift (differenza orario > 30 secondi)
2. Secret non sincronizzato
3. Codice già utilizzato

**Soluzione**:
- Verificare orario server
- Rigenerare secret se necessario
- Usare recovery code se disponibile

### Problema: QR code non scansionabile

**Soluzione**:
- Mostrare anche secret in formato testo
- Permettere inserimento manuale
- Fornire link alternativo

## Conclusione

L'implementazione TOTP standard garantisce:
- ✅ **Compatibilità completa** con Microsoft Authenticator
- ✅ **Sicurezza** elevata (2FA obbligatoria)
- ✅ **UX** semplice (QR code standard)
- ✅ **Flessibilità** (supporta altri authenticator apps)
- ✅ **Compliance** con DORA/NIS2

Microsoft Authenticator è **completamente supportato** e funziona out-of-the-box con l'implementazione TOTP standard.
