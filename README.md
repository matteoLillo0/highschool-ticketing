# 📜 Registro delle Infauste Segnalazioni (Baroque Ticketing System)

Un sistema di ticketing per i laboratori informatici che unisce un backend blindato e moderno a un'interfaccia utente volutamente anacronistica, teatrale e sfarzosa. 

Questo progetto nasce come esercizio avanzato per consolidare le competenze su reti, sicurezza backend e orchestrazione dei database, fungendo da ponte tecnico tra le esperienze di laboratorio all'ITT Blaise Pascal e le future sfide architetturali a Scienze Informatiche e Ingegneria.

## 🛠️ Stack Tecnologico e Architettura

L'infrastruttura è progettata per essere sicura, portabile e facilmente replicabile:

*   **Backend:** PHP 8+ puro.
*   **Database:** MySQL 8.0, interamente orchestrato tramite un container **Docker** e `docker-compose.yml`. I dati sono mantenuti tramite volumi persistenti.
*   **Interfacciamento Dati:** Utilizzo esclusivo di **PDO (PHP Data Objects)** con gestione rigorosa delle eccezioni (`PDO::ERRMODE_EXCEPTION`).
*   **Frontend:** HTML5 e CSS3 Vanilla. Il design Barocco fa uso di Google Fonts (Cinzel Decorative, EB Garamond), pattern SVG in-line per le texture damascate e animazioni CSS per simulare riflessi metallici sui componenti della UI.

## 🔒 Sicurezza Implementata

Il focus tecnico del progetto è la robustezza contro i vettori di attacco più comuni nelle applicazioni web:

1.  **Prevenzione SQL Injection (SQLi):** Tutte le query di inserimento (DML) utilizzano i *Prepared Statements* nativi. I dati di input sono strettamente separati dalla logica SQL, impedendo l'esecuzione di payload malevoli.
2.  **Mitigazione Cross-Site Scripting (XSS):** Sanitizzazione degli input in entrata tramite `trim()` e neutralizzazione dell'output in pagina tramite `htmlspecialchars()`, per evitare il rendering di script non autorizzati riflettuti dal server.
3.  **Information Disclosure:** Gestione centralizzata degli errori tramite blocco `try-catch`. Le eccezioni del database non vengono mai stampate a schermo, ma silenziate all'utente e reindirizzate al file di log del server.

## 🚀 Avvio Rapido (Local Environment)

1. Clona la repository.
2. Avvia il demone Docker e lancia il comando:
   ```bash
   docker-compose up -d
