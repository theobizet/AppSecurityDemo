<?php
/**
 * Configuration de connexion a la base "banque_test".
 *
 * Identifiants par defaut de XAMPP en local : utilisateur "root", sans mot
 * de passe. C'est le reglage usine de XAMPP sur un poste de developpement ;
 * a ne jamais utiliser sur un serveur expose.
 */

const DB_HOST = '127.0.0.1';
const DB_USER = 'root';
const DB_PASS = '';
const DB_NAME = 'banque_test';
const DB_PORT = 3306;

/**
 * Ouvre une connexion mysqli et coupe le rapport d'exceptions automatique de
 * mysqli, afin que l'on puisse RECUPERER et AFFICHER les erreurs SQL nous-memes
 * (indispensable pour la demo "error-based").
 */
function db_connect(): mysqli
{
    mysqli_report(MYSQLI_REPORT_OFF);
    $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    if ($conn->connect_errno) {
        http_response_code(500);
        exit(
            "Connexion a MySQL impossible (" . $conn->connect_error . ").<br>" .
            "Verifie que MySQL est demarre dans XAMPP et que la base " .
            "'banque_test' a bien ete importee (sql/01_install.sql)."
        );
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}
