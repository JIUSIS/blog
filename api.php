<?php
/**
 * 文章 API（带认证保护）
 * 
 * GET    /api.php          → 获取文章列表（公开）
 * POST   /api.php          → 发布新文章（需要登录）
 * DELETE /api.php?id=xxx   → 删除文章（需要登录）
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/auth.php';

// 初始化数据文件
if (!file_exists(DATA_FILE)) {
    file_put_contents(DATA_FILE, json_encode([], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

// ─── 处理请求 ───

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // 公开接口：返回所有文章
    $articles = json_decode(file_get_contents(DATA_FILE), true) ?? [];
    jsonResponse([
        'success' => true,
        'count' => count($articles),
        'articles' => $articles
    ]);

} elseif ($method === 'POST') {
    // 需要登录 + CSRF 验证
    requireAuth();

    $input = getJsonInput();

    // 验证 CSRF Token
    $token = $input['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($token) || !verifyCsrfToken($token)) {
        jsonResponse(['success' => false, 'error' => 'CSRF 令牌无效，请刷新页面重试'], 403);
    }

    // 验证必填字段
    if (empty(trim($input['title'] ?? ''))) {
        jsonResponse(['success' => false, 'error' => '标题不能为空'], 400);
    }
    if (mb_strlen(trim($input['title'] ?? '')) > 200) {
        jsonResponse(['success' => false, 'error' => '标题不能超过200字'], 400);
    }
    if (empty(trim($input['content'] ?? ''))) {
        jsonResponse(['success' => false, 'error' => '内容不能为空'], 400);
    }
    if (mb_strlen(trim($input['content'] ?? '')) > 50000) {
        jsonResponse(['success' => false, 'error' => '内容不能超过50000字'], 400);
    }

    // 构建文章（所有字段都做 XSS 过滤）
    $article = [
        'id' => bin2hex(random_bytes(8)),
        'title' => htmlspecialchars(trim($input['title']), ENT_QUOTES, 'UTF-8'),
        'tag' => htmlspecialchars(trim($input['tag'] ?? '随笔'), ENT_QUOTES, 'UTF-8'),
        'content' => trim($input['content']), // 内容保留原始文本，前端渲染时转义
        'date' => date('Y.m.d'),
        'timestamp' => time()
    ];

    // 保存（最新在前）
    $articles = json_decode(file_get_contents(DATA_FILE), true) ?? [];
    array_unshift($articles, $article);
    file_put_contents(DATA_FILE, json_encode($articles, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

    jsonResponse([
        'success' => true,
        'message' => '文章发布成功！',
        'article' => $article
    ]);

} elseif ($method === 'DELETE') {
    // 需要登录 + CSRF 验证
    requireAuth();

    // DELETE 请求的 CSRF 从 header 或 query 获取
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_GET['_csrf'] ?? '';
    if (empty($token) || !verifyCsrfToken($token)) {
        jsonResponse(['success' => false, 'error' => 'CSRF 令牌无效，请刷新页面重试'], 403);
    }

    $deleteId = $_GET['id'] ?? '';
    if (empty($deleteId)) {
        jsonResponse(['success' => false, 'error' => '缺少文章ID'], 400);
    }

    $articles = json_decode(file_get_contents(DATA_FILE), true) ?? [];
    $found = false;
    foreach ($articles as $i => $art) {
        if ($art['id'] === $deleteId) {
            array_splice($articles, $i, 1);
            $found = true;
            break;
        }
    }

    if ($found) {
        file_put_contents(DATA_FILE, json_encode($articles, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        jsonResponse(['success' => true, 'message' => '文章已删除']);
    } else {
        jsonResponse(['success' => false, 'error' => '文章不存在'], 404);
    }

} else {
    jsonResponse(['success' => false, 'error' => '不支持的请求方法'], 405);
}
