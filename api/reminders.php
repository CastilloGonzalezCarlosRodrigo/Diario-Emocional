<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

$db = new Database();
$auth = new Auth($db->getConnection());

if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $time = trim($_POST['time'] ?? '');
    
    if (empty($time)) {
        echo json_encode(['success' => false, 'message' => 'La hora es requerida']);
        exit;
    }
    
    try {
        $stmt = $db->getConnection()->prepare(
            "INSERT INTO reminders (user_id, time) VALUES (?, ?)"
        );
        $stmt->execute([$_SESSION['user_id'], $time]);
        
        $id = $db->getConnection()->lastInsertId();
        echo json_encode(['success' => true, 'id' => $id]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al guardar el recordatorio']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $id = $_GET['id'] ?? 0;
    
    try {
        $stmt = $db->getConnection()->prepare(
            "DELETE FROM reminders WHERE id = ? AND user_id = ?"
        );
        $stmt->execute([$id, $_SESSION['user_id']]);
        
        echo json_encode(['success' => $stmt->rowCount() > 0]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar el recordatorio']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>