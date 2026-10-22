# 📜 Registro delle Infauste Segnalazioni

*Baroque Ticketing System*

Sistema di segnalazione guasti per i laboratori informatici scolastici, che unisce un backend in PHP scritto con attenzione alla sicurezza a un'interfaccia volutamente anacronistica, teatrale e sfarzosa.

Il progetto è nato come esercizio di approfondimento su sicurezza delle applicazioni web, containerizzazione e gestione dei database, partendo dalle esperienze di laboratorio all'ITT Blaise Pascal.

## 🛠️ Stack e architettura

```
Browser ──► web (php:8.3-apache) :8080 ──PDO──► db (mysql:8.0)
                                                  └─ volume db_data
```

* **Backend:** PHP 8.3 senza framework, con `declare(strict_types=1)`, servito da Apache in un container dedicato.
* **Database:** MySQL 8.0 in un secondo container, con schema creato automaticamente al primo avvio tramite `init.sql` e dati persistenti su volume Docker.
* **Orchestrazione:** Docker Compose, con healthcheck sul database e `depends_on: service_healthy`, così il container web parte solo quando MySQL è effettivamente pronto ad accettare connessioni.
* **Accesso ai dati:** esclusivamente tramite PDO, con charset `utf8mb4`, `ERRMODE_EXCEPTION` e timeout di connessione.
* **Frontend:** HTML5 e CSS3 vanilla. Lo stile barocco usa Google Fonts (Cinzel Decorative, EB Garamond), pattern SVG inline per le texture damascate e animazioni CSS per i riflessi metallici.

## 🔒 Misure di sicurezza

1. **SQL Injection:** tutte le query che coinvolgono input dell'utente usano prepared statement con parametri nominati. L'emulazione dei prepared statement è disattivata (`PDO::ATTR_EMULATE_PREPARES => false`), quindi la separazione tra query e dati avviene sul server MySQL e non con un escaping lato PHP.
2. **Cross-Site Scripting (XSS):** tutto ciò che viene stampato in pagina passa da una funzione di escaping centralizzata basata su `htmlspecialchars()` con `ENT_QUOTES | ENT_SUBSTITUTE` e charset UTF-8 esplicito, che neutralizza anche gli apici negli attributi HTML e le sequenze UTF-8 non valide.
3. **Cross-Site Request Forgery (CSRF):** il form include un token casuale da 256 bit generato con `random_bytes()` e legato alla sessione. Il token viene verificato con `hash_equals()`, che esegue un confronto a tempo costante, e viene rigenerato dopo ogni invio riuscito, impedendo anche il reinvio accidentale dello stesso form.
4. **Validazione dell'input:** campi obbligatori e lunghezze massime controllati lato server con `mb_strlen()` (quindi corretti anche con caratteri multibyte), coerenti con i vincoli dello schema SQL e con gli attributi `required` e `maxlength` lato client.
5. **Information disclosure:** le eccezioni del database vengono catturate e scritte nel log del server con `error_log()`; all'utente arriva solo un messaggio generico, e in caso di database irraggiungibile la pagina risponde con un HTTP 503.

## 🚀 Avvio rapido

Prerequisiti: Docker con il plugin Compose.

```bash
git clone https://github.com/matteoLillo0/highschool-ticketing.git
cd highschool-ticketing
docker compose up -d --build
```

L'applicazione è poi raggiungibile su `http://localhost:8080`. Per fermarla:

```bash
docker compose down        # mantiene i dati
docker compose down -v     # elimina anche il volume del database
```

## 🚧 Limiti noti

Essendo un progetto didattico pensato per girare in locale, alcune scelte andrebbero riviste prima di un uso reale:

* **Credenziali:** l'applicazione si collega a MySQL come `root`, con password scritta in chiaro in `db.php` e in `docker-compose.yml`. In produzione servirebbero un utente dedicato con i soli privilegi `SELECT` e `INSERT` sulla tabella `ticket` e credenziali passate tramite variabili d'ambiente o Docker secrets.
* **Nessuna autenticazione:** chiunque raggiunga la pagina può inviare segnalazioni e leggere le ultime 50, compresi i nomi degli studenti. Un uso reale richiederebbe almeno un'area riservata ai tecnici per la consultazione e la gestione dei ticket.
* **Nessuno stato del ticket:** le segnalazioni non possono essere prese in carico, chiuse o commentate.
* **Nessun rate limiting:** il form non ha protezioni contro invii massivi automatizzati.
* **Cookie di sessione:** non sono configurati esplicitamente i flag `Secure`, `HttpOnly` e `SameSite`, che andrebbero impostati in un deploy servito via HTTPS.

## ⚠️ Disclaimer

Progetto sviluppato a scopo didattico. Le credenziali presenti nella repository sono valori di sviluppo locale e non vanno riutilizzate in nessun ambiente reale.
