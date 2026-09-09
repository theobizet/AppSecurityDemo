<?php
/**
 * RECHERCHE DE COMPTE PAR ID - contexte NUMERIQUE vulnerable.
 *
 * Le parametre "id" est concatene sans quotes dans la clause WHERE, ce qui
 * offre un terrain ideal pour :
 *   - le time-based  : id = 1-sleep(15)
 *   - l'UNION        : id = 0 UNION SELECT ...
 *   - l'error-based  : id = 1'  (guillemet en trop)
 *   - INTO OUTFILE   : ... INTO OUTFILE '...'
 *
 * Contexte numerique => une seule requete (pas de multi_query ici) : c'est le
 * complement du login de index.php, ou se testent plutot les empilees.
 */
require __DIR__ . '/config.php';

$id = $_GET['id'] ?? '';
$sql = null;
$lignes = [];
$erreur = null;
$soumis = ($id !== '');

if ($soumis) {
    $conn = db_connect();
    // --- CONCATENATION VULNERABLE, contexte numerique (pas de quotes) ---
    $sql = "SELECT id, owner, type, amount FROM account WHERE id = $id;";
    $res = $conn->query($sql);
    if ($res === false) {
        $erreur = '#' . $conn->errno . ' - ' . $conn->error;
    } else {
        while ($row = $res->fetch_assoc()) {
            $lignes[] = $row;
        }
        $res->free();
    }
    $conn->close();
}

$page_titre = 'Recherche de compte - Simulateur SQLi';
require __DIR__ . '/includes/header.php';
?>
  <h1>Recherche de compte par id</h1>
  <p class="hint">
    Le param&egrave;tre <code>id</code> passe dans l'URL sans quotes. Essaie
    <code>1</code>, puis <code>1-sleep(5)</code> (r&eacute;ponse retard&eacute;e),
    <code>0 UNION SELECT id, login, password, 1 FROM users</code>, ou <code>1'</code>
    (erreur). Voir la <a href="techniques.php">fiche des techniques</a>.
  </p>

  <form method="get" class="form-inline">
    <input type="text" name="id" value="<?= htmlspecialchars($id) ?>" placeholder="id du compte">
    <button type="submit">Rechercher</button>
  </form>

<?php if ($soumis): ?>
  <h2>Requ&ecirc;te SQL ex&eacute;cut&eacute;e</h2>
  <pre class="sql-echo"><?= htmlspecialchars($sql) ?></pre>

  <?php if ($erreur !== null): ?>
    <p class="error">Erreur MySQL : <?= htmlspecialchars($erreur) ?></p>
  <?php elseif ($lignes): ?>
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
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
