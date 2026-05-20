-- Creazione del database
CREATE DATABASE IF NOT EXISTS scuola_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE scuola_db;

CREATE TABLE IF NOT EXISTS ticket (
    id                  INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    nome_studente       VARCHAR(100)    NOT NULL,
    classe              VARCHAR(10)     NOT NULL,
    descrizione_guasto  TEXT            NOT NULL,
    data_segnalazione   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_data_segnalazione (data_segnalazione DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
