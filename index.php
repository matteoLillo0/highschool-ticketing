<?php

declare(strict_types=1);
session_start();
require_once __DIR__ . '/db.php';

function esc(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$success_message = '';
$error_message   = '';


// Elaborazione del form (solo su POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token_ricevuto = $_POST['csrf_token'] ?? '';
    if (!hash_equals($csrf_token, $token_ricevuto)) {
        $error_message = 'Richiesta non valida. Ricaricare la pagina e riprovare.';
    } else {

        // sanitizzazione input — trim + rimozione tag HTML
        $nome_studente      = trim(strip_tags($_POST['nome_studente'] ?? ''));
        $classe             = trim(strip_tags($_POST['classe'] ?? ''));
        $descrizione_guasto = trim(strip_tags($_POST['descrizione_guasto'] ?? ''));

        if ($nome_studente === '' || $classe === '' || $descrizione_guasto === '') {
            $error_message = 'Tutti i campi sono obbligatori.';
        } elseif (mb_strlen($nome_studente) > 100) {
            $error_message = 'Il nome studente non può superare i 100 caratteri.';
        } elseif (mb_strlen($classe) > 10) {
            $error_message = 'La classe non può superare i 10 caratteri.';
        } else {

            // inserimento con Prepared Statement (anti SQL-Injection)
            try {
                $stmt = $pdo->prepare(
                    'INSERT INTO ticket (nome_studente, classe, descrizione_guasto, data_segnalazione)
                     VALUES (:nome, :classe, :descrizione, NOW())'
                );

                $stmt->execute([
                    ':nome'        => $nome_studente,
                    ':classe'      => $classe,
                    ':descrizione' => $descrizione_guasto,
                ]);
                $success_message = 'Segnalazione inviata correttamente! Ticket #' . $pdo->lastInsertId();

                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                $csrf_token = $_SESSION['csrf_token'];

            } catch (PDOException $e) {
                error_log('[TICKET] Errore inserimento: ' . $e->getMessage());
                $error_message = 'Errore interno. La segnalazione non è stata salvata. Riprovare.';
            }
        }
    }
}

// recupero ticket esistenti (ultimi 50, ordinati per data decrescente)
try {
    $stmt_lista = $pdo->query(
        'SELECT id, nome_studente, classe, descrizione_guasto, data_segnalazione
         FROM ticket
         ORDER BY data_segnalazione DESC
         LIMIT 50'
    );
    $tickets = $stmt_lista->fetchAll();
} catch (PDOException $e) {
    error_log('[TICKET] Errore lettura ticket: ' . $e->getMessage());
    $tickets = [];
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Registro Barocco delle Infauste Segnalazioni — Sistema di ticketing scolastico">
    <title>Registro delle Infauste Segnalazioni — Ticketing Scolastico</title>

    <!-- Google Fonts — Cinzel Decorative + EB Garamond -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@400;700;900&family=EB+Garamond:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">

    <style>
        /* ============================
           CSS RESET & BASE
           ============================ */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --gold:        #c9a84c;
            --gold-light:  #f0d78c;
            --gold-dark:   #8b6914;
            --burgundy:    #5c0a0a;
            --burgundy-dk: #2e0505;
            --parchment:   #fffbef;
            --parch-dark:  #f5ead0;
            --ink:         #2b1a0e;
            --ink-light:   #5a4630;
            --crimson:     #8b0000;
            --emerald:     #2e6b3a;
            --font-display:'Cinzel Decorative', serif;
            --font-body:   'EB Garamond', serif;
        }

        /* ============================
           BODY — DARK DAMASK
           ============================ */
        body {
            font-family: var(--font-body);
            font-size: 18px;
            line-height: 1.7;
            color: var(--parchment);
            min-height: 100vh;
            background-color: #110808;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='80' height='80'%3E%3Cpath d='M40 0c2 10 10 18 20 20C50 22 42 30 40 40c-2-10-10-18-20-20C30 18 38 10 40 0z' fill='%23ffffff' fill-opacity='0.03'/%3E%3C/svg%3E");
        }

        /* ============================
           LAYOUT
           ============================ */
        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px 24px 60px;
        }

        /* ============================
           HEADER
           ============================ */
        .page-header {
            text-align: center;
            padding: 40px 20px 30px;
            margin-bottom: 40px;
        }
        .fleuron { font-size: 2rem; color: var(--gold); display: block; margin-bottom: 8px; }
        .page-title {
            font-family: var(--font-display);
            font-weight: 900;
            font-size: clamp(1.4rem, 4vw, 2.2rem);
            color: var(--gold-light);
            text-shadow: 0 2px 12px rgba(201,168,76,0.35), 0 0 40px rgba(201,168,76,0.10);
            letter-spacing: 0.04em;
            line-height: 1.3;
        }
        .page-subtitle {
            font-family: var(--font-body);
            font-style: italic;
            font-size: 1.05rem;
            color: #c4a97d;
            margin-top: 10px;
            opacity: 0.85;
        }
        .ornament-line {
            display: block;
            margin: 18px auto 0;
            color: var(--gold);
            font-size: 1.4rem;
            letter-spacing: 0.5em;
            opacity: 0.5;
        }

        /* ============================
           PANELS — PARCHMENT
           ============================ */
        .panel {
            background: var(--parchment);
            border: 3px solid var(--gold);
            border-radius: 4px;
            padding: 40px 36px;
            margin-bottom: 40px;
            color: var(--ink);
            position: relative;
            box-shadow:
                0 8px 40px rgba(0,0,0,0.6),
                0 2px 8px rgba(0,0,0,0.4),
                inset 0 0 60px rgba(201,168,76,0.07);
        }
        /* corner flourishes */
        .panel::before, .panel::after {
            font-size: 1.6rem;
            color: var(--gold);
            position: absolute;
            opacity: 0.55;
            line-height: 1;
        }
        .panel::before { content: '❧'; top: 10px; left: 14px; }
        .panel::after  { content: '❧'; bottom: 10px; right: 14px; transform: rotate(180deg); }

        .panel-heading {
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 1.3rem;
            color: var(--burgundy);
            text-align: center;
            margin-bottom: 24px;
            letter-spacing: 0.03em;
        }
        .panel-divider {
            display: block;
            text-align: center;
            color: var(--gold);
            font-size: 1rem;
            letter-spacing: 0.3em;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        /* ============================
           ALERTS
           ============================ */
        .alert {
            padding: 14px 20px;
            border-radius: 4px;
            margin-bottom: 24px;
            font-size: 0.95rem;
            font-style: italic;
            border: 2px solid;
            animation: fadeSlide 0.4s ease-out;
        }
        .alert-success {
            background: #eaf5ec;
            border-color: var(--emerald);
            color: #1a4d28;
        }
        .alert-error {
            background: #fbeaea;
            border-color: var(--crimson);
            color: #5c0a0a;
        }
        @keyframes fadeSlide {
            from { opacity: 0; transform: translateY(-10px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ============================
           FORM
           ============================ */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 22px;
        }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group.full-width { grid-column: 1 / -1; }

        .form-label {
            font-family: var(--font-display);
            font-weight: 400;
            font-size: 0.78rem;
            color: var(--ink-light);
            text-transform: uppercase;
            letter-spacing: 0.12em;
        }
        .form-input, .form-textarea {
            font-family: var(--font-body);
            font-size: 1rem;
            color: var(--ink);
            background: rgba(245, 234, 208, 0.5);
            border: 1.5px solid var(--gold);
            border-radius: 3px;
            padding: 12px 16px;
            outline: none;
            transition: border-color 0.25s, box-shadow 0.25s;
        }
        .form-input::placeholder, .form-textarea::placeholder {
            color: #a89470;
            font-style: italic;
        }
        .form-input:focus, .form-textarea:focus {
            border-color: var(--gold-dark);
            box-shadow: 0 0 0 3px rgba(201,168,76,0.2);
        }
        .form-textarea {
            resize: vertical;
            min-height: 120px;
        }

        /* ============================
           SUBMIT BUTTON — GOLD SHEEN
           ============================ */
        .btn-submit {
            grid-column: 1 / -1;
            margin-top: 8px;
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 1rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #1a0f00;
            background: linear-gradient(135deg, #b8860b, #daa520, #f5d576, #daa520, #b8860b);
            background-size: 200% 200%;
            border: 2.5px solid #8b6914;
            border-radius: 4px;
            padding: 16px 32px;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(139,105,20,0.4);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(139,105,20,0.5);
        }
        .btn-submit:active { transform: translateY(0); }

        /* animated light sheen */
        .btn-submit::after {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 60%;
            height: 100%;
            background: linear-gradient(
                120deg,
                transparent 0%,
                rgba(255,255,255,0.5) 50%,
                transparent 100%
            );
            animation: sheen 3s ease-in-out infinite;
        }
        @keyframes sheen {
            0%   { left: -100%; }
            40%  { left: 150%; }
            100% { left: 150%; }
        }

        /* ============================
           TABLE
           ============================ */
        .table-wrap { overflow-x: auto; }
        .ticket-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.92rem;
        }
        .ticket-table thead th {
            font-family: var(--font-display);
            font-weight: 400;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: var(--ink-light);
            background: var(--parch-dark);
            padding: 12px 14px;
            text-align: left;
            border-bottom: 2px solid var(--gold);
        }
        .ticket-table tbody td {
            padding: 11px 14px;
            border-bottom: 1px solid #e2d5b8;
            vertical-align: top;
            color: var(--ink);
        }
        .ticket-table tbody tr:hover { background: rgba(201,168,76,0.08); }
        .ticket-id {
            font-weight: 700;
            color: var(--burgundy);
            font-variant-numeric: tabular-nums;
        }
        .badge-classe {
            display: inline-block;
            background: var(--burgundy);
            color: var(--gold-light);
            padding: 2px 10px;
            border-radius: 3px;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.04em;
        }
        .ticket-date {
            color: var(--ink-light);
            font-size: 0.85rem;
            white-space: nowrap;
            font-style: italic;
        }
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: var(--ink-light);
            font-style: italic;
        }
        .empty-icon { font-size: 2.5rem; margin-bottom: 10px; opacity: 0.4; }

        /* ============================
           FOOTER
           ============================ */
        .page-footer {
            text-align: center;
            color: var(--gold);
            opacity: 0.35;
            font-size: 1.2rem;
            letter-spacing: 0.5em;
            margin-top: 10px;
        }

        /* ============================
           RESPONSIVE
           ============================ */
        @media (max-width: 640px) {
            .form-grid { grid-template-columns: 1fr; }
            .panel { padding: 28px 20px; }
            .container { padding: 12px 14px 40px; }
        }
    </style>
</head>
<body>

<div class="container">

    <!-- ===== Header ===== -->
    <header class="page-header">
        <span class="fleuron">⚜</span>
        <h1 class="page-title">Registro delle Infauste<br>Segnalazioni</h1>
        <p class="page-subtitle">
            Compìlisi il presente formulario per annotare nel Gran Libro ogni guasto e malfunzionamento delle macchine computanti
        </p>
        <span class="ornament-line">❦ ❦ ❦</span>
    </header>

    <!-- ===== Messaggi di feedback ===== -->
    <?php if ($success_message !== ''): ?>
        <div class="alert alert-success" role="alert" id="alert-success">
            ✠ <?= esc($success_message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error_message !== ''): ?>
        <div class="alert alert-error" role="alert" id="alert-error">
            ⚠ <?= esc($error_message) ?>
        </div>
    <?php endif; ?>

    <!-- ===== Form di inserimento ===== -->
    <div class="panel">
        <h2 class="panel-heading">✦ Nuova Supplica di Assistenza ✦</h2>
        <span class="panel-divider">— ❦ —</span>

        <form method="POST" action="" id="ticket-form" autocomplete="off">
            <!-- Token CSRF nascosto -->
            <input type="hidden" name="csrf_token" value="<?= esc($csrf_token) ?>">

            <div class="form-grid">

                <!-- Nome Studente -->
                <div class="form-group">
                    <label for="nome_studente" class="form-label">
                        Nomen dello Studente
                    </label>
                    <input type="text"
                           id="nome_studente"
                           name="nome_studente"
                           placeholder="es. Mario de' Rossi"
                           maxlength="100"
                           required
                           class="form-input">
                </div>

                <!-- Classe -->
                <div class="form-group">
                    <label for="classe" class="form-label">
                        Classe et Sezione
                    </label>
                    <input type="text"
                           id="classe"
                           name="classe"
                           placeholder="es. III-A INF"
                           maxlength="10"
                           required
                           class="form-input">
                </div>

                <!-- Descrizione guasto (full-width) -->
                <div class="form-group full-width">
                    <label for="descrizione_guasto" class="form-label">
                        Descriptio del Guasto ovvero Malfunzionamento
                    </label>
                    <textarea id="descrizione_guasto"
                              name="descrizione_guasto"
                              placeholder="Esponi con dovizia di particolari la natura dell'infausto malfunzionamento..."
                              required
                              class="form-textarea"></textarea>
                </div>

                <!-- Submit -->
                <button type="submit" id="btn-submit" class="btn-submit">
                    ⚜ Sancisci la Segnalazione ⚜
                </button>
            </div>
        </form>
    </div>

    <!-- ===== Elenco ticket ===== -->
    <div class="panel">
        <h2 class="panel-heading">❦ Codex delle Recenti Doglianze ❦</h2>
        <span class="panel-divider">— ✦ —</span>

        <?php if (empty($tickets)): ?>
            <div class="empty-state">
                <div class="empty-icon">⚙</div>
                <p>Nessuna doglianza è stata annotata nel presente Registro.</p>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="ticket-table" id="ticket-table">
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>Studente</th>
                            <th>Classe</th>
                            <th>Descriptio</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tickets as $ticket): ?>
                            <tr>
                                <td class="ticket-id">
                                    <?= (int) $ticket['id'] ?>
                                </td>
                                <td>
                                    <?= esc($ticket['nome_studente']) ?>
                                </td>
                                <td>
                                    <span class="badge-classe">
                                        <?= esc($ticket['classe']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?= esc($ticket['descrizione_guasto']) ?>
                                </td>
                                <td class="ticket-date">
                                    <?= esc(
                                        (new DateTime($ticket['data_segnalazione']))
                                            ->format('d/m/Y H:i')
                                    ) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="page-footer">❧ ❦ ❧</div>
</div>

</body>
</html>
