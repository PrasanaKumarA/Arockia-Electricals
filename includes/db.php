<?php
// ============================================================
// Arockia Electricals - Database Connection (PDO)
// ============================================================

require_once __DIR__ . '/config.php';

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Check if it's an AJAX request
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
            
            if ($isAjax) {
                die(json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]));
            } else {
                die("<div style='font-family:sans-serif; max-width:600px; margin:50px auto; padding:20px; border:1px solid #ffc107; background-color:#fff3cd; color:#856404; border-radius:8px;'>
                        <h2 style='margin-top:0;'>⚠️ Database Connection Error</h2>
                        <p>The system could not connect to the database. Please check your <code>includes/config.php</code> settings.</p>
                        <hr style='border-color:#ffeeba'>
                        <code>" . htmlspecialchars($e->getMessage()) . "</code>
                     </div>");
            }
        }
    }
    return $pdo;
}
