<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';
require_once 'includes/emotions.php';

$db = new Database();
$auth = new Auth($db->getConnection());

if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Obtener entradas del usuario
$stmt = $db->getConnection()->prepare("SELECT * FROM entries WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener recordatorios
$reminders = [];
$stmt = $db->getConnection()->prepare("SELECT * FROM reminders WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$reminders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>
    <div class="app-container">

        <!-- Encabezado -->
        <header>
            <div class="header-content">
                <h1><i class="fas fa-book-open"></i> Mi Diario Emocional</h1>
                <div class="header-actions">
                    <button id="themeToggle"><i class="fas fa-moon"></i></button>
                </div>
            </div>
        </header>

        <!-- Contenido principal -->
        <main>

            <!-- SECCIÓN INICIO -->
            <section id="section-home" class="app-section active">
                <div class="user-section">
                    <div class="avatar" id="userAvatar">😐</div>
                    <div class="user-info">
                        <h2>Hola, <?= htmlspecialchars($_SESSION['username']) ?></h2>
                        <p><?= date('l, j F Y') ?></p>
                    </div>
                </div>
                <div class="entries-section card">
                    <div class="section-header">
                        <h2><i class="fas fa-history"></i> Últimas Entradas</h2>
                    </div>
                    <div class="entries-list">
                        <?php foreach (array_slice($entries, 0, 3) as $entry): ?>
                            <div class="entry" style="border-left-color: <?= EmotionDetector::getEmotionColor($entry['emotion']) ?>">
                                <div class="entry-header">
                                    <span><?= EmotionDetector::getEmotionIcon($entry['emotion']) ?></span>
                                    <span class="entry-date"><?= date('d M Y H:i', strtotime($entry['created_at'])) ?></span>
                                </div>
                                <div class="entry-text"><?= nl2br(htmlspecialchars($entry['text'])) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <!-- SECCIÓN NUEVA ENTRADA -->
            <section id="section-entry" class="app-section">
                <div class="new-entry card">
                    <h2><i class="fas fa-edit"></i> ¿Cómo te sientes hoy?</h2>
                    <form id="entryForm" action="api/entries.php" method="POST">
                        <textarea name="text" id="entryText" placeholder="Escribe cómo te sientes hoy..."></textarea>
                        <div id="emotionPreview" class="emotion-preview">
                            <div id="emotionDisplay"></div>
                            <div id="confidenceBar"></div>
                        </div>
                        <div class="hashtag-hint">Puedes usar #hashtags para categorizar</div>
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> Guardar Entrada
                        </button>
                    </form>
                </div>
            </section>

            <!-- SECCIÓN CALENDARIO -->
            <section id="section-calendar" class="app-section">
                <div class="calendar-section card">
                    <h2><i class="fas fa-calendar-alt"></i> Calendario</h2>
                    <div class="calendar-controls">
                        <button id="prevMonth"><i class="fas fa-chevron-left"></i></button>
                        <h3 id="currentMonth"></h3>
                        <button id="nextMonth"><i class="fas fa-chevron-right"></i></button>
                    </div>
                    <div class="calendar-grid" id="calendarDays"></div>
                </div>
            </section>

            <!-- SECCIÓN RECORDATORIOS -->
            <section id="section-reminders" class="app-section">
                <div class="reminders-section card">
                    <h2><i class="fas fa-bell"></i> Recordatorios</h2>
                    <form id="reminderForm" action="api/reminders.php" method="POST">
                        <div class="time-picker">
                            <label for="reminderTime">Hora del recordatorio:</label>
                            <input type="time" id="reminderTime" name="time" required>
                        </div>
                        <button type="submit" class="btn-secondary">
                            <i class="fas fa-plus"></i> Agregar Recordatorio
                        </button>
                    </form>
                    <div class="reminders-list" id="remindersList">
                        <?php foreach ($reminders as $reminder): ?>
                            <div class="reminder">
                                <span><i class="fas fa-clock"></i> <?= htmlspecialchars($reminder['time']) ?></span>
                                <button class="delete-reminder" data-id="<?= $reminder['id'] ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <!-- SECCIÓN AJUSTES -->
            <section id="section-settings" class="app-section">
                <div class="export-section card">
                    <h2><i class="fas fa-file-export"></i> Exportar Datos</h2>
                    <div class="export-options">
                        <a href="api/export.php?format=json" class="btn-secondary">
                            <i class="fas fa-file-code"></i> Exportar a JSON
                        </a>
                        <a href="api/export.php?format=csv" class="btn-secondary">
                            <i class="fas fa-file-csv"></i> Exportar a CSV
                        </a>
                    </div>
                </div>
                <div class="logout-section card">
                    <h2><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</h2>
                    <a href="login.php" class="btn-logout">
                        <i class="fas fa-power-off"></i> Salir de mi cuenta
                    </a>
                </div>
            </section>

        </main>

        <!-- Barra de navegación inferior -->
        <nav class="bottom-nav">
            <button data-target="home" class="active"><i class="fas fa-home"></i><span></span></button>
            <button data-target="entry"><i class="fas fa-edit"></i><span></span></button>
            <button data-target="calendar"><i class="fas fa-calendar-alt"></i><span></span></button>
            <button data-target="reminders"><i class="fas fa-bell"></i><span></span></button>
            <button data-target="settings"><i class="fas fa-cog"></i><span></span></button>
        </nav>

    </div>
    <script src="js/script.js"></script>
</body>

</html>