# Strategia Multi-Database per AuditReady

## Panoramica

AuditReady utilizza un approccio **multi-database** dove ogni tenant (cliente) ha il proprio database completamente isolato. Questo garantisce il massimo livello di sicurezza e compliance.

## Vantaggi dell'Approccio Multi-Database

### 1. Isolamento Completo
- **Impossibile accesso cross-tenant**: Anche con bug SQL o query malformate, è fisicamente impossibile accedere ai dati di altri tenant
- **Separazione fisica**: Ogni database è completamente indipendente
- **Compliance**: Allineato a GDPR, DORA, NIS2 che richiedono isolamento dati

### 2. Sicurezza
- Nessun rischio di data leakage tramite query
- Backup e restore per singolo tenant senza impatto su altri
- Possibilità di crittografia per database specifici

### 3. Scalabilità
- Scalare risorse per tenant specifici
- Performance migliori (niente filtri `tenant_id` su ogni query)
- Possibilità di distribuire tenant su server diversi

### 4. Manutenzione
- Backup mirati per tenant
- Restore senza impatto su altri clienti
- Migrazioni testabili su tenant specifici prima di applicarle a tutti

## Architettura

```
┌─────────────────────────────────────────────────────────┐
│              Database Centrale (tenants)                │
│  ┌───────────────────────────────────────────────────┐  │
│  │ tenants table:                                     │  │
│  │ - id (UUID)                                        │  │
│  │ - name                                             │  │
│  │ - domain                                           │  │
│  │ - database_name (auditready_tenant_{uuid})        │  │
│  │ - database_config (JSON)                          │  │
│  │ - created_at, updated_at                           │  │
│  └───────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────┘
                        │
                        │
        ┌───────────────┼───────────────┐
        │               │               │
        ▼               ▼               ▼
┌─────────────┐ ┌─────────────┐ ┌─────────────┐
│ Tenant 1 DB │ │ Tenant 2 DB │ │ Tenant 3 DB │
│             │ │             │ │             │
│ - users     │ │ - users     │ │ - users     │
│ - evidences │ │ - evidences │ │ - evidences │
│ - audits    │ │ - audits    │ │ - audits    │
│ - ...       │ │ - ...       │ │ - ...       │
└─────────────┘ └─────────────┘ └─────────────┘
```

## Creazione di un Nuovo Tenant

### Processo Automatizzato

La creazione di un nuovo tenant è **completamente automatizzata** tramite comando artisan:

```bash
php artisan tenant:create "Nome Cliente" "cliente.auditready.com"
```

### Cosa Fa il Comando (Processo ~30 secondi)

1. **Genera UUID univoco** per il tenant
2. **Crea nuovo database** con nome `auditready_tenant_{uuid}`
3. **Esegue tutte le migrazioni** sul nuovo database
4. **Crea utente admin iniziale** (Organization Owner) con:
   - Email generata automaticamente
   - Password temporanea generata
   - 2FA da configurare al primo login
5. **Configura connessione dinamica** nel sistema
6. **Registra tenant** nel database centrale
7. **Crea directory storage dedicata** per file del tenant (MinIO o filesystem locale)
8. **Genera credenziali** e le invia via email al cliente

### Output del Comando

```
✓ Tenant creato con successo!

Tenant ID: 550e8400-e29b-41d4-a716-446655440000
Database: auditready_tenant_550e8400e29b41d4a716446655440000
Domain: cliente.auditready.com

Credenziali iniziali:
Email: admin@cliente.auditready.com
Password temporanea: [inviata via email]

✓ Database creato e migrazioni eseguite
✓ Utente admin creato
✓ Directory S3 creata
✓ Email inviata al cliente
```

### Complessità: **BASSA** ✅

Grazie all'automazione, creare un nuovo tenant richiede:
- **Tempo**: ~30 secondi
- **Operazioni manuali**: 0
- **Rischio errore**: Minimo (tutto automatizzato)

## Gestione Migrazioni

### Eseguire Migrazioni su Tutti i Tenant

```bash
php artisan tenant:migrate
```

Questo comando:
1. Identifica tutti i tenant attivi
2. Per ogni tenant:
   - Switcha connessione al database tenant
   - Esegue migrazioni pendenti
   - Verifica integrità
3. Report finale con risultati

### Eseguire Migrazioni su Tenant Specifico

```bash
php artisan tenant:migrate --tenant=550e8400-e29b-41d4-a716-446655440000
```

### Complessità: **MEDIA** ⚠️

- **Tempo**: Dipende dal numero di tenant (es: 10 tenant = ~2 minuti)
- **Operazioni manuali**: 0 (automatizzato)
- **Rischio**: Gestito con transazioni e rollback automatico

## Gestione Backup

### Backup Automatico per Tenant

Ogni tenant ha backup automatizzati:
- **Frequenza**: Giornaliera (configurabile)
- **Retention**: 30 giorni (configurabile)
- **Location**: S3 bucket separato con crittografia

### Restore Singolo Tenant

```bash
php artisan tenant:restore --tenant={uuid} --backup={backup_id}
```

### Complessità: **BASSA** ✅

- Backup completamente automatizzati
- Restore con singolo comando
- Nessun impatto su altri tenant

## Identificazione Tenant

Il sistema identifica il tenant corrente tramite:

### 1. Sub-domain (Preferito)
```
cliente1.auditready.com  → Tenant 1
cliente2.auditready.com  → Tenant 2
```

### 2. Header HTTP (per API)
```
X-Tenant-ID: 550e8400-e29b-41d4-a716-446655440000
```

### 3. Path Parameter (opzionale)
```
/api/tenant/{uuid}/evidences
```

## Confronto Complessità

| Operazione | Single-DB | Multi-DB | Automazione |
|------------|-----------|----------|-------------|
| Creare tenant | 1 min | 30 sec | ✅ Comando artisan |
| Migrazioni | 1 comando | 1 comando | ✅ `tenant:migrate` |
| Backup | Manuale | Automatico | ✅ Per tenant |
| Restore | Complesso | Semplice | ✅ Per tenant |
| Isolamento | Logico | Fisico | ✅ Garantito |

## Best Practices

### 1. Naming Convention
- Database: `auditready_tenant_{uuid}`
- S3 Path: `tenants/{uuid}/`
- Domain: `{slug}.auditready.com`

### 2. Monitoring
- Monitorare spazio disco per ogni database
- Alert se database supera soglia
- Dashboard con statistiche per tenant

### 3. Performance
- Connection pooling per database tenant
- Cache separata per tenant (Redis con prefisso)
- Queue workers dedicati per tenant critici

### 4. Sicurezza
- Credenziali database diverse per tenant
- Firewall rules per database
- Audit log per accessi database

## Limitazioni e Considerazioni

### 1. Numero Massimo Tenant
- **Teorico**: Illimitato
- **Pratico**: Dipende da risorse server
- **Raccomandato**: Monitorare dopo 100+ tenant

### 2. Costi
- Ogni database consuma risorse
- Backup multipli aumentano storage
- **Mitigazione**: Automazione riduce overhead operativo

### 3. Migrazioni
- Richiedono tempo proporzionale al numero tenant
- **Mitigazione**: Eseguire in background, batch processing

## Conclusione

L'approccio multi-database offre:
- ✅ **Isolamento completo** e sicurezza massima
- ✅ **Compliance** con GDPR/DORA/NIS2
- ✅ **Automazione** che riduce complessità operativa
- ✅ **Scalabilità** per crescita futura

La complessità di gestione è **minimizzata** grazie all'automazione completa dei processi critici.
