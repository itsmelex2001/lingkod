<?php
/**
 * api/config/session_handler.php
 * PostgreSQL / MySQL database-backed PHP session handler for stateless
 * serverless hosting (Vercel + Supabase).
 */

if (!class_exists('DbSessionHandler')) {
    class DbSessionHandler implements SessionHandlerInterface
    {
        private PDO $pdo;
        private bool $isPgsql;
        private int $ttl;

        public function __construct(PDO $pdo, int $ttl = 86400)
        {
            $this->pdo = $pdo;
            $this->isPgsql = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql';
            $this->ttl = max(300, $ttl);
        }

        public function open(string $path, string $name): bool
        {
            return true;
        }

        public function close(): bool
        {
            return true;
        }

        public function read(string $id): string|false
        {
            try {
                $stmt = $this->pdo->prepare('SELECT data FROM php_sessions WHERE id = ? AND expires_at >= ? LIMIT 1');
                $stmt->execute([$id, time()]);
                $data = $stmt->fetchColumn();
                return $data !== false ? (string) $data : '';
            } catch (Throwable $e) {
                error_log('DbSessionHandler::read error: ' . $e->getMessage());
                return '';
            }
        }

        public function write(string $id, string $data): bool
        {
            try {
                $expires = time() + $this->ttl;
                if ($this->isPgsql) {
                    $stmt = $this->pdo->prepare(
                        'INSERT INTO php_sessions (id, data, expires_at) VALUES (?, ?, ?)
                         ON CONFLICT (id) DO UPDATE SET data = EXCLUDED.data, expires_at = EXCLUDED.expires_at'
                    );
                } else {
                    $stmt = $this->pdo->prepare(
                        'INSERT INTO php_sessions (id, data, expires_at) VALUES (?, ?, ?)
                         ON DUPLICATE KEY UPDATE data = VALUES(data), expires_at = VALUES(expires_at)'
                    );
                }
                return $stmt->execute([$id, $data, $expires]);
            } catch (Throwable $e) {
                error_log('DbSessionHandler::write error: ' . $e->getMessage());
                return false;
            }
        }

        public function destroy(string $id): bool
        {
            try {
                $stmt = $this->pdo->prepare('DELETE FROM php_sessions WHERE id = ?');
                return $stmt->execute([$id]);
            } catch (Throwable $e) {
                error_log('DbSessionHandler::destroy error: ' . $e->getMessage());
                return false;
            }
        }

        public function gc(int $max_lifetime): int|false
        {
            try {
                $stmt = $this->pdo->prepare('DELETE FROM php_sessions WHERE expires_at < ?');
                $stmt->execute([time()]);
                return $stmt->rowCount();
            } catch (Throwable $e) {
                error_log('DbSessionHandler::gc error: ' . $e->getMessage());
                return false;
            }
        }
    }
}

if (!function_exists('initCloudSessionHandler')) {
    function initCloudSessionHandler(PDO $pdo): void
    {
        static $registered = false;
        if ($registered || PHP_SAPI === 'cli') {
            return;
        }
        $useDbSessions = ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql')
            || getenv('VERCEL') === '1'
            || getenv('USE_DB_SESSIONS') === '1';

        if (!$useDbSessions || session_status() !== PHP_SESSION_NONE) {
            return;
        }

        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        $ttl = (int) (getenv('SESSION_LIFETIME') ?: 86400);
        @ini_set('session.gc_maxlifetime', (string) $ttl);
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_set_save_handler(new DbSessionHandler($pdo, $ttl), true);
        $registered = true;
    }
}
