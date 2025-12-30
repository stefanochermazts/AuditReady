# Git Workflow per AuditReady

## Strategia di Branching

AuditReady utilizza un workflow basato su **feature branches** dove ogni step del piano di sviluppo viene implementato in un branch dedicato.

## Convenzioni Branching

### Branch Principali

- **`master`**: Branch principale con codice stabile e rilasciato
- **`step/{numero}-{nome}`**: Branch per ogni step del piano di sviluppo
- **`fix/{descrizione}`**: Branch per bugfix urgenti
- **`feature/{nome}`**: Branch per feature aggiuntive non nel piano

### Naming Convention

```
step/{numero}-{nome-breve}
```

Esempi:
- `step/2-setup-laravel`
- `step/3-multi-tenant`
- `step/4-authentication-2fa`
- `step/6-filament-ui`

## Processo per Ogni Step

### 1. Preparazione

```bash
# Assicurati di essere su master aggiornato
git checkout master
git pull origin master
```

### 2. Creare Branch

```bash
# Crea e switcha al nuovo branch
git checkout -b step/2-setup-laravel
```

### 3. Implementare Step

Lavora sul branch seguendo le specifiche dello step nel piano di sviluppo.

### 4. Committare Progressivamente

```bash
# Committare con messaggi chiari
git add .
git commit -m "step/2: Install Filament and required packages"
git commit -m "step/2: Configure Filament panel with multi-tenant support"
git commit -m "step/2: Setup Laravel Pint for code quality"
```

**Formato Commit Message**:
```
step/{numero}: {descrizione breve e chiara}
```

### 5. Push Branch

```bash
# Push del branch al remote
git push -u origin step/2-setup-laravel
```

### 6. Creare Pull Request

Su GitHub:
1. Vai al repository
2. Clicca "Compare & pull request"
3. **Titolo**: `Step {numero}: {Nome Step}`
   - Esempio: `Step 2: Setup Base Laravel con Pacchetti Richiesti`
4. **Descrizione**: 
   ```markdown
   ## Step {numero}: {Nome Step}
   
   Implementa lo Step {numero} del piano di sviluppo.
   
   ### Checklist
   - [ ] Step completato secondo specifiche
   - [ ] Test passati (unit + feature)
   - [ ] Code quality checks passati (Pint)
   - [ ] Documentazione aggiornata (se necessario)
   - [ ] Nessun file sensibile committato
   
   ### Riferimenti
   - Piano di sviluppo: `.artiforge/plan-auditready-development.md`
   - Step {numero}: Linee {range}
   ```

### 7. Review e Merge

- Attendere review del codice
- Risolvere eventuali commenti
- Assicurarsi che CI/CD passi
- Merge in `master` dopo approvazione

### 8. Aggiornare Master Locale

```bash
# Dopo merge, aggiorna master locale
git checkout master
git pull origin master
```

## Branch per Ogni Step

| Step | Branch Name | Descrizione |
|------|-------------|-------------|
| 1 | `step/1-architecture` | Design architetturale ✅ |
| 2 | `step/2-setup-laravel` | Setup base Laravel |
| 3 | `step/3-multi-tenant` | Infrastruttura multi-tenant |
| 4 | `step/4-authentication-2fa` | Autenticazione 2FA |
| 5 | `step/5-rbac` | Role-Based Access Control |
| 6 | `step/6-filament-ui` | Interfaccia Filament |
| 7 | `step/7-evidence-management` | Gestione evidenze |
| 8 | `step/8-audit-trail` | Audit trail immutabile |
| 9 | `step/9-third-party-upload` | Upload third-party |
| 10 | `step/10-export-audit` | Export audit |
| 11 | `step/11-backup-dr` | Backup e disaster recovery |
| 12 | `step/12-security-hardening` | Hardening sicurezza |
| 13 | `step/13-documentation-cicd` | Documentazione e CI/CD |

## Checklist Pull Request

Ogni Pull Request deve verificare:

- [ ] **Step completato**: Tutte le specifiche dello step implementate
- [ ] **Test passati**: Unit tests e feature tests eseguiti con successo
- [ ] **Code quality**: Laravel Pint eseguito senza errori
- [ ] **Documentazione**: README/docs aggiornati se necessario
- [ ] **Sicurezza**: Nessun file sensibile committato (.env, keys, secrets)
- [ ] **Commit messages**: Messaggi chiari e descrittivi
- [ ] **CI/CD**: Tutti i check automatici passati

## Protezione Branch Master

Il branch `master` dovrebbe essere protetto con:

- ✅ **Require pull request reviews**: Almeno 1 approvazione richiesta
- ✅ **Require status checks**: CI/CD deve passare
- ✅ **Require branches to be up to date**: Branch deve essere aggiornato
- ✅ **No force push**: Impedisce force push
- ✅ **No deletion**: Impedisce eliminazione branch

### Configurazione GitHub

1. Vai a **Settings** → **Branches**
2. Aggiungi rule per `master`:
   - Branch name pattern: `master`
   - Require a pull request before merging
   - Require approvals: 1
   - Require status checks to pass
   - Require branches to be up to date before merging
   - Do not allow bypassing the above settings

## Esempio Workflow Completo

### Scenario: Implementare Step 2

```bash
# 1. Aggiorna master
git checkout master
git pull origin master

# 2. Crea branch
git checkout -b step/2-setup-laravel

# 3. Lavora sullo step
composer require filament/filament:"^3.0"
composer require tenancy/tenancy
# ... altre installazioni ...

# 4. Committare progressivamente
git add composer.json composer.lock
git commit -m "step/2: Install Filament and required packages"

php artisan filament:install --panels
git add .
git commit -m "step/2: Configure Filament panel"

# 5. Test
php artisan test
# Se passano, committare

# 6. Push
git push -u origin step/2-setup-laravel

# 7. Su GitHub: Crea Pull Request
# 8. Dopo merge
git checkout master
git pull origin master
```

## Release Strategy

Al completamento di tutti gli step:

### 1. Creare Tag di Versione

```bash
git checkout master
git pull origin master
git tag -a v1.0.0 -m "Release v1.0.0: Initial release"
git push origin v1.0.0
```

### 2. Generare CHANGELOG

Creare `CHANGELOG.md` con:
- Lista di tutti gli step completati
- Feature principali
- Breaking changes (se presenti)
- Note di migrazione

### 3. Creare GitHub Release

1. Vai a **Releases** → **Draft a new release**
2. Tag: `v1.0.0`
3. Titolo: `v1.0.0 - Initial Release`
4. Descrizione: Copia da CHANGELOG
5. Pubblica release

## Best Practices

### Commit Messages

✅ **Buono**:
```
step/2: Install Filament and required packages
step/3: Implement tenant database creation command
step/6: Add EvidenceResource with file upload
```

❌ **Evitare**:
```
fix
update
changes
wip
```

### Branch Management

- **Mantieni branch aggiornati**: Rebase o merge da master regolarmente
- **Branch brevi**: Completa step in tempi ragionevoli
- **Un branch per step**: Non mescolare più step nello stesso branch
- **Elimina branch dopo merge**: Pulisci branch locali e remoti

### Pull Request

- **Descrizioni chiare**: Spiega cosa fa la PR
- **Link al piano**: Riferimento allo step nel piano
- **Screenshots**: Se modifica UI, aggiungi screenshot
- **Testing**: Descrivi come testare le modifiche

## Troubleshooting

### Branch in conflitto con master

```bash
# Rebase sul master aggiornato
git checkout step/2-setup-laravel
git fetch origin
git rebase origin/master

# Risolvi conflitti se presenti
# Continua rebase
git rebase --continue
```

### Commit sbagliato

```bash
# Modifica ultimo commit
git commit --amend -m "step/2: Messaggio corretto"

# Force push (solo se branch non ancora mergiato)
git push --force-with-lease origin step/2-setup-laravel
```

### Reset branch

```bash
# Reset a master (ATTENZIONE: perde modifiche)
git checkout step/2-setup-laravel
git reset --hard origin/master
```

---

**Nota**: Questo workflow garantisce tracciabilità, review del codice e qualità del progetto.
