AuditReady – Analisi Funzionale, Privacy e Security
1. Scopo del documento

Questo documento descrive:

funzionalità della piattaforma AuditReady

flussi operativi

dati trattati

misure di sicurezza e privacy-by-design

rischi noti e mitigazioni

L’obiettivo è identificare e affrontare in anticipo tutti gli aspetti che potrebbero ostacolare:

adozione da parte dei clienti

approvazione security / IT

utilizzo in contesti regolamentati (DORA / NIS2)

2. Ambito e posizionamento

AuditReady è una piattaforma operativa di supporto all’audit.

Dichiarazione esplicita di perimetro

AuditReady:

NON valuta la conformità

NON interpreta dati

NON fornisce consulenza legale

NON esegue scoring o rating

AuditReady:

archivia

versiona

struttura

esporta evidenze fornite dagli utenti

Questa distinzione è fondamentale per:

ridurre il rischio legale

semplificare la DPIA

limitare il perimetro del trattamento dati

3. Ruoli utente e principio del minimo privilegio
Ruoli previsti

Organization Owner

Audit Manager

Contributor

Viewer

External Uploader (fornitori – modulo terze parti)

Principi applicati

Least privilege: ogni ruolo ha solo i permessi minimi necessari

Segregation of duties:

chi carica non approva

chi gestisce utenti non modifica evidenze

No shared accounts

4. Tipologie di dati trattati
4.1 Dati personali

Possibili dati personali trattati:

nome e cognome utenti

email aziendali

metadati di attività (log)

⚠️ Non è richiesto caricare dati personali nelle evidenze, ma non può essere escluso che documenti caricati li contengano.

4.2 Dati sensibili / critici

policy interne

procedure di sicurezza

documentazione incidenti

evidenze di continuità operativa

documentazione fornitori

👉 AuditReady deve essere trattato come sistema ad alta confidenzialità.

5. Architettura di sicurezza (principi generali)
5.1 Security by design

La piattaforma è progettata secondo:

privacy by design

security by default

defense in depth

Ogni livello (UI, API, storage, infrastruttura) implementa controlli autonomi.

6. Autenticazione e controllo accessi
6.1 Autenticazione

Login con email aziendale

Password con requisiti forti

2FA obbligatoria (TOTP – app autenticazione)

⚠️ La 2FA è obbligatoria, non opzionale, per:

Audit Manager

Owner

ruoli con upload/export

6.2 Gestione sessioni

Token di sessione a durata limitata

Invalida sessioni su:

cambio password

attivazione 2FA

Protezione CSRF

7. Autorizzazione e controllo dati
7.1 Isolamento tenant (multi-tenant security)

Ogni organizzazione è logicamente isolata

Ogni query applica sempre organization_id

Test automatici per prevenire data leakage cross-tenant

7.2 Accesso alle evidenze

Nessun accesso diretto via URL statico

Download solo tramite endpoint autenticati

Token temporanei per accessi esterni

8. Crittografia
8.1 Crittografia in transito

HTTPS obbligatorio

TLS 1.2+ (preferibilmente 1.3)

HSTS attivo

8.2 Crittografia a riposo

Database cifrato (AES-256)

File storage cifrato

Backup cifrati

8.3 Hashing e integrità

Password hashate (Argon2 / bcrypt)

Evidenze con hash SHA-256

Verifica integrità file

👉 Questo consente di dimostrare che un file non è stato alterato.

9. Logging e tracciabilità (audit trail)
9.1 Eventi tracciati

login / logout

upload evidenze

nuove versioni

export audit

modifiche ownership

gestione utenti

9.2 Protezione dei log

log immutabili (append-only)

accesso ristretto

retention configurabile

10. Gestione delle evidenze (risk focus)
10.1 Versioning

Nessuna sovrascrittura

Ogni modifica genera nuova versione

Le versioni precedenti restano accessibili

10.2 Eliminazione

Eliminazione solo da utenti autorizzati

Soft delete con retention

Tracciamento eliminazioni

11. Modulo Third-Party – rischi e mitigazioni
Rischi

upload di file malevoli

accesso non autorizzato

leak di evidenze

Mitigazioni

link upload a tempo

scadenza automatica

dimensioni limitate

antivirus / malware scanning

nessuna visibilità su altri dati

12. Privacy e GDPR
12.1 Ruoli GDPR

Cliente = Titolare del trattamento

AuditReady = Responsabile del trattamento

12.2 Principi applicati

minimizzazione dati

limitazione finalità

conservazione limitata

accountability

12.3 Diritti interessati

accesso

rettifica

cancellazione

limitazione

Gestiti a livello account e file, compatibilmente con obblighi di conservazione.

13. Backup e disaster recovery

Backup automatici

Retention definita

Test periodici di restore

RTO e RPO dichiarabili

14. Rischi residui e limiti dichiarati
Rischi non eliminabili

contenuto dei documenti caricati

errori umani

configurazioni cliente

Mitigazioni

policy chiare

responsabilità contrattuali

configurazioni conservative

15. Impatti sulla diffusione (analisi realistica)
Ostacoli principali

timore di caricare evidenze sensibili

richieste security assessment

necessità approvazione IT

Punti di forza

perimetro funzionale limitato

assenza di processing automatico

trasparenza architetturale

modularità