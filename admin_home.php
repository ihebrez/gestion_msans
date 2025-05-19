<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Connexion à la base de données
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
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Accueil Admin - Ultra Moderne</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style>
        /* Reset & font */
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

        /* Sidebar */
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

        /* Main content */
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

        .main-content h2 {
            font-size: 2rem;
            margin-top: 40px;
            margin-bottom: 12px;
            color: #ffa840;
            text-shadow: 0 0 8px #ffa840aa;
        }

        .main-content p {
            font-size: 1.15rem;
            line-height: 1.6;
            color: #ffddb3;
            max-width: 650px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            box-shadow: 0 0 10px rgba(0,255,234,0.2);
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
            cursor: default;
        }

        ::-webkit-scrollbar {
            width: 12px;
        }

        ::-webkit-scrollbar-track {
            background: #4e1a00;
            border-radius: 12px;
        }

        ::-webkit-scrollbar-thumb {
            background: #ff7f50;
            border-radius: 12px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #ff914d;
        }

        @keyframes fadeInContent {
            from {opacity: 0; transform: translateY(10px);}
            to {opacity: 1; transform: translateY(0);}
        }

        /* Responsive */
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
            <a href="dashboard.php"><span class="icon" aria-hidden="true"></span>Dashboard</a>
            <a href="profile.php"><span class="icon" aria-hidden="true"></span>Paramètres</a>
            <a href="acces_utilisateurs.php"><span class="icon" aria-hidden="true"></span>Accès utilisateurs</a>
            <a href="logout.php" class="logout"><span class="icon" aria-hidden="true"></span>Déconnexion</a>
        </nav>
    </aside>

    <main class="main-content" role="main" tabindex="-1">
        <h1>Bienvenue, <?= htmlspecialchars($_SESSION['username']) ?> !</h1>

        <p>Liste des utilisateurs enregistrés :</p>
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
