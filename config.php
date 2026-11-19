<?php
// config.php - Configuration générale et gestion de données
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('BASE_DIR', __DIR__);
define('DATA_DIR', BASE_DIR . '/data');
define('DB_FILE', DATA_DIR . '/db.json');
define('BACKUP_FILE', DATA_DIR . '/db_backup.json');
define('SECRET_FILE', DATA_DIR . '/secret.txt');

require_once __DIR__ . '/includes/security.php';

/**
 * Charge les données depuis le fichier JSON
 */
function load_db() {
    if (!file_exists(DB_FILE)) {
        if (file_exists(BACKUP_FILE)) {
            copy(BACKUP_FILE, DB_FILE);
        } else {
            return ['vulnerable_mode' => true, 'users' => [], 'grades' => [], 'logs' => []];
        }
    }
    $content = file_get_contents(DB_FILE);
    $data = json_decode($content, true);
    return is_array($data) ? $data : ['vulnerable_mode' => true, 'users' => [], 'grades' => [], 'logs' => []];
}

/**
 * Enregistre les données dans le fichier JSON
 */
function save_db($data) {
    file_put_contents(DB_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

/**
 * Réinitialise la base à l'état propre d'origine (1 clic)
 */
function reset_db() {
    if (file_exists(BACKUP_FILE)) {
        copy(BACKUP_FILE, DB_FILE);
        $_SESSION['flash_message'] = "✅ Les données de démo ont été réinitialisées avec succès (Mamadou Sow: 08/20) !";
        $_SESSION['flash_type'] = "success";
        return true;
    }
    return false;
}

/**
 * Vérifie si le mode vulnérable est activé
 */
function is_vulnerable_mode() {
    $db = load_db();
    return !empty($db['vulnerable_mode']);
}

/**
 * Change le mode de sécurité (Vulnérable <-> Sécurisé)
 */
function set_vulnerable_mode($vulnerable) {
    $db = load_db();
    $db['vulnerable_mode'] = (bool)$vulnerable;
    save_db($db);
    log_security_event(
        $vulnerable ? 'MODE_SWITCH_VULNERABLE' : 'MODE_SWITCH_SECURE',
        'SUCCESS',
        $vulnerable ? 'Passage en Mode Vulnérable (signatures désactivées, DTD activées)' : 'Passage en Mode Sécurisé (SHA-256 et X.509 obligatoires, DTD bloquées)'
    );
}

/**
 * Enregistre un événement dans le journal d'audit
 */
function log_security_event($event, $status, $details) {
    $db = load_db();
    if (!isset($db['logs']) || !is_array($db['logs'])) {
        $db['logs'] = [];
    }
    $db['logs'][] = [
        'id' => uniqid(),
        'timestamp' => date('Y-m-d H:i:s'),
        'event' => $event,
        'status' => $status,
        'details' => $details,
        'mode' => is_vulnerable_mode() ? 'VULNERABLE' : 'SECURE',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
    ];
    // Conserver les 50 derniers logs
    if (count($db['logs']) > 50) {
        $db['logs'] = array_slice($db['logs'], -50);
    }
    save_db($db);
}

/**
 * Met à jour une note d'un étudiant dans la base
 */
function update_student_grade($studentId, $newGrade, $course = 'Sécurité Informatique') {
    $db = load_db();
    $found = false;
    foreach ($db['grades'] as &$g) {
        if ($g['student_id'] === $studentId) {
            $oldGrade = $g['grade'];
            $g['grade'] = $newGrade;
            $found = true;
            log_security_event(
                'GRADE_UPDATE',
                'SUCCESS',
                "Note de {$g['name']} ($studentId) modifiée : {$oldGrade}/20 -> {$newGrade}/20"
            );
            break;
        }
    }
    if ($found) {
        save_db($db);
    }
    return $found;
}

/**
 * Récupère l'utilisateur actuellement connecté
 */
function get_current_user_session() {
    return $_SESSION['user'] ?? null;
}
