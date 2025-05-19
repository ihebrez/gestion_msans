<?php
session_start();

// Accès seulement admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$host = 'localhost';
$db = 'msan';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Supprimer utilisateur sauf admin principal id=1
if (isset($_GET['delete'])) {
    $idToDelete = (int)$_GET['delete'];
    if ($idToDelete !== 1) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$idToDelete]);
    }
    header('Location: acces_utilisateurs.php');
    exit();
}

// Changer rôle utilisateur sauf admin principal id=1
if (isset($_GET['toggle_role'])) {
    $idToToggle = (int)$_GET['toggle_role'];
    if ($idToToggle !== 1) {
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$idToToggle]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $newRole = ($user['role'] === 'admin') ? 'user' : 'admin';
            $update = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            $update->execute([$newRole, $idToToggle]);
        }
    }
    header('Location: acces_utilisateurs.php');
    exit();
}

// Récupérer utilisateurs
$stmt = $pdo->query("SELECT id, username, email, role FROM users ORDER BY id DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Gestion des utilisateurs</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #1e0f0f; color: #f5e9e0; padding: 40px; }
        h1 { color: #ffb347; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; background: #2c1a1a; border-radius: 12px; overflow: hidden; }
        th, td { padding: 14px 20px; border-bottom: 1px solid #444; text-align: left; }
        th { background: #ff5722; color: white; }
        tr:hover { background: #3e2723; }
        a.button { background: #ff7043; color: white; padding: 6px 14px; border-radius: 8px; text-decoration: none; font-weight: 600; transition: background 0.3s ease; }
        a.button:hover { background: #ff5722; }
        a.delete { background: #e53935; }
        a.delete:hover { background: #c62828; }
        span.protected { color: #999999; font-style: italic; }
    </style>
</head>
<body>
    <h1>Gestion des utilisateurs</h1>
    <table>
        <thead>
            <tr>
                <th>ID</th><th>Nom d'utilisateur</th><th>Email</th><th>Rôle</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= htmlspecialchars($u['id']) ?></td>
                    <td><?= htmlspecialchars($u['username']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= htmlspecialchars($u['role']) ?></td>
                    <td>
                        <?php if ($u['id'] == 1): ?>
                            <span class="protected">Admin principal protégé</span>
                        <?php else: ?>
                            <a href="?toggle_role=<?= $u['id'] ?>" class="button"><?= $u['role'] === 'admin' ? 'Passer utilisateur' : 'Passer admin' ?></a>
                            <a href="?delete=<?= $u['id'] ?>" class="button delete">Supprimer</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
