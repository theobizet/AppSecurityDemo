# TP Sécurisation des données — deux applications

Ce dépôt regroupe les deux livrables du TP, séparés :

| Partie | Dossier | Stack | Objet |
|---|---|---|---|
| **1. Démo de l'exercice** | [`app/`](app/) | Python / Flask / SQLite | Le schéma de l'Exercice 2 décomposé en 3ᵉ forme normale, présenté dans un petit gestionnaire de projet (login, rôles, requêtes paramétrées). Démo **propre**. |
| **2. Simulateur de failles** | [`simulateur-injection-sql/`](simulateur-injection-sql/) | PHP / MySQL (XAMPP) | Application **volontairement vulnérable** pour tester les 10 techniques d'injection SQL du sujet, + une version sécurisée (PDO + hachage). |

Les documents sources de l'exercice (`Sujet_Exercice2.pdf`,
`Exo sécurrisation des données.docx`) sont à la racine.

## Partie 1 — `app/`

Démo web du schéma 3FN (tables `Departement`, `Employe`, `Projet`,
`PerformanceEmp`). Installation et lancement détaillés dans
[`app/README.md`](app/README.md). En bref :

```bash
cd app
python -m venv .venv && .venv\Scripts\activate
pip install -r requirements.txt
python seed.py
python app.py        # http://127.0.0.1:5000
```

## Partie 2 — `simulateur-injection-sql/`

Labo d'injection SQL sous XAMPP (Apache + MariaDB + PHP). Reproduit
commentaire, OR vrai, time-based, UNION, `INTO OUTFILE`, `information_schema`,
error-based, requêtes empilées `DROP`/`UPDATE`. Installation, import de la base
et fiche des payloads dans
[`simulateur-injection-sql/README.md`](simulateur-injection-sql/README.md).

> ⚠️ Application **volontairement vulnérable**, à usage **strictement local**.
> Ne jamais l'exposer sur un réseau. Base jetable.

## Rappel des livrables du TP

Au-delà du code, le sujet demande aussi un **compte rendu** des expérimentations
(techniques testées, limites, protections en place et à mettre en œuvre) et un
**résumé d'article** en français. Ces documents rédigés restent à ta charge.
