# Excalidraw PHP

Une application de dessin collaboratif inspirée d'Excalidraw, développée en **PHP**. Elle permet de créer, sauvegarder, visualiser et gérer des dessins en ligne avec un système de gestion des utilisateurs.

---

## 🚀 Fonctionnalités

* Authentification utilisateur (connexion / déconnexion)
* Création de dessins via une interface interactive
* Sauvegarde des dessins au format JSON
* Gestion des fichiers enregistrés (`dessins/`, `json/`)
* Verrouillage des ressources pour éviter les conflits (système de lock)
* Tableau de bord des dessins
* Suppression et modification de dessins existants
* Protection des répertoires sensibles via `.htaccess`
* Visualisation en live des dessins générés par un admin

---

## 📂 Structure du projet

```
excalidraw/
├── index.php              # Page d'accueil / login
├── dashboard.php          # Tableau de bord des utilisateurs
├── dessin.php             # Interface de dessin
├── save_dessin.php        # Sauvegarde des dessins
├── save_json.php          # Sauvegarde au format JSON
├── delete_dessin.php      # Suppression de dessins
├── view_dessin.php        # Visualisation des dessins
├── login.php              # Page de connexion
├── logout.php             # Déconnexion
├── fichiers.php           # Gestion des fichiers
├── page_ouverte.php       # Gestion des sessions de dessin ouvertes
├── heartbeat_lock.php     # Vérification/verrouillage concurrentiel
├── release_lock.php       # Libération des verrous
│
├── css/
│   └── style.css          # Feuilles de style
│
├── dessins/               # Répertoire des dessins sauvegardés
│   └── .htaccess          # Protection d'accès
│
├── json/                  # Sauvegardes JSON
│   └── .htaccess
│
├── lock/                  # Gestion des fichiers de verrouillage
│   └── .htaccess
│
└── inc/
    ├── auth.php           # Gestion de l'authentification
    ├── users.php          # Gestion des utilisateurs
    ├── header.php         # Header commun
    └── .htaccess
```

---

## ⚙️ Installation

### Prérequis

* Serveur web (Apache, Nginx, etc.)
* PHP 7.4 ou supérieur
* Accès à un système de fichiers pour stocker les dessins et JSON

### Étapes

1. Cloner le dépôt :

   ```bash
   git clone https://github.com/USERNAME/excalidraw-php.git
   cd excalidraw-php/excalidraw
   ```

2. Déployer le dossier `excalidraw/` dans votre serveur web (ex: `/var/www/html/excalidraw`).

3. Vérifier que les dossiers suivants sont accessibles en écriture :

   * `dessins/`
   * `json/`
   * `lock/`

4. Configurer un utilisateur dans `inc/users.php` si nécessaire.

5. Accéder à l’application via :

   ```
   http://localhost/excalidraw/
   ```

---

## 🔑 Authentification

* L’application utilise un système de connexion basique via `login.php` et `logout.php`.
* Les informations utilisateurs sont gérées dans `inc/users.php`.

---

## 📌 Sécurité

* Les répertoires sensibles (`dessins/`, `json/`, `lock/`, `inc/`) sont protégés par `.htaccess`.
* Vérifier la configuration de votre serveur Apache pour que la directive `AllowOverride All` soit activée.

---

## 🤝 Contribution

Les contributions sont les bienvenues :

1. Forkez le projet
2. Créez une branche (`git checkout -b feature/nouvelle-fonctionnalite`)
3. Committez vos modifications (`git commit -m 'Ajout d'une nouvelle fonctionnalité'`)
4. Poussez la branche (`git push origin feature/nouvelle-fonctionnalite`)
5. Ouvrez une Pull Request

---

## 📄 Licence

Ce projet est distribué sous licence **MIT**. Vous êtes libre de l'utiliser, le modifier et le redistribuer.
