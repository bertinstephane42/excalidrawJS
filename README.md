# ExcalidrawJS

![License](https://img.shields.io/badge/license-MIT-green.svg)
![Fabric.js](https://img.shields.io/badge/Fabric.js-v5.2.4-blue)
![HTML5 Canvas](https://img.shields.io/badge/Canvas-HTML5-orange)

**ExcalidrawJS** est un éditeur de dessin en ligne inspiré d’Excalidraw, construit avec **Fabric.js** et HTML5 Canvas. Il permet de créer des diagrammes, des flèches, du texte, et des formes géométriques, avec une expérience proche d’outils de dessin vectoriel simples.  

---

## Table des matières

- [Fonctionnalités](#fonctionnalités)
- [Démonstration](#démonstration)
- [Installation](#installation)
- [Utilisation](#utilisation)
- [Structure du projet](#structure-du-projet)
- [Outils et technologies](#outils-et-technologies)
- [Personnalisation](#personnalisation)
- [Contribution](#contribution)
- [Licence](#licence)

---

## Fonctionnalités

- Dessin de formes simples : rectangle, ellipse, losange
- Création de lignes et flèches (droites, courbées, coudées)
- Texte éditable et personnalisable (police, couleur, taille)
- Dessin libre à main levée
- Zoom, déplacement et panning via ALT + souris
- Aimantation à la grille (snap-to-grid) pour déplacer et redimensionner
- Calques : amener au premier plan, renvoyer à l’arrière
- Duplication et suppression d’objets
- Undo / Redo avec historique limité
- Import / Export JSON pour sauvegarder et charger les dessins
- Export PNG et SVG
- Modale d’aide intégrée pour les utilisateurs

---

## Démonstration

Pour tester localement :

1. Cloner le projet :  
   ```bash
   git clone https://github.com/votre-utilisateur/excalidrawJS.git
````

2. Ouvrir `index.html` dans un navigateur moderne (Chrome, Firefox, Edge)
3. Commencer à dessiner !

> Optionnel : si vous souhaitez un serveur local pour tester l’export côté serveur, configurez un serveur PHP ou Node.js.

---

## Installation

### Prérequis

* Navigateur moderne compatible HTML5 Canvas
* PHP 7+ si utilisation de la sauvegarde serveur (optionnelle)
* Accès à Internet pour le CDN Fabric.js (ou installer localement)

### Étapes

1. Télécharger ou cloner le dépôt :

   ```bash
   git clone https://github.com/votre-utilisateur/excalidrawJS.git
   ```
2. Ouvrir `index.html` dans le navigateur.
3. Si vous utilisez les fonctionnalités serveur, configurer `save_dessin.php` et le dossier `dessins/`.

---

## Utilisation

### Outils et raccourcis

| Outil       | Raccourci       | Description                                      |
| ----------- | --------------- | ------------------------------------------------ |
| Sélection   | V               | Déplacer, redimensionner et supprimer des objets |
| Rectangle   | R               | Dessiner un rectangle                            |
| Ellipse     | O               | Dessiner une ellipse                             |
| Losange     | D               | Dessiner un losange                              |
| Ligne       | L               | Dessiner une ligne                               |
| Flèche      | A               | Dessiner une flèche (droite, courbée, coudée)    |
| Crayon      | P               | Dessin libre à main levée                        |
| Texte       | T               | Ajouter et éditer du texte                       |
| Undo / Redo | Ctrl+Z / Ctrl+Y | Annuler / rétablir la dernière action            |
| Dupliquer   | Ctrl+D          | Dupliquer l’objet sélectionné                    |
| Supprimer   | Suppr           | Supprimer l’objet sélectionné                    |

### Export / Import

* **Exporter JSON** : sauvegarde complète du dessin pour rechargement ultérieur
* **Importer JSON** : charger un dessin précédemment sauvegardé
* **Exporter PNG / SVG** : exporter le dessin pour utilisation externe ou impression

---

## Structure du projet

```
excalidrawJS/
│
├─ index.html        # Page principale et canvas
├─ save_dessin.php   # (Optionnel) sauvegarde côté serveur
├─ dessins/          # Dossier pour stocker les fichiers JSON/PNG/SVG
├─ inc/              # Fichiers PHP inclus (auth, header)
└─ README.md         # Documentation
```

* Le code JS est intégré directement dans `index.html` mais peut être extrait dans un fichier séparé `main.js` pour modularité.
* La logique de dessin repose sur **Fabric.js v5**.
* La grille, l’aimantation, et les flèches utilisent des événements `object:moving` et `object:scaling`.

---

## Outils et technologies

* [Fabric.js](http://fabricjs.com/) : gestion du canvas et des objets vectoriels
* HTML5 Canvas
* CSS3 pour interface et modale d’aide
* JavaScript ES6+
* PHP 7+ (optionnel pour sauvegarde côté serveur)

---

## Personnalisation

* **Grille** : modifier `const grid = 20;` pour changer l’espacement
* **Couleurs par défaut** : changer `strokeColor` et `fillColor`
* **Polices** : ajouter de nouvelles options dans `<select id="fontFamily">`
* **Taille du canvas** : modifier `CANVAS_MIN_WIDTH` et `CANVAS_MIN_HEIGHT`

---

## Contribution

Contributions bienvenues !

1. Fork le projet
2. Créez une branche pour votre feature :

   ```bash
   git checkout -b feature/nom-feature
   ```
3. Committez vos modifications :

   ```bash
   git commit -m "Ajout d'une nouvelle fonctionnalité"
   ```
4. Poussez votre branche :

   ```bash
   git push origin feature/nom-feature
   ```
5. Ouvrez un Pull Request

---

## Licence

MIT License © 2025 – Stephane Bertin
Voir [LICENSE](LICENSE) pour plus de détails.

---

## Remarques

* Optimisé pour une utilisation pédagogique ou rapide de diagrammes
* Ne gère pas encore les fonctionnalités avancées type Excalidraw : export en PDF, collaboration en temps réel
* La sécurité des fichiers importés/exportés doit être vérifiée si déployé en production
