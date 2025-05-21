<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

require 'db.php';

// Récupération des utilisateurs
$stmtUsers = $pdo->query("SELECT id, username, email, role FROM users ORDER BY id DESC");
$users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

// Récupération des MSANs avec le nom d'utilisateur lié
$stmtMsans = $pdo->query("
    SELECT msans.*, users.username 
    FROM msans 
    LEFT JOIN users ON msans.user_id = users.id
    ORDER BY msans.id DESC
");
$msans = $stmtMsans->fetchAll(PDO::FETCH_ASSOC);

// Statistiques
$stmtCountUsers = $pdo->query("SELECT COUNT(*) AS total_users FROM users");
$totalUsers = $stmtCountUsers->fetch(PDO::FETCH_ASSOC)['total_users'];

$stmtCountMsans = $pdo->query("SELECT COUNT(*) AS total_msans FROM msans");
$totalMsans = $stmtCountMsans->fetch(PDO::FETCH_ASSOC)['total_msans'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Accueil Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        /* Styles déjà fournis */
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
        }
        .sidebar h2 {
            font-weight: 900;
            font-size: 2.4rem;
            letter-spacing: 2px;
            margin-bottom: 40px;
            color: #ffe5b4;
            text-align: center;
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
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }
        .sidebar nav a:hover {
            background: #ff7f50;
            color: #1e0f0f;
            transform: scale(1.05);
        }
        .sidebar nav a.logout {
            margin-top: auto;
            background: #e63946;
            color: white;
            font-weight: 700;
        }
        .sidebar nav a.logout:hover {
            background: #c62828;
        }

        .main-content {
            margin-left: 280px;
            padding: 40px 50px;
            flex-grow: 1;
            background: linear-gradient(135deg, #3c1a0a, #1e0f0f);
            overflow-y: auto;
        }

        .main-content h1 {
            font-size: 2.8rem;
            font-weight: 900;
            color: #ffb347;
            margin-bottom: 20px;
        }

        .stats-container {
            display: flex;
            gap: 30px;
            margin-bottom: 40px;
            flex-wrap: wrap;
        }

        .stat-box {
            flex: 1;
            min-width: 220px;
            background: #2d1205;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 0 15px #ffa840aa;
        }

        .stat-box h2 {
            font-size: 1.5rem;
            color: #ffb347;
        }

        .stat-box p {
            font-size: 2.4rem;
            font-weight: bold;
            color: #ffffff;
            margin-top: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background: #1a1a1a;
            border-radius: 12px;
            overflow: hidden;
            color: #f5e9e0;
        }

        thead {
            background: rgb(255, 72, 0);
            color: #121212;
        }

        th, td {
            padding: 14px 20px;
            text-align: left;
        }

        tbody tr:hover {
            background: #00fff440;
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
                font-size: 0;
            }
            .main-content {
                margin-left: 60px;
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <h2>ADMIN</h2>
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="profile.php">Paramètres</a>
            <a href="acces_utilisateurs.php">Accès utilisateurs</a>
            <a href="logout.php" class="logout">Déconnexion</a>
        </nav>
    </aside>

    <main class="main-content">
        <h1>Bienvenue, <?= htmlspecialchars($_SESSION['username']) ?> !</h1>

        <div class="stats-container">
            <div class="stat-box">
                <h2>Nombre total d'utilisateurs</h2>
                <p><?= $totalUsers ?></p>
            </div>
            <div class="stat-box">
                <h2>Nombre total de MSANs</h2>
                <p><?= $totalMsans ?></p>
            </div>
        </div>

        <h2>Liste des utilisateurs :</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom d'utilisateur</th>
                    <th>Email</th>
                    <th>Rôle</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['id']) ?></td>
                    <td><?= htmlspecialchars($user['username']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= htmlspecialchars($user['role']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h2>Liste des MSANs :</h2>
        <table>
            <thead>
                <tr>
                    <th>ID MSAN</th>
                    <th>Nom</th>
                    <th>Type</th>
                    <th>Version</th>
                    <th>Nombre de ports</th>
                    <th>Service</th>
                    <th>Fixe PSTN</th>
                    <th>ADSL</th>
                    <th>VDSL</th>
                    <th>Utilisateur lié</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($msans as $msan): ?>
                <tr>
                    <td><?= htmlspecialchars($msan['id']) ?></td>
                    <td><?= htmlspecialchars($msan['nom']) ?></td>
                    <td><?= htmlspecialchars($msan['type']) ?></td>
                    <td><?= htmlspecialchars($msan['version']) ?></td>
                    <td><?= htmlspecialchars($msan['nombre_ports']) ?></td>
                    <td><?= htmlspecialchars($msan['service']) ?></td>
                    <td><?= $msan['fixe_pstn']?></td>
                    <td><?= $msan['adsl']?></td>
                    <td><?= $msan['vdsl']?></td>
                    <td><?= htmlspecialchars($msan['username'] ?? 'Non attribué') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>
</body>
</html>
