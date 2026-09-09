-- =====================================================================
--  Simulateur de failles - Injection SQL (TP securisation des donnees)
--  Script d'installation : base "banque_test", tables account + users.
--
--  A IMPORTER dans phpMyAdmin (onglet Importer) ou via la console MySQL.
--  Environnement LOCAL/JETABLE uniquement.
--
--  Mots de passe stockes EN CLAIR : c'est VOLONTAIRE, c'est l'objet du TP.
-- =====================================================================

DROP DATABASE IF EXISTS banque_test;
CREATE DATABASE banque_test CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE banque_test;

-- ---------------------------------------------------------------------
-- Table des utilisateurs (login / mot de passe en clair)
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id       INT(11)     NOT NULL AUTO_INCREMENT,
    login    VARCHAR(50) NOT NULL,
    password VARCHAR(50) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY login (login)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO users (id, login, password) VALUES
    (1, 'bob',   'bobpass'),
    (2, 'admin', 'admin123'),
    (3, 'alice', 'wonderland'),
    (4, 'sqdf',  'nuisdncom');

-- ---------------------------------------------------------------------
-- Table des comptes bancaires (owner -> users.id)
-- ---------------------------------------------------------------------
CREATE TABLE account (
    id     INT(11)     NOT NULL AUTO_INCREMENT,
    owner  INT(11)     NOT NULL,
    type   VARCHAR(10) NOT NULL,
    amount INT(11)     NOT NULL,
    PRIMARY KEY (id),
    KEY owner (owner)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO account (id, owner, type, amount) VALUES
    (1, 1, 'COURANT', 1561),
    (2, 2, 'COURANT', 16841),
    (3, 3, 'COURANT', 4894),
    (4, 4, 'COURANT', 0);

-- ---------------------------------------------------------------------
-- Table utilisee UNIQUEMENT par la version SECURISEE (secure.php) :
-- memes comptes, mais mots de passe HASHES (bcrypt via password_hash()).
-- Montre la bonne pratique face a la table "users" en clair.
--   admin -> admin123     bob -> bobpass
-- ---------------------------------------------------------------------
CREATE TABLE users_secure (
    id            INT(11)      NOT NULL AUTO_INCREMENT,
    login         VARCHAR(50)  NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY login (login)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO users_secure (login, password_hash) VALUES
    ('admin', '$2y$10$DduhPK/TgTeYnvR4bP4LTeSACoVaO16/qiBrn5ZL3CWbWHZ4YsV7m'),
    ('bob',   '$2y$10$LiYWzm9.q1YeFh4MonB61eHLHmogVoWbGxT6NWfdTMyTUNLiW.tDq');
