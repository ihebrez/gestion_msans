<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

require 'db.php'; // Connexion PDO

$stmt = $pdo->prepare("SELECT username, email, password FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newUsername = trim($_POST['username'] ?? '');
    $newEmail = trim($_POST['email'] ?? '');

    // Champs mdp
    $currentPass = $_POST['current_password'] ?? '';
    $newPass = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    // Mise à jour username/email
    if ($newUsername === '' || $newEmail === '') {
        $error = 'Veuillez remplir les champs nom d\'utilisateur et email.';
    } else {
        // Mise à jour username/email
        $updateUser = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
        $updateUser->execute([$newUsername, $newEmail, $_SESSION['user_id']]);
        $_SESSION['username'] = $newUsername;
        $user['username'] = $newUsername;
        $user['email'] = $newEmail;
        $message = 'Profil mis à jour avec succès.';
    }

    // Si utilisateur veut changer de mot de passe (au moins un des champs mdp rempli)
    if ($currentPass !== '' || $newPass !== '' || $confirmPass !== '') {
        if ($currentPass === '' || $newPass === '' || $confirmPass === '') {
            $error = 'Veuillez remplir tous les champs de mot de passe pour le changement.';
        } else if (!password_verify($currentPass, $user['password'])) {
            $error = 'Le mot de passe actuel est incorrect.';
        } else if ($newPass !== $confirmPass) {
            $error = 'Le nouveau mot de passe et sa confirmation ne correspondent pas.';
        } else if (strlen($newPass) < 6) {
            $error = 'Le nouveau mot de passe doit contenir au moins 6 caractères.';
        } else {
            // Mise à jour du mot de passe
            $newPassHash = password_hash($newPass, PASSWORD_DEFAULT);
            $updatePass = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $updatePass->execute([$newPassHash, $_SESSION['user_id']]);
            $message = 'Mot de passe mis à jour avec succès.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Paramètres Admin - Ultra Moderne</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style>
        /* Même style que précédemment */
        * {
            margin: 0; padding: 0; box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        body, html {
            height: 100%;
            background: #1e0f0f;
            color: #f5e9e0;
            display: flex;
            overflow: hidden;
        }

        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #4e1a00, #a64000, #ff5a00);
            display: flex;
            flex-direction: column;
            padding: 30px 25px;
            box-shadow: 4px 0 12px rgba(255, 90, 0, 0.7);
            position: fixed;
            height: 100%;
            transition: width 0.3s ease;
            user-select: none;
        }
        .sidebar h2 {
            font-weight: 900;
            font-size: 2.4rem;
            letter-spacing: 2px;
            margin-bottom: 40px;
            color: #ffe5b4;
            text-align: center;
            text-transform: uppercase;
            text-shadow: 0 0 12px #ffa840aa;
        }
        .sidebar nav {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            gap: 18px;
        }
        .sidebar nav a {
            text-decoration: none;
            color: #f5e9e0;
            font-weight: 600;
            font-size: 1.15rem;
            padding: 14px 22px;
            border-radius: 12px;
            background: transparent;
            display: flex;
            align-items: center;
            gap: 14px;
            transition: all 0.3s ease;
            box-shadow: inset 0 0 0 0 #ff7f50;
        }
        .icon {
            display: inline-block;
            width: 20px; height: 20px;
            background: #ff7f50;
            border-radius: 4px;
            box-shadow: 0 0 10px #ff7f50aa;
        }
        .sidebar nav a:hover {
            color: #1e0f0f;
            background: #ff7f50;
            box-shadow: inset 0 0 10px #ff7f50;
            transform: scale(1.05);
        }
        .sidebar nav a.logout {
            margin-top: auto;
            background: #e63946;
            color: white;
            font-weight: 700;
            box-shadow: 0 0 15px #e63946bb;
        }
        .sidebar nav a.logout:hover {
            background: #c62828;
            box-shadow: 0 0 20px #c62828aa;
            transform: scale(1.1);
        }

        .main-content {
            margin-left: 280px;
            padding: 40px 50px;
            flex-grow: 1;
            background: linear-gradient(135deg, #3c1a0a, #1e0f0f);
            overflow-y: auto;
            border-radius: 0 40px 40px 0;
            box-shadow: inset 8px 0 30px rgba(255, 127, 80, 0.25);
            animation: fadeInContent 0.6s ease forwards;
        }
        .main-content h1 {
            font-size: 2.8rem;
            font-weight: 900;
            color: #ffb347;
            margin-bottom: 18px;
            text-shadow: 0 0 15px #ffb347aa;
        }
        form {
            max-width: 420px;
            background: #2e1a0a;
            padding: 30px 25px;
            border-radius: 20px;
            box-shadow: 0 0 20px #ff7f50aa;
            color: #ffe5b4;
        }
        label {
            display: block;
            font-weight: 700;
            margin-bottom: 8px;
            margin-top: 15px;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border-radius: 12px;
            border: none;
            font-size: 1rem;
            margin-bottom: 10px;
            background: #4e1a00;
            color: #f5e9e0;
            box-shadow: inset 0 0 8px #ff7f50aa;
        }
        button {
            margin-top: 20px;
            background: #ff7f50;
            border: none;
            padding: 14px 25px;
            font-size: 1.2rem;
            font-weight: 700;
            border-radius: 15px;
            color: #1e0f0f;
            cursor: pointer;
            box-shadow: 0 0 15px #ff7f50bb;
            transition: background 0.3s ease, transform 0.2s ease;
        }
        button:hover {
            background: #ff9a68;
            transform: scale(1.05);
        }
        .message {
            margin-top: 15px;
            font-weight: 700;
            color: #a1ff9a;
            text-shadow: 0 0 10px #a1ff9aaa;
        }
        .error {
            margin-top: 15px;
            font-weight: 700;
            color: #ff6b6b;
            text-shadow: 0 0 10px #ff6b6baa;
        }
        @keyframes fadeInContent {
            from {opacity: 0; transform: translateY(10px);}
            to {opacity: 1; transform: translateY(0);}
        }

        @media (max-width: 720px) {
            .sidebar {
                width: 60px;
                padding: 20px 10px;
            }
            .sidebar h2 {
                display: none;
            }
            .sidebar nav a {
                justify-content: center;
                padding: 12px 8px;
                font-size: 0;
            }
            .sidebar nav a .icon {
                width: 28px;
                height: 28px;
                box-shadow: 0 0 15px #ff7f50aa;
            }
            .main-content {
                margin-left: 60px;
                padding: 30px 20px;
                border-radius: 0;
            }
        }
    </style>
</head>
<body>
    <aside class="sidebar" aria-label="Navigation principale">
        <h2>ADMIN</h2>
        <nav>
            <a href="dashboard.php">
                <span class="icon" aria-hidden="true"></span>
                Dashboard
            </a>
            <a href="messagerie.php">
                <span class="icon" aria-hidden="true"></span>
                Messagerie
            </a>
            <a href="profile.php">
                <span class="icon" aria-hidden="true"></span>
                Paramètres
            </a>
            <a href="acces_utilisateurs.php">
                <span class="icon" aria-hidden="true"></span>
                Accès utilisateurs
            </a>
            <a href="logout.php" class="logout">
                <span class="icon" aria-hidden="true"></span>
                Déconnexion
            </a>
        </nav>
    </aside>

    <main class="main-content" role="main" tabindex="-1">
        <h1>Paramètres de <?= htmlspecialchars($_SESSION['username']) ?></h1>

        <?php if ($message): ?>
            <p class="message"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="POST" action="profile.php" novalidate>
            <label for="username">Nom d'utilisateur</label>
            <input type="text" id="username" name="username" required value="<?= htmlspecialchars($user['username'] ?? '') ?>" />

            <label for="email">Email</label>
            <input type="email" id="email" name="email" required value="<?= htmlspecialchars($user['email'] ?? '') ?>" />

            <hr style="margin: 25px 0; border: 1px solid #ff7f50aa; border-radius: 4px;" />

            <label for="current_password">Mot de passe actuel</label>
            <input type="password" id="current_password" name="current_password" autocomplete="current-password" placeholder="Laisser vide pour ne pas changer" />

            <label for="new_password">Nouveau mot de passe</label>
            <input type="password" id="new_password" name="new_password" autocomplete="new-password" placeholder="Laisser vide pour ne pas changer" />

            <label for="confirm_password">Confirmer nouveau mot de passe</label>
            <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" placeholder="Laisser vide pour ne pas changer" />

            <button type="submit">Mettre à jour</button>
        </form>
    </main>
</body>
</html>
