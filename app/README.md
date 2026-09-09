# Gestionnaire de Projet — démo de l'exercice de normalisation (Partie 1)

Petite application web (Python/Flask/SQLite) illustrant le schéma en 3ᵉ forme
normale obtenu dans `Exo sécurrisation des données.docx` (à partir du sujet
`Sujet_Exercice2.pdf`). C'est une **démo propre du schéma**, avec des bonnes
pratiques appliquées par défaut (requêtes paramétrées, mots de passe hachés,
contrôle d'accès par rôle).

> La démonstration des **failles d'injection SQL** est traitée dans l'autre
> application du dépôt : `../simulateur-injection-sql/` (PHP/MySQL).

## Schéma

Décomposition 3FN de la table `Projet` d'origine :

- `Departement(deptEmp, #mgrEmp)`
- `Employe(idEmp, nomEmp, salEmp, #deptEmp)`
- `Projet(nomProj, #mgrProj, budget, dateDebut)`
- `PerformanceEmp(#nomProj, #idEmp, heures, evalEmp)`

**Un manager est un employé.** `mgrProj` et `mgrEmp` sont donc des clés
étrangères vers `Employe(idEmp)`, et non des noms : l'énoncé précise que les
noms des employés et des managers ne sont pas uniques, un nom ne peut donc pas
identifier une personne. L'instance de l'énoncé ne donnant que les noms des
managers, `seed.py` leur attribue un `idEmp` (Dupont E200, Jones E201,
Holmes E202, Lupin E203) et un salaire, afin de pouvoir les référencer.

`Departement.mgrEmp` est nullable : cela autorise un département sans manager
et rompt le cycle `Departement` ↔ `Employe` au chargement (les départements
sont insérés d'abord, puis rattachés à leur manager une fois les employés créés).

S'y ajoute une table `Utilisateur(idUtilisateur, username, motDePasseHash, #idEmp)`
pour la connexion, absente des dépendances fonctionnelles d'origine. Le statut
de manager n'y est **pas** stocké : il se déduit de `Projet.mgrProj` et
`Departement.mgrEmp`, pour ne pas dupliquer une information déjà en base, et il
est relu à chaque requête plutôt que figé dans la session.

## Installation

```bash
cd app
python -m venv .venv
.venv\Scripts\activate
pip install -r requirements.txt
python seed.py
```

`seed.py` crée `instance/gestion_projet.db` et le peuple avec les données de
l'énoncé (projets ILO/MAXI, employés E101/E105/E110 + les managers) ainsi que
des comptes de démo.

## Lancer l'app

```bash
python app.py
```

Puis ouvrir http://127.0.0.1:5000. Comptes :

| Utilisateur | Mot de passe | Employé | Manager ? |
|---|---|---|---|
| dupont | dupont123 | E200 | oui — dirige le projet ILO |
| holmes | holmes123 | E202 | oui — dirige le département 10 |
| durand | durand123 | E101 | non |
| adam | adam123 | E105 | non |
| rivera | rivera123 | E110 | non |

Un manager voit tous les salaires/évaluations et la liste des employés.
Un employé ne voit que sa propre évaluation, jamais son salaire ni celui des
autres sur la page projet, et n'a pas accès à `/employees` (403).

## Bonnes pratiques appliquées

- **Requêtes paramétrées** partout (aucune concaténation de saisie dans le SQL).
- **Mots de passe hachés** (`werkzeug.generate_password_hash` / `check_password_hash`).
- **Contrôle d'accès par rôle** : rôle déduit de la base, masquage des colonnes
  sensibles selon l'utilisateur, `403` sur les routes réservées au manager.

## Portée

Projet scolaire minimal : pas de framework JS, pas d'ORM, session Flask
manuelle. La protection CSRF et le durcissement de production sont hors
périmètre volontairement.
