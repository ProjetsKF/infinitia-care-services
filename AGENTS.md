# AGENTS.md

## Projet

Nom : INFINITIA Care Services

Application web développée en PHP procédural pour la gestion des services d'assistance à domicile.

---

## Environnement technique

* PHP 5.6.30
* MySQL 5.7.x
* Apache 2.4.x
* EasyPHP Devserver
* Materialize CSS
* JavaScript natif
* mysqli uniquement

---

## Contraintes

* Ne jamais utiliser PDO.
* Ne jamais utiliser de framework PHP.
* Ne jamais utiliser MVC.
* Ne jamais utiliser les fonctionnalités de PHP 7 ou PHP 8.

Exemples interdits :

* ??
* ??=
* match
* types scalaires
* return type
* propriétés typées
* arrow functions

Le code doit rester compatible PHP 5.6.

---

## Base de données

Utiliser uniquement :

```php
$conn
```

Les requêtes doivent utiliser des requêtes préparées mysqli.

Ne jamais modifier la structure de la base de données sans autorisation.

---

## Style de développement

* Conserver l'organisation actuelle du projet.
* Modifier uniquement les fichiers nécessaires.
* Éviter les modifications inutiles.
* Réutiliser les composants existants.
* Ajouter des commentaires uniquement lorsqu'ils apportent une réelle valeur.

---

## Interface

Utiliser Materialize CSS.

Conserver le style graphique existant.

Ne pas remplacer Materialize par Bootstrap ou un autre framework.

---

## Sécurité

* Utiliser des requêtes préparées.
* Vérifier les sessions.
* Vérifier les rôles utilisateur.
* Vérifier les autorisations avant toute modification ou suppression.
* Utiliser POST pour les actions sensibles.
* Ne pas afficher les erreurs PHP en production.

---

## Git

Ne jamais effectuer automatiquement :

* git commit
* git push
* git merge
* git rebase

sans validation explicite de l'utilisateur.

---

## Avant toute modification

Toujours :

1. analyser les fichiers concernés ;
2. expliquer brièvement les modifications proposées ;
3. limiter les changements au strict nécessaire.

---

## Objectif

Produire un code :

* propre ;
* lisible ;
* maintenable ;
* compatible PHP 5.6 ;
* cohérent avec l'architecture actuelle du projet.
