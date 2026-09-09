# Compte rendu — Sécurisation des données : injections SQL

**Auteur :** _(à compléter)_
**Date :** _(à compléter)_
**TP :** Sécurisation des applications — structuration et sécurisation des données

---

## 1. Introduction et objectifs

Ce compte rendu présente les expérimentations menées sur un **simulateur de
failles** développé pour le TP : une application web volontairement vulnérable
aux injections SQL, accompagnée d'une version sécurisée servant de point de
comparaison. L'objectif est de reproduire et documenter les principales
techniques d'injection SQL, d'en mesurer les effets, d'en identifier les
limites, puis de récapituler les protections en place et celles à mettre en
œuvre.

Le dépôt contient deux applications distinctes :
- `app/` — démonstration de l'exercice de normalisation (gestionnaire de
  projet, Python/Flask), développée directement avec les bonnes pratiques.
- `simulateur-injection-sql/` — le simulateur de failles objet de ce compte
  rendu (PHP/MySQL).

---

## 2. Environnement de test

| Élément | Valeur |
|---|---|
| Plateforme | XAMPP (Apache + MariaDB + PHP + phpMyAdmin) |
| SGBD | MariaDB 10.4.32 |
| Langage applicatif | PHP |
| Base de test | `banque_test` (jetable, ré-importable) |
| Accès | `http://127.0.0.1:8099/` (serveur PHP intégré, cf. README) |

> Remarque environnement : le port 80 étant occupé par un autre hôte virtuel
> Apache sur le poste, le simulateur a été servi via le serveur PHP intégré sur
> le port 8099. Le SGBD MariaDB de XAMPP est utilisé normalement.

### Schéma de la base

- `users(id, login, password)` — **mots de passe en clair** (volontaire, cible
  des attaques).
- `account(id, owner, type, amount)` — comptes bancaires, `owner → users.id`.
- `users_secure(id, login, password_hash)` — variante **hachée** (bcrypt),
  utilisée uniquement par la version sécurisée.

### Requête vulnérable de référence (formulaire de login, `index.php`)

```sql
SELECT users.login, account.owner, account.type, account.amount
FROM account INNER JOIN users ON account.owner = users.id
WHERE users.login = '<login>' AND users.password = '<password>';
```

Les valeurs `<login>` et `<password>` sont **concaténées sans échappement**.
Le point d'entrée numérique `compte.php?id=<id>` concatène `<id>` sans quotes
(`... WHERE id = <id>`).

---

## 3. Expérimentations menées

Pour chaque technique : objectif, payload utilisé, où il est saisi, requête
réellement exécutée et effet observé. _(Insérer les captures d'écran aux
emplacements indiqués.)_

### 3.1 Commentaire (`--`)
- **Objectif :** contourner la vérification du mot de passe.
- **Où :** `index.php`, champ *login*.
- **Payload :** `bob' -- ` _(espace final requis)_
- **Requête exécutée :**
  ```sql
  ... WHERE users.login = 'bob' -- ' AND users.password = '...'
  ```
- **Effet observé :** connexion réussie en tant que `bob` sans mot de passe
  valide (le `-- ` neutralise la fin de la clause).
- _[capture à insérer]_

### 3.2 Expression toujours vraie (`OR 1=1`)
- **Objectif :** rendre la condition vraie pour toutes les lignes.
- **Où :** `index.php`, champ *mot de passe* (login = `bob`).
- **Payload :** `blabla' OR 1='1`
- **Effet observé :** authentification contournée, la ligne est renvoyée.
- _[capture à insérer]_

### 3.3 Paralyser le SGBD — time-based (`sleep`)
- **Objectif :** prouver l'injection sans affichage (blind), par le temps.
- **Où :** `compte.php`, paramètre *id*.
- **Payload :** `1-sleep(3)` (15 pour reproduire la capture du sujet)
- **Effet observé :** réponse retardée. **Limite constatée :** `sleep()` placé
  dans le `WHERE` est évalué **une fois par ligne** de la table `account` (4
  lignes) → le délai observé pour `sleep(3)` a été d'environ 12–13 s, soit
  ≈ 3 s × 4. L'injection est donc bien prouvée, mais le délai n'est pas
  strictement égal à l'argument.
- _[capture à insérer]_

### 3.4 UNION
- **Objectif :** greffer le contenu d'une autre table dans le résultat.
- **Où :** `compte.php`, paramètre *id*.
- **Payload :** `0 UNION SELECT id, login, password, 1 FROM users`
- **Effet observé :** la table `users` (login + mot de passe **en clair**) est
  affichée. Le nombre de colonnes (4) doit correspondre à la requête d'origine.
- _[capture à insérer]_

### 3.5 Export `INTO OUTFILE`
- **Objectif :** écrire des données dans un fichier serveur.
- **Où :** console SQL de phpMyAdmin.
- **Payload :**
  ```sql
  SELECT * FROM users INTO OUTFILE 'C:/xampp/mysql/data/fuite.txt';
  ```
- **Effet observé :** bloqué par défaut avec l'erreur
  `#1290 ... --secure-file-priv`. L'écriture n'est autorisée que dans le
  dossier pointé par `secure_file_priv` (vérifiable via
  `SHOW VARIABLES LIKE 'secure_file_priv';`). **C'est une protection native**
  du SGBD ; elle peut être assouplie en labo (cf. README) mais ne doit jamais
  l'être en production.
- _[capture à insérer]_

### 3.6 Récupération du schéma (`information_schema`)
- **Objectif :** découvrir tables, colonnes et types.
- **Où :** phpMyAdmin (ou via UNION).
- **Payload :**
  ```sql
  SELECT table_name, column_name, data_type
  FROM information_schema.columns
  WHERE table_schema = database();
  ```
- **Effet observé :** liste complète de la structure de `banque_test`, dont la
  colonne `users.password`.
- _[capture à insérer]_

### 3.7 Error-based
- **Objectif :** faire fuiter la requête via un message d'erreur.
- **Où :** `compte.php`, paramètre *id* (ou `index.php`).
- **Payload :** `1'`
- **Effet observé :** erreur `#1064 ... syntax` révélant un fragment de la
  requête, utile pour cartographier l'injection.
- _[capture à insérer]_

### 3.8 Suppression de données (requête empilée)
- **Objectif :** exécuter une seconde requête destructrice.
- **Où :** `index.php`, champ *login* (exécution via `mysqli_multi_query`).
- **Payload :** `bob'; DROP TABLE account; -- `
  (variante du sujet : `bob'; DROP DATABASE banque_test; -- `)
- **Effet observé :** la table (ou la base) est supprimée.
- **Restauration :** ré-importer `sql/01_install.sql`.
- _[capture à insérer]_

### 3.9 Mise à jour d'un mot de passe (requête empilée)
- **Objectif :** modifier une donnée sensible.
- **Où :** `index.php`, champ *login*.
- **Payload :** `bob'; UPDATE users SET password='1234' WHERE login='admin'; -- `
- **Effet observé (vérifié en base) :** le mot de passe du compte `admin` est
  passé de `admin123` à `1234`.
- _[capture à insérer]_

### 3.10 Mix / dump complet
- **Objectif :** disposer du schéma + données complet.
- **Où :** phpMyAdmin.
- **Effet observé :** `sql/01_install.sql` recrée l'intégralité de la base
  (équivalent du dump de la capture du sujet), ce qui sert aussi de
  réinitialisation.
- _[capture à insérer]_

---

## 4. Limites des expérimentations

- **Requêtes empilées** (3.8, 3.9) : exploitables ici parce que le code utilise
  `mysqli_multi_query()`. Avec `mysqli_query()` seul, ou avec PDO sans
  reconcaténation, l'empilement n'est pas possible — ce n'est donc pas une
  faille universelle mais dépend de l'API utilisée.
- **Time-based** (3.3) : délai proportionnel au nombre de lignes évaluées, pas
  strictement égal à l'argument de `sleep()`.
- **`INTO OUTFILE`** (3.5) : bloqué par `secure_file_priv` par défaut ; nécessite
  le privilège `FILE` et une configuration permissive.
- **Portabilité :** ces techniques sont propres à MySQL/MariaDB. Un SGBD
  différent (ex. SQLite) ne dispose ni de `sleep()`, ni d'`INTO OUTFILE`, ni
  d'`information_schema`, ni des requêtes empilées via la même API — d'où le
  choix de MariaDB pour ce simulateur.

---

## 5. Protections

### 5.1 Protections déjà mises en œuvre (version sécurisée + app 1)

- **Requêtes préparées / paramétrées** : dans `secure.php` (PDO,
  `prepare`/`execute` avec paramètres liés, `ATTR_EMULATE_PREPARES = false`) et
  dans toute l'app 1 (Flask, `?` paramétrés). La saisie n'est jamais interprétée
  comme du SQL → les payloads 3.1, 3.2, 3.8, 3.9 échouent (testé).
- **Hachage des mots de passe** : `password_hash()` / `password_verify()`
  (bcrypt) dans `secure.php` ; `werkzeug` dans l'app 1. Une fuite de base ne
  livre plus les mots de passe en clair.
- **Contrôle d'accès par rôle** (app 1) : masquage des données sensibles selon
  l'utilisateur, `403` sur les routes réservées.

### 5.2 Protections à mettre en œuvre (sur l'application vulnérable)

- **Passer toutes les requêtes en préparé** (correction n°1 des failles 3.1–3.4,
  3.7–3.9).
- **Hacher les mots de passe** (supprime l'intérêt de 3.4/3.9 en cas de fuite).
- **Compte MySQL applicatif à moindre privilège** : pas de `FILE` (bloque 3.5),
  pas de `DROP`/`ALTER` (limite 3.8) ; un compte dédié en `SELECT/INSERT/UPDATE`
  sur les seules tables nécessaires.
- **Désactiver les requêtes empilées** côté driver (ne pas utiliser
  `multi_query`) : neutralise 3.8 et 3.9.
- **Messages d'erreur génériques en production** : ne pas renvoyer l'erreur SQL
  brute au client (contre 3.7).
- **Validation/typage des entrées** en défense en profondeur (ex. `id` forcé en
  entier), voire un WAF.

---

## 6. Résumé de l'article

> Article : *« From Prompt Injections to SQL Injection Attacks: How Protected is
> Your LLM-Integrated Web Application? »*
>
> _(Section à rédiger : exposer en français, de façon résumée, la problématique
> soulevée par l'article ainsi que les moyens de protection qu'il présente.)_

---

## 7. Conclusion

_(À compléter : synthèse personnelle — ce que le TP a permis de comprendre sur
le mécanisme des injections SQL et sur l'efficacité des requêtes préparées et du
moindre privilège.)_
