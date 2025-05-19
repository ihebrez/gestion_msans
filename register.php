<?php
require 'db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $email === '' || $password === '') {
        $message = "Veuillez remplir tous les champs.";
    } else {
        // Vérifier si email ou username existe déjà
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        if ($stmt->fetch()) {
            $message = "Ce nom d'utilisateur ou email est déjà utilisé.";
        } else {
            // Vérifier s'il y a déjà des utilisateurs
            $stmt = $pdo->query("SELECT COUNT(*) FROM users");
            $userCount = $stmt->fetchColumn();

            // Premier utilisateur = admin, sinon user
            $role = ($userCount == 0) ? 'admin' : 'user';

            // Hasher le mot de passe
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            // Insertion en base
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$username, $email, $passwordHash, $role])) {
                $message = "Inscription réussie, vous pouvez vous connecter.";
            } else {
                $message = "Erreur lors de l'inscription.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Inscription</title>
    <style>
        /* (Ton CSS inchangé) */
        * {
            margin: 0; padding: 0; box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #eee;
        }
        .container {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            padding: 40px 45px;
            border-radius: 25px;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            max-width: 400px;
            width: 100%;
            text-align: center;
            color: #fff;
            transition: box-shadow 0.3s ease;
        }
        .container:hover {
            box-shadow: 0 12px 48px rgba(255, 255, 255, 0.25);
        }
        h2 {
            font-size: 2.4rem;
            font-weight: 900;
            margin-bottom: 30px;
            letter-spacing: 1.4px;
            text-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .error {
            background-color: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
            padding: 12px 15px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-weight: 700;
            font-size: 1rem;
            box-shadow: 0 2px 8px rgba(231, 76, 60, 0.3);
            text-shadow: 0 1px 1px rgba(0,0,0,0.1);
        }
        form {
            display: flex;
            flex-direction: column;
            text-align: left;
        }
        label {
            font-weight: 600;
            margin-bottom: 6px;
            font-size: 1rem;
            color: #eee;
            text-shadow: 0 1px 2px rgba(0,0,0,0.2);
        }
        input[type="text"],
        input[type="email"],
        input[type="password"] {
            padding: 14px 18px;
            margin-bottom: 25px;
            border: none;
            border-radius: 14px;
            font-size: 1rem;
            background: rgba(255, 255, 255, 0.3);
            color: #fff;
            transition: background-color 0.3s ease, box-shadow 0.3s ease;
            box-shadow: inset 0 0 5px rgba(255,255,255,0.3);
        }
        input::placeholder {
            color: rgba(255,255,255,0.7);
            font-style: italic;
        }
        input:focus {
            outline: none;
            background-color: rgba(255, 255, 255, 0.5);
            box-shadow: 0 0 8px 2px #9b59b6;
            color: #222;
        }
        button {
            padding: 14px 18px;
            background: linear-gradient(135deg, #8e44ad, #6a11cb);
            color: white;
            font-weight: 700;
            font-size: 1.1rem;
            border: none;
            border-radius: 14px;
            cursor: pointer;
            transition: background 0.4s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 15px rgba(106, 17, 203, 0.5);
        }
        button:hover {
            background: linear-gradient(135deg, #6a11cb, #8e44ad);
            box-shadow: 0 6px 20px rgba(106, 17, 203, 0.8);
        }
        .info {
            margin-top: 20px;
            font-size: 1rem;
            color: #ddd;
            text-align: center;
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
        }
        .info a {
            color: #d1b3ff;
            font-weight: 700;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .info a:hover {
            color: #fff;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Inscription</h2>
        <?php if ($message): ?>
            <p class="error"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <form method="POST" action="">
            <label for="username">Nom d'utilisateur :</label>
            <input type="text" id="username" name="username" placeholder="Votre nom d'utilisateur" required />

            <label for="email">Email :</label>
            <input type="email" id="email" name="email" placeholder="exemple@mail.com" required />

            <label for="password">Mot de passe :</label>
            <input type="password" id="password" name="password" placeholder="Votre mot de passe" required />

            <!-- Champ rôle supprimé -->

            <button type="submit">S'inscrire</button>
        </form>
        <p class="info">Déjà un compte ? <a href="login.php">Se connecter</a></p>
    </div>
</body>
</html>
