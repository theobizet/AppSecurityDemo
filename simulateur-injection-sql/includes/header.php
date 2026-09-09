<?php
/** En-tete commun : bandeau d'avertissement + navigation. */
if (!isset($page_titre)) {
    $page_titre = 'Simulateur de failles SQL';
}

// Page courante, pour surligner l'onglet actif dans la navbar.
$page_courante = basename($_SERVER['SCRIPT_NAME']);

// Onglets : fichier => [libelle, classe optionnelle].
$onglets = [
    'index.php'      => ['Login vuln&eacute;rable', 'nav-danger'],
    'compte.php'     => ['Recherche compte', 'nav-danger'],
    'techniques.php' => ['Fiche des techniques', ''],
    'secure.php'     => ['Version s&eacute;curis&eacute;e', 'nav-safe'],
];
?><!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($page_titre) ?></title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="warning-banner">
    <span class="warning-dot"></span>
    LABO LOCAL &mdash; APPLICATION VOLONTAIREMENT VULN&Eacute;RABLE &mdash; NE JAMAIS EXPOSER SUR UN R&Eacute;SEAU
  </div>
  <header>
    <nav>
      <a class="brand" href="index.php">
        <span class="brand-icon">&#128137;</span>
        <span>Simulateur <strong>SQLi</strong></span>
      </a>
      <div class="nav-links">
        <?php foreach ($onglets as $fichier => [$libelle, $classe]): ?>
          <a
            href="<?= $fichier ?>"
            class="nav-link <?= $classe ?><?= $fichier === $page_courante ? ' active' : '' ?>"
          ><?= $libelle ?></a>
        <?php endforeach; ?>
      </div>
    </nav>
  </header>
  <main>
