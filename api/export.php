<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

$db = new Database();
$auth = new Auth($db->getConnection());

if (!$auth->isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

$format = $_GET['format'] ?? 'json';

// Obtener entradas del usuario
$stmt = $db->getConnection()->prepare("SELECT * FROM entries WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($format === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="diario-emocional.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Fecha', 'Emoción', 'Texto']);
    
    foreach ($entries as $entry) {
        fputcsv($output, [
            $entry['created_at'],
            $entry['emotion'],
            $entry['text']
        ]);
    }
    
    fclose($output);
} else {
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="diario-emocional.json"');
    echo json_encode($entries, JSON_PRETTY_PRINT);
}
?>