<?php

declare(strict_types=1);

$db_host = 'db';
$db_name = 'scuola_db';
$db_user = 'root';
$db_pass = 'root_password';

$dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";

// Opzioni PDO per hardening della connessione
$options = [
    // Lancia eccezioni per ogni errore SQL (fondamentale per il debug e la sicurezza)
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,

    PDO::ATTR_EMULATE_PREPARES   => false,

    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

    PDO::ATTR_TIMEOUT            => 5,
];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    // In produzione: loggare l'errore e mostrare un messaggio generico.
    // Mai esporre dettagli della connessione all'utente finale.
    error_log('[DB] Connessione fallita: ' . $e->getMessage());
    http_response_code(503);
    exit('Servizio temporaneamente non disponibile. Riprovare più tardi.');
}
