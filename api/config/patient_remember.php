<?php
/**
 * api/config/patient_remember.php
 * Persistent "Remember me" token helpers for the Patient Portal.
 */

const LINGKOD_REMEMBER_COOKIE = 'lingkod_patient_remember';
const LINGKOD_REMEMBER_EMAIL_COOKIE = 'lingkod_remember_email';
const LINGKOD_REMEMBER_TTL = 2592000; // 30 days

function ensurePatientRememberTable(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS patient_remember_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_db_id INT NOT NULL,
            selector VARCHAR(32) NOT NULL,
            token_hash VARCHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_selector (selector),
            INDEX idx_patient_db_id (patient_db_id),
            INDEX idx_expires_at (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function setPatientRememberCookie(string $name, string $value, int $expires, bool $httpOnly): void {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    setcookie($name, $value, [
        'expires'  => $expires,
        'path'     => '/sdn/',
        'secure'   => $isHttps,
        'httponly' => $httpOnly,
        'samesite' => 'Lax',
    ]);
}

function setPatientLoginSession(array $patient, ?int $userId = null): void {
    $_SESSION['patient_logged_in'] = true;
    $_SESSION['patient_id']        = $patient['patient_id'];
    $_SESSION['patient_db_id']     = $patient['id'];
    $_SESSION['patient_name']      = $patient['full_name'];
    $_SESSION['patient_email']     = $patient['email'];
    if ($userId !== null) {
        $_SESSION['user_id']         = $userId;
        $_SESSION['patient_user_id'] = $userId;
    }
    $_SESSION['user_role']         = 'patient';
    $_SESSION['role']              = 'patient';
}

function issuePatientRememberCookies(PDO $pdo, array $patient): void {
    ensurePatientRememberTable($pdo);
    $selector  = bin2hex(random_bytes(12));
    $validator = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $validator);
    $expiresAt = date('Y-m-d H:i:s', time() + LINGKOD_REMEMBER_TTL);

    $stmt = $pdo->prepare("
        INSERT INTO patient_remember_tokens (patient_db_id, selector, token_hash, expires_at)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([(int)$patient['id'], $selector, $tokenHash, $expiresAt]);

    $exp = time() + LINGKOD_REMEMBER_TTL;
    setPatientRememberCookie(LINGKOD_REMEMBER_COOKIE, $selector . ':' . $validator, $exp, true);
    setPatientRememberCookie(LINGKOD_REMEMBER_EMAIL_COOKIE, (string)($patient['email'] ?? ''), $exp, false);
}

function clearPatientRememberCookies(?PDO $pdo = null, bool $clearEmail = false): void {
    $cookieVal = $_COOKIE[LINGKOD_REMEMBER_COOKIE] ?? '';
    if ($pdo && is_string($cookieVal) && str_contains($cookieVal, ':')) {
        [$selector] = explode(':', $cookieVal, 2);
        if ($selector !== '') {
            try {
                ensurePatientRememberTable($pdo);
                $pdo->prepare("DELETE FROM patient_remember_tokens WHERE selector = ?")->execute([$selector]);
            } catch (Throwable $e) {}
        }
    }
    setPatientRememberCookie(LINGKOD_REMEMBER_COOKIE, '', time() - 3600, true);
    unset($_COOKIE[LINGKOD_REMEMBER_COOKIE]);

    if ($clearEmail) {
        setPatientRememberCookie(LINGKOD_REMEMBER_EMAIL_COOKIE, '', time() - 3600, false);
        unset($_COOKIE[LINGKOD_REMEMBER_EMAIL_COOKIE]);
    }
}

function tryAutoLoginPatientFromCookie(PDO $pdo): bool {
    if (!empty($_SESSION['patient_logged_in']) && (!empty($_SESSION['patient_id']) || !empty($_SESSION['patient_email']))) {
        return true;
    }
    $cookieVal = $_COOKIE[LINGKOD_REMEMBER_COOKIE] ?? '';
    if (!is_string($cookieVal) || !str_contains($cookieVal, ':')) {
        return false;
    }
    [$selector, $validator] = explode(':', $cookieVal, 2);
    if ($selector === '' || $validator === '') {
        clearPatientRememberCookies(null, false);
        return false;
    }

    try {
        ensurePatientRememberTable($pdo);
        $stmt = $pdo->prepare("
            SELECT t.id AS token_id, t.token_hash, t.expires_at,
                   p.id, p.patient_id, p.full_name, p.email, p.user_id, p.is_verified, p.status
            FROM patient_remember_tokens t
            INNER JOIN patients p ON p.id = t.patient_db_id
            WHERE t.selector = ?
            LIMIT 1
        ");
        $stmt->execute([$selector]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || strtotime($row['expires_at']) <= time()) {
            clearPatientRememberCookies($pdo, false);
            return false;
        }
        if (!hash_equals($row['token_hash'], hash('sha256', $validator))) {
            clearPatientRememberCookies($pdo, false);
            return false;
        }
        if (isset($row['is_verified']) && (int)$row['is_verified'] !== 1) {
            clearPatientRememberCookies($pdo, false);
            return false;
        }

        // Rotate validator on each auto-login
        $newValidator = bin2hex(random_bytes(32));
        $newHash = hash('sha256', $newValidator);
        $newExpiresAt = date('Y-m-d H:i:s', time() + LINGKOD_REMEMBER_TTL);
        $pdo->prepare("UPDATE patient_remember_tokens SET token_hash = ?, expires_at = ? WHERE id = ?")
            ->execute([$newHash, $newExpiresAt, (int)$row['token_id']]);

        $exp = time() + LINGKOD_REMEMBER_TTL;
        setPatientRememberCookie(LINGKOD_REMEMBER_COOKIE, $selector . ':' . $newValidator, $exp, true);
        setPatientRememberCookie(LINGKOD_REMEMBER_EMAIL_COOKIE, (string)($row['email'] ?? ''), $exp, false);

        $userId = !empty($row['user_id']) ? (int)$row['user_id'] : null;
        if ($userId) {
            try {
                $hasCol = (bool)$pdo->query("SHOW COLUMNS FROM users LIKE 'account_patient_id'")->fetch();
                if ($hasCol) {
                    $apStmt = $pdo->prepare("SELECT account_patient_id FROM users WHERE id = ? LIMIT 1");
                    $apStmt->execute([$userId]);
                    $acctPid = trim((string)($apStmt->fetchColumn() ?: ''));
                    if ($acctPid !== '') {
                        $row['patient_id'] = $acctPid;
                    }
                }
            } catch (Throwable $ignoreAcct) {}
        }
        setPatientLoginSession($row, $userId);
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

