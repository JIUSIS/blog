<?php
/**
 * 认证模块
 *
 * 功能：
 *   - session 管理
 *   - 登录/登出/检查登录状态
 *   - 密码验证（password_hash / 明文二选一）
 *   - 登录失败锁定（按 IP）
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib.php';

// 安全的 session 启动（HTTPS 环境下自动启用 secure cookie）
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || ((($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https');
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
        'cookie_secure' => $isHttps,
        'use_strict_mode' => true
    ]);
}

/**
 * 检查是否已登录
 */
function isLoggedIn(): bool {
    return !empty($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * 是否已配置管理员密码
 */
function isPasswordConfigured(): bool {
    return ADMIN_PASSWORD_HASH !== '' || ADMIN_PASSWORD !== '';
}

/**
 * 登录锁定剩余秒数（0 表示未锁定）
 */
function loginLockRemaining(): int {
    $entry = readJsonFile(ATTEMPTS_FILE)[clientIp()] ?? null;
    if (!is_array($entry)) return 0;
    if ((int)($entry['count'] ?? 0) < LOGIN_MAX_ATTEMPTS) return 0;
    $remaining = LOGIN_LOCK_SECONDS - (time() - (int)($entry['last'] ?? 0));
    return $remaining > 0 ? $remaining : 0;
}

/**
 * 记录一次登录失败
 */
function recordLoginFailure(): void {
    $ip = clientIp();
    mutateJsonFile(ATTEMPTS_FILE, function (array $attempts) use ($ip) {
        $now = time();
        // 清理 24 小时前的旧记录，避免文件无限增长
        foreach ($attempts as $key => $item) {
            if (($now - (int)($item['last'] ?? 0)) > 86400) unset($attempts[$key]);
        }
        $entry = $attempts[$ip] ?? ['count' => 0, 'last' => 0];
        // 距上次失败超过锁定窗口，则重新计数
        $count = ((int)($entry['last'] ?? 0) < $now - LOGIN_LOCK_SECONDS) ? 0 : (int)$entry['count'];
        $attempts[$ip] = ['count' => $count + 1, 'last' => $now];
        return $attempts;
    });
}

/**
 * 清空当前 IP 的失败记录
 */
function clearLoginFailures(): void {
    $ip = clientIp();
    mutateJsonFile(ATTEMPTS_FILE, function (array $attempts) use ($ip) {
        unset($attempts[$ip]);
        return $attempts;
    });
}

/**
 * 执行登录
 */
function doLogin(string $password): bool {
    if (loginLockRemaining() > 0) return false;

    $ok = false;
    if (ADMIN_PASSWORD_HASH !== '') {
        $ok = password_verify($password, ADMIN_PASSWORD_HASH);
    } elseif (ADMIN_PASSWORD !== '') {
        $ok = hash_equals(ADMIN_PASSWORD, $password);
    }

    if ($ok) {
        clearLoginFailures();
        session_regenerate_id(true); // 防止 session 固定攻击
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['login_time'] = time();
        return true;
    }

    recordLoginFailure();
    return false;
}

/**
 * 执行登出
 */
function doLogout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}

/**
 * 要求登录（未登录则返回 JSON 错误）
 */
function requireAuth(): void {
    if (!isLoggedIn()) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error' => '未登录，请先登录',
            'need_login' => true
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

/**
 * 要求登录（未登录则重定向到登录页）
 */
function requireAuthRedirect(): void {
    if (!isLoggedIn()) {
        header('Location: write.php?action=login');
        exit;
    }
}

/**
 * 输出 JSON 响应
 */
function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * 获取请求体 JSON
 */
function getJsonInput(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/**
 * HTML 转义（防 XSS）
 */
function esc(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * 生成 CSRF Token
 */
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * 验证 CSRF Token
 */
function verifyCsrfToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
