# Correctifs PHP du 21 octobre 2026

L'[alerte officielle PHP](https://www.php.net/archive/2026.php#2026-10-21-5)
concerne aussi les consoles d'administration et les runtimes en ligne de commande.

L'image applicative de développement et de production utilise PHP **8.2.35**.
La production sur manager a été remplacée et sa version contrôlée via Apache
le 21 octobre 2026. Les extensions et les capacités GD ont été conservées.

La console phpMyAdmin facultative est construite séparément avec PHP **8.3.36**.
Son cœur et toutes ses extensions natives fournies par PHP sont reconstruits
depuis l'archive officielle vérifiée par SHA-256 ; l'extension tierce PECL
`uploadprogress` est conservée. Le profil de production `debug` reste facultatif.

Les anciennes images de production sont conservées pour retour arrière.
Les conteneurs de développement précédemment arrêtés sont recréés avec les
images corrigées sans démarrer les traitements métier.
