<?php
/**
 * LOGIN VOLONTAIREMENT VULNERABLE.
 *
 * Les champs "login" et "password" sont concatenes directement dans la requete
 * SQL, sans aucun echappement ni requete preparee. C'est la faille centrale du
 * TP. On utilise mysqli_multi_query() pour que les REQUETES EMPILEES
 * (`; DROP ...`, `; UPDATE ...`) soient egalement exploitables depuis ce
 * formulaire, comme dans les captures du sujet.
 *
 * Comparaison : voir secure.php (memes champs, mais requete preparee PDO).
 */
require __DIR__ . '/config.php';

$login = $_POST['login'] ?? '';
$pass  = $_POST['password'] ?? '';
$sql = null;
$blocs = [];      // resultats (un tableau de lignes par SELECT execute)
$erreur = null;
$soumis = ($_SERVER['REQUEST_METHOD'] === 'POST');

if ($soumis) {
    $conn = db_connect();

    // --- CONSTRUCTION VULNERABLE DE LA REQUETE (concatenation brute) ---
    $sql = "SELECT users.login, account.owner, account.type, account.amount\n"
         . "FROM account INNER JOIN users ON account.owner = users.id\n"
         . "WHERE users.login = '$login' AND users.password = '$pass';";

    // --- EXECUTION avec support des requetes empilees ---
    if ($conn->multi_query($sql)) {
        do {
            $res = $conn->store_result();
            if ($res instanceof mysqli_result) {
                $lignes = [];
                while ($row = $res->fetch_assoc()) {
                    $lignes[] = $row;
                }
                $blocs[] = $lignes;
                $res->free();
            }
        } while ($conn->more_results() && $conn->next_result());
    }
    // Une erreur peut survenir sur la 1ere requete OU sur une requete empilee.
    if ($conn->errno) {
        $erreur = '#' . $conn->errno . ' - ' . $conn->error;
    }
    $conn->close();
}

$page_titre = 'Login vulnerable - Simulateur SQLi';
require __DIR__ . '/includes/header.php';
?>
  <h1>Login vuln&eacute;rable</h1>
  <p class="hint">
    Les champs sont ins&eacute;r&eacute;s tels quels dans la requ&ecirc;te. Essaie par exemple
    login&nbsp;=&nbsp;<code>bob' -- </code> (avec l'espace final), ou mot de passe&nbsp;=&nbsp;<code>blabla' OR 1='1</code>.
    Voir la <a href="techniques.php">fiche des techniques</a> pour la liste compl&egrave;te.
  </p>

  <form method="post" class="form">
    <label>Login
      <input type="text" name="login" value="<?= htmlspecialchars($login) ?>" autofocus>
    </label>
    <label>Mot de passe
      <input type="text" name="password" value="<?= htmlspecialchars($pass) ?>">
    </label>
    <button type="submit">Se connecter</button>
  </form>

<?php if ($soumis): ?>
  <h2>Requ&ecirc;te SQL ex&eacute;cut&eacute;e</h2>
  <pre class="sql-echo"><?= htmlspecialchars($sql) ?></pre>

  <?php if ($erreur !== null): ?>
    <p class="error">Erreur MySQL : <?= htmlspecialchars($erreur) ?></p>
  <?php endif; ?>

  <?php foreach ($blocs as $i => $lignes): ?>
    <h2>R&eacute;sultat <?= $i + 1 ?> (<?= count($lignes) ?> ligne(s))</h2>
    <?php if ($lignes): ?>
      <table>
        <thead><tr><?php foreach (array_keys($lignes[0]) as $col): ?><th><?= htmlspecialchars($col) ?></th><?php endforeach; ?></tr></thead>
        <tbody>
          <?php foreach ($lignes as $row): ?>
            <tr><?php foreach ($row as $val): ?><td><?= htmlspecialchars((string) $val) ?></td><?php endforeach; ?></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p>Aucune ligne.</p>
    <?php endif; ?>
  <?php endforeach; ?>

  <?php if (!$blocs && $erreur === null): ?>
    <p>Aucun r&eacute;sultat (identifiants refus&eacute;s).</p>
  <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
