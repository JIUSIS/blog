<?php
/**
 * 认证模块
 * 
 * 功能：
 *   - session 管理
 *   - 登录/登出/检查登录状态
 *   - 密码验证
 */

require_once __DIR__ . '/config.php';

// 安全的 session 启动
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
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
 * 执行登录
 */
function doLogin(string $password): bool {
    if ($password === ADMIN_PASSWORD) {
        session_regenerate_id(true); // 防止 session 固定攻击
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['login_time'] = time();
        return true;
    }
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
