<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$edit_mode = false;
$edit_msan = null;

// Répertoire où stocker les images uploadées
$upload_dir = __DIR__ . '/uploads/';

// Crée le dossier s'il n'existe pas
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Suppression d'un MSAN
if (isset($_GET['delete'])) {
    $delete_id = (int) $_GET['delete'];
    
    // Supprimer aussi l'image associée si existe
    $stmt = $pdo->prepare("SELECT image FROM msans WHERE id = ? AND user_id = ?");
    $stmt->execute([$delete_id, $user_id]);
    $msan_to_delete = $stmt->fetch();
    if ($msan_to_delete && !empty($msan_to_delete['image'])) {
        $img_path = $upload_dir . $msan_to_delete['image'];
        if (file_exists($img_path)) {
            unlink($img_path);
        }
    }
    
    $stmt = $pdo->prepare("DELETE FROM msans WHERE id = ? AND user_id = ?");
    $stmt->execute([$delete_id, $user_id]);
    header('Location: dashboard.php');
    exit;
}

// Charger le MSAN à modifier
if (isset($_GET['edit'])) {
    $edit_id = (int) $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM msans WHERE id = ? AND user_id = ?");
    $stmt->execute([$edit_id, $user_id]);
    $edit_msan = $stmt->fetch();
    if ($edit_msan) {
        $edit_mode = true;
    } else {
        header('Location: dashboard.php');
        exit;
    }
}

// Traitement formulaire ajout / modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $version = trim($_POST['version'] ?? '');
    $nombre_ports = (int) ($_POST['nombre_ports'] ?? 0);
    $service = trim($_POST['service'] ?? '');
    $fixe_pstn = (int) ($_POST['fixe_pstn'] ?? 0);
    $adsl = (int) ($_POST['adsl'] ?? 0);
    $vdsl = (int) ($_POST['vdsl'] ?? 0);

    if ($nom === '') {
        $error = "Le nom du MSAN est obligatoire.";
    } else {
        // Gestion upload image
        $image_name = null;

        if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['image'];

            // Vérifications simples
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($file['type'], $allowed_types)) {
                $error = "Type d'image non autorisé. Utilisez jpg, png ou gif.";
            } elseif ($file['size'] > 2 * 1024 * 1024) { // 2Mo max
                $error = "Image trop lourde (max 2Mo).";
            } else {
                // Générer un nom unique
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $image_name = uniqid('msan_', true) . '.' . $ext;
                $destination = $upload_dir . $image_name;

                if (!move_uploaded_file($file['tmp_name'], $destination)) {
                    $error = "Erreur lors de l'upload de l'image.";
                }
            }
        }

        if (!$error) {
            try {
                if (isset($_POST['id']) && is_numeric($_POST['id'])) {
                    // Modification
                    $id = (int) $_POST['id'];

                    // Si pas d'image uploadée, on conserve l'ancienne
                    if (!$image_name && $edit_msan) {
                        $image_name = $edit_msan['image'];
                    } else {
                        // Si nouvelle image, supprimer l'ancienne
                        if ($edit_msan && !empty($edit_msan['image'])) {
                            $old_img = $upload_dir . $edit_msan['image'];
                            if (file_exists($old_img)) unlink($old_img);
                        }
                    }

                    $stmt = $pdo->prepare("UPDATE msans SET nom = ?, type = ?, version = ?, nombre_ports = ?, service = ?, fixe_pstn = ?, adsl = ?, vdsl = ?, image = ? WHERE id = ? AND user_id = ?");
                    $stmt->execute([$nom, $type, $version, $nombre_ports, $service, $fixe_pstn, $adsl, $vdsl, $image_name, $id, $user_id]);
                } else {
                    // Ajout
                    $stmt = $pdo->prepare("INSERT INTO msans (nom, type, version, nombre_ports, service, fixe_pstn, adsl, vdsl, image, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$nom, $type, $version, $nombre_ports, $service, $fixe_pstn, $adsl, $vdsl, $image_name, $user_id]);
                }
                header('Location: dashboard.php');
                exit;
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $error = "Ce nom de MSAN existe déjà. Merci d'en choisir un autre.";
                } else {
                    $error = "Erreur lors de l'enregistrement : " . $e->getMessage();
                }
            }
        }
    }
}

// Récupérer tous les MSANs de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM msans WHERE user_id = ?");
$stmt->execute([$user_id]);
$msans = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Dashboard MSANs</title>
    <link rel="stylesheet" href="styles.css" />
</head>
<body>
    <header>
        <h1>Dashboard MSANs</h1>
        <a href="logout.php" class="logout">Déconnexion</a>
        <a href="export.php" class="btn-export">Exporter</a>
    </header>

    <main>
        <section class="form-section">
            <h2><?= $edit_mode ? "Modifier un MSAN" : "Ajouter un MSAN" ?></h2>
            <?php if ($error): ?>
                <p class="error-msg"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= $edit_mode ? $edit_msan['id'] : '' ?>" />

                <input type="text" name="nom" placeholder="Nom du MSAN" required value="<?= $edit_mode ? htmlspecialchars($edit_msan['nom']) : '' ?>" />
                <input type="text" name="type" placeholder="Type" value="<?= $edit_mode ? htmlspecialchars($edit_msan['type']) : '' ?>" />
                <input type="text" name="version" placeholder="Version" value="<?= $edit_mode ? htmlspecialchars($edit_msan['version']) : '' ?>" />
                <input type="number" name="nombre_ports" placeholder="Nombre de ports" value="<?= $edit_mode ? (int)$edit_msan['nombre_ports'] : '' ?>" />
                <input type="text" name="service" placeholder="Service" value="<?= $edit_mode ? htmlspecialchars($edit_msan['service']) : '' ?>" />
                <input type="number" name="fixe_pstn" placeholder="Fixe PSTN" value="<?= $edit_mode ? (int)$edit_msan['fixe_pstn'] : '' ?>" />
                <input type="number" name="adsl" placeholder="ADSL" value="<?= $edit_mode ? (int)$edit_msan['adsl'] : '' ?>" />
                <input type="number" name="vdsl" placeholder="VDSL" value="<?= $edit_mode ? (int)$edit_msan['vdsl'] : '' ?>" />

                <!-- Upload image -->
                <label for="image"></label>
                <input type="file" name="image" id="image" accept="image/*" />
                <?php if ($edit_mode && !empty($edit_msan['image'])): ?>
                    <div>
                        <small>Image actuelle :</small><br/>
                        <img src="uploads/<?= htmlspecialchars($edit_msan['image']) ?>" alt="Image MSAN" style="max-width:150px; max-height:100px;" />
                    </div>
                <?php endif; ?>

                <button type="submit"><?= $edit_mode ? "Modifier" : "Ajouter" ?></button>
                <?php if ($edit_mode): ?>
                    <a href="dashboard.php" class="cancel-link">Annuler</a>
                <?php endif; ?>
            </form>
        </section>

        <section class="table-section">
            <h2>Liste de vos MSANs</h2>
            <?php if (count($msans) === 0): ?>
                <p>Aucun MSAN pour le moment.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Nom</th>
                            <th>Type</th>
                            <th>Version</th>
                            <th>Ports</th>
                            <th>Service</th>
                            <th>Fixe PSTN</th>
                            <th>ADSL</th>
                            <th>VDSL</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($msans as $m): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($m['image']) && file_exists($upload_dir . $m['image'])): ?>
                                        <img src="uploads/<?= htmlspecialchars($m['image']) ?>" alt="Image MSAN" style="max-width:80px; max-height:60px;" />
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($m['nom']) ?></td>
                                <td><?= htmlspecialchars($m['type']) ?></td>
                                <td><?= htmlspecialchars($m['version']) ?></td>
                                <td><?= (int)$m['nombre_ports'] ?></td>
                                <td><?= htmlspecialchars($m['service']) ?></td>
                                <td><?= (int)$m['fixe_pstn'] ?></td>
                                <td><?= (int)$m['adsl'] ?></td>
                                <td><?= (int)$m['vdsl'] ?></td>
                                <td class="action-links">
                                    <a href="dashboard.php?edit=<?= $m['id'] ?>">Modifier</a> | 
                                    <a href="dashboard.php?delete=<?= $m['id'] ?>" onclick="return confirm('Supprimer ce MSAN ?');">Supprimer</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
