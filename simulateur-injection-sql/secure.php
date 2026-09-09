<?php
/**
 * VERSION SECURISEE - contrepartie de index.php.
 *
 * Deux protections cumulees :
 *   1) Requete PREPAREE (PDO) : le login est passe en PARAMETRE lie, jamais
 *      concatene. Les payloads d'injection (bob' -- , ' OR 1=1, empilees...)
 *      sont traites comme une simple chaine et echouent.
 *   2) Mot de passe HASHE : on lit un hash bcrypt (table users_secure) et on
 *      le compare avec password_verify(). Aucun mot de passe en clair.
 *
 * Note (article "SQL Injection in PDO's Prepared Statements") : une requete
 * preparee ne protege que si l'emulation est desactivee et si l'on ne
 * reconcatene pas la saisie. On force donc ATTR_EMULATE_PREPARES = false.
 */

const DB_HOST = '127.0.0.1';
const DB_NAME = 'banque_test';
const DB_USER = 'root';
const DB_PASS = '';
const DB_PORT = 3306;

$login = $_POST['login'] ?? '';
$pass  = $_POST['password'] ?? '';
$message = null;
$classe = null;
$soumis = ($_SERVER['REQUEST_METHOD'] === 'POST');

if ($soumis) {
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES   => false,   // vraie requete preparee cote serveur
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // 1) Recherche du compte par login, en PARAMETRE lie (:login).
    $stmt = $pdo->prepare('SELECT id, login, password_hash FROM users_secure WHERE login = :login');
    $stmt->execute([':login' => $login]);
    $user = $stmt->fetch();

    // 2) Verification du mot de passe hashe.
    if ($user && password_verify($pass, $user['password_hash'])) {
        $message = "Connexion reussie en tant que '" . $user['login'] . "'.";
        $classe = 'ok';
    } else {
        $message = "Identifiants invalides.";
        $classe = 'error';
    }
}

$page_titre = 'Version securisee - Simulateur SQLi';
require __DIR__ . '/includes/header.php';
?>
  <h1>Version s&eacute;curis&eacute;e (PDO + hachage)</h1>
  <p class="hint">
    M&ecirc;me formulaire que le <a href="index.php">login vuln&eacute;rable</a>, mais requ&ecirc;te
    pr&eacute;par&eacute;e et mot de passe hach&eacute;. Rejoue ici les payloads qui marchaient
    (<code>bob' -- </code>, <code>' OR 1=1</code>, empil&eacute;es&hellip;) : ils &eacute;chouent tous.
    Comptes valides : <code>admin / admin123</code>, <code>bob / bobpass</code>.
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
  <h2>R&eacute;sultat</h2>
  <p class="<?= $classe === 'ok' ? 'ok' : 'error' ?>"><strong><?= htmlspecialchars($message) ?></strong></p>
  <p class="hint">
    Requ&ecirc;te r&eacute;ellement ex&eacute;cut&eacute;e (le param&egrave;tre reste s&eacute;par&eacute; du SQL) :<br>
    <code>SELECT id, login, password_hash FROM users_secure WHERE login = :login</code>
    &nbsp;avec <code>:login</code> = la valeur saisie, trait&eacute;e comme donn&eacute;e.
  </p>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
