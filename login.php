<?php
require_once 'inc/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (login($_POST['username'], $_POST['password'])) {
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Identifiants incorrects.';
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Formulaire central */
        .login-container {
            max-width: 400px;
            margin: 5rem auto;
            padding: 2rem;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            text-align: center;
        }

        .login-container h2 {
            margin-bottom: 1.5rem;
            color: #2563eb;
        }

        .login-container input {
            width: 100%;
            margin-bottom: 1rem;
            padding: 0.6rem;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
        }

        .login-container button {
            width: 100%;
            padding: 0.7rem;
            border-radius: 8px;
            background-color: #2563eb;
            color: #ffffff;
            font-weight: 500;
            border: none;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: background-color 0.3s, transform 0.2s;
        }

        .login-container button:hover {
            background-color: #1e40af;
            transform: translateY(-2px);
        }

        .error-msg {
            color: #ef4444;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Connexion</h2>
        <?php if ($error): ?>
            <p class="error-msg"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form method="post">
            <input type="text" name="username" placeholder="Identifiant" required>
            <input type="password" name="password" placeholder="Mot de passe" required>
            <button type="submit">Se connecter</button>
        </form>
    </div>
</body>
</html>
