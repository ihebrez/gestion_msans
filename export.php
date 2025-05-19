<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=export-msan.csv');
echo "\xEF\xBB\xBF"; // BOM UTF-8

$output = fopen('php://output', 'w');

// En-tête colonne
fputcsv($output, ['Nom', 'Type', 'Version', 'Nombre de ports', 'Service', 'Fixe PSTN', 'ADSL', 'VDSL'], ';');

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT nom, type, version, nombre_ports, service, fixe_pstn, adsl, vdsl FROM msans WHERE user_id = ?");
$stmt->execute([$user_id]);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Écrire une ligne CSV avec séparateur ;
    fputcsv($output, $row, ';');
}

fclose($output);
exit;
