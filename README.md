# Excalidraw PHP

Une application de dessin collaboratif inspirée d'Excalidraw, développée en **PHP**. Elle permet de créer, sauvegarder, visualiser et gérer des dessins en ligne avec un système de gestion des utilisateurs.

---

## 🚀 Fonctionnalités

### ⚙️ Générale
- 🔐 Authentification utilisateur (connexion / déconnexion)  (`login.php`, `logout.php`)
- 🛡️ Protection des répertoires sensibles via `.htaccess` (`inc/`)  

### ⚙️ Tableau de bord (`dashboard.php`)  
- 📊 Affichage des différents outils
- 🔧 Affichage de l'outil "Gérer les verrous" (mode admin)

### 🎨 Outil de dessin (`dessin.php`)
- 🔒 Verrouillage de l’outil de dessin pour éviter les conflits (système de lock)  
- ✏️ Création de dessins via une interface interactive  
- 💾 Sauvegarde des dessins aux formats **PNG**, **SVG** et **JSON**  
- 📂 Import et modification de dessins existants (fichiers JSON)  

### 👁️ Visualisation des dessins (`fichiers.php`)
- 📑 Visualisation et téléchargement des fichiers enregistrés (`dessins/`)  
- 🛠️ Gestion des fichiers enregistrés (`dessins/`) *(mode admin)*  

### 📡 Session live (`voir.php`)
- 🎥 Visualisation **en temps réel** des dessins générés par un admin  

### 🔧 Gestion du locking (`manage_lock.php`)
- 🗑️ Suppression manuelle du verrouillage en cas de conflit *(mode admin)*  

---

## 📂 Structure du projet

```
excalidraw/
├── index.php              # Page d'accueil / login
├── dashboard.php          # Tableau de bord des utilisateurs
├── delete_dessin.php      # Suppression de dessins
├── dessin.php             # Interface de dessin
├── fichiers.php           # Gestion des fichiers
├── heartbeat_lock.php     # Vérification/verrouillage concurrentiel
├── login.php              # Page de connexion
├── logout.php             # Déconnexion
├── manage_lock.php        # Suppression du fichier lock en cas de conflit
├── page_ouverte.php       # Gestion des sessions de dessin ouvertes
├── release_lock.php       # Libération des verrous
├── save_dessin.php        # Sauvegarde des dessins
├── save_json.php          # Sauvegarde au format JSON
├── view_dessin.php        # Visualisation des dessins
├── voir.php               # Visualisation en live des dessins
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
