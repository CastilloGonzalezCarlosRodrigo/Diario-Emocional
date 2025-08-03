<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/emotions.php';

header('Content-Type: application/json');

$db = new Database();
$auth = new Auth($db->getConnection());

if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $text = trim($_POST['text'] ?? '');
    
    if (empty($text)) {
        echo json_encode(['success' => false, 'message' => 'El texto no puede estar vacío']);
        exit;
    }
    
    [$emotion, $confidence] = EmotionDetector::detect($text);
    
    try {
        $stmt = $db->getConnection()->prepare(
            "INSERT INTO entries (user_id, text, emotion) VALUES (?, ?, ?)"
        );
        $stmt->execute([$_SESSION['user_id'], $text, $emotion]);
        
        echo json_encode(['success' => true, 'emotion' => $emotion]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al guardar la entrada']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>