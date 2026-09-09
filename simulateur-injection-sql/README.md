# Simulateur de failles — Injection SQL (PHP + MySQL / XAMPP)

Application **volontairement vulnérable** servant de labo pour tester les
techniques d'injection SQL du sujet (comment, OR vrai, time-based, UNION,
`INTO OUTFILE`, `information_schema`, error-based, requêtes empilées
`DROP`/`UPDATE`). Elle est accompagnée d'une **version sécurisée** montrant les
protections à mettre en œuvre.

> ⚠️ **Usage strictement local et pédagogique.** L'app expose des failles
> réelles et utilise des mots de passe en clair. Ne jamais la déployer sur un
> réseau accessible. La base `banque_test` est jetable.

## Prérequis

XAMPP (Apache + MySQL/MariaDB + PHP + phpMyAdmin). Démarrer **Apache** et
**MySQL** depuis le panneau de contrôle XAMPP.

## Installation

1. **Copier le dossier dans `htdocs`** :

   ```bash
   xcopy /E /I "simulateur-injection-sql" "C:\xampp\htdocs\simulateur-injection-sql"
   ```

   (ou copier le dossier à la main dans `C:\xampp\htdocs\`).

2. **Importer la base** via phpMyAdmin (http://localhost/phpmyadmin) :
   onglet *Importer* → choisir `sql/01_install.sql` → exécuter. Cela crée la
   base `banque_test`, les tables `users`, `account` et `users_secure`.

3. Ouvrir **http://localhost/simulateur-injection-sql/**.

> **Si `localhost` sert une autre application** (un vhost Apache « catch-all »
> capte déjà le port 80), sers ce dossier sur un port dédié avec le serveur PHP
> intégré — aucune modification de la config Apache nécessaire :
>
> ```bash
> C:\xampp\php\php.exe -S 127.0.0.1:8099 -t "C:\xampp\htdocs\simulateur-injection-sql"
> ```
>
> puis ouvrir **http://127.0.0.1:8099/index.php**. MariaDB (démarré dans XAMPP)
> reste utilisé de la même façon.

Connexion MySQL par défaut de XAMPP : utilisateur `root`, **sans mot de passe**
(réglage usine local, voir `config.php`). Si ton `root` a un mot de passe,
l'ajouter dans `config.php` **et** dans `secure.php`.

## Pages

| Page | Rôle |
|---|---|
| `index.php` | Login **vulnérable** (login + mot de passe concaténés, `multi_query` → requêtes empilées possibles). Affiche la requête SQL construite. |
| `compte.php` | Recherche de compte par `id` — **contexte numérique** (idéal pour `sleep()`, UNION, error-based). |
| `secure.php` | **Version sécurisée** : PDO + requêtes préparées + `password_verify()` sur hash bcrypt. |
| `techniques.php` | Fiche des 10 techniques : payload exact, où le saisir, effet attendu. |

## Comptes

- Table `users` (en clair, cible des attaques) : `bob/bobpass`, `admin/admin123`,
  `alice/wonderland`, `sqdf/nuisdncom`.
- Table `users_secure` (hachée, pour `secure.php`) : `admin/admin123`, `bob/bobpass`.

## Les 10 techniques (résumé)

Détail et payloads exacts sur la page `techniques.php`. En bref :

1. **Commentaire** — login `bob' -- ` → bypass du mot de passe.
2. **OR vrai** — mot de passe `blabla' OR 1='1`.
3. **Time-based** — `compte.php?id=1-sleep(5)` → réponse retardée.
4. **UNION** — `id = 0 UNION SELECT id, login, password, 1 FROM users`.
5. **INTO OUTFILE** — via phpMyAdmin (voir la note `secure_file_priv` ci-dessous).
6. **information_schema** — dump du schéma (tables/colonnes) via phpMyAdmin.
7. **Error-based** — `id = 1'` → erreur `#1064` révélatrice.
8. **DROP** (empilée) — login `bob'; DROP TABLE account; -- `.
9. **UPDATE mot de passe** (empilée) — login `bob'; UPDATE users SET password='1234' WHERE login='admin'; -- `.
10. **Mix** — `sql/01_install.sql` = dump complet (recrée tout).

### Réinitialiser après un test destructeur

Ré-importer `sql/01_install.sql` (il fait `DROP DATABASE IF EXISTS` puis
recrée tout). En ligne de commande :

```bash
C:\xampp\mysql\bin\mysql.exe -u root < sql\01_install.sql
```

### Note sur `INTO OUTFILE` (`#1290`)

Par défaut MySQL/MariaDB sous XAMPP limite l'écriture de fichiers au dossier
`secure_file_priv` (souvent `C:/xampp/mysql/data/` ou vide). D'où l'erreur
`#1290 ... --secure-file-priv`. Vérifier la valeur avec :

```sql
SHOW VARIABLES LIKE 'secure_file_priv';
```

Écrire alors dans le dossier autorisé. Pour lever la restriction **en labo
uniquement**, éditer `C:\xampp\mysql\bin\my.ini`, mettre `secure_file_priv=`
(vide) sous `[mysqld]`, puis redémarrer MySQL. À **remettre** après le TP.

## Protections démontrées (`secure.php`)

- **Requêtes préparées (PDO)** avec paramètres liés : la saisie n'est jamais
  concaténée au SQL → 1, 2, 8, 9 échouent. `ATTR_EMULATE_PREPARES = false`
  pour une vraie préparation côté serveur (cf. article *SQL Injection in PDO's
  Prepared Statements* sur les limites de l'émulation).
- **Hachage des mots de passe** (`password_hash` / `password_verify`) : plus
  aucun mot de passe en clair, une fuite de base ne livre pas les mots de passe.
- Autres bonnes pratiques à mentionner dans le compte rendu : moindre privilège
  du compte MySQL applicatif (pas de `FILE`, pas de `DROP`), désactivation des
  requêtes empilées côté driver, messages d'erreur génériques en production.
