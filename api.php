<?php
/**
 * 文章 API（带认证保护）
 *
 * GET    /api.php          → 获取文章列表（公开）
 * POST   /api.php          → 发布新文章（需要登录 + CSRF）
 * DELETE /api.php?id=xxx   → 删除文章（需要登录 + CSRF）
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/auth.php';

/**
 * 取字符串字段（类型不对或缺失时返回空串，避免数组等类型触发 TypeError）
 */
function inputString($value): string {
    return is_string($value) ? trim($value) : '';
}

// 初始化数据文件
if (!is_file(DATA_FILE)) {
    writeJsonFile(DATA_FILE, []);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    // 公开接口：返回所有文章
    $articles = readJsonFile(DATA_FILE);
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
    $token = is_string($input['_csrf'] ?? null) ? $input['_csrf'] : ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if ($token === '' || !verifyCsrfToken($token)) {
        jsonResponse(['success' => false, 'error' => 'CSRF 令牌无效，请刷新页面重试'], 403);
    }

    // 验证必填字段
    $title = inputString($input['title'] ?? null);
    $tag = inputString($input['tag'] ?? null);
    $content = inputString($input['content'] ?? null);

    if ($title === '') {
        jsonResponse(['success' => false, 'error' => '标题不能为空'], 400);
    }
    if (mb_strlen($title) > 200) {
        jsonResponse(['success' => false, 'error' => '标题不能超过200字'], 400);
    }
    if ($content === '') {
        jsonResponse(['success' => false, 'error' => '内容不能为空'], 400);
    }
    if (mb_strlen($content) > 50000) {
        jsonResponse(['success' => false, 'error' => '内容不能超过50000字'], 400);
    }
    if ($tag === '') $tag = '随笔';
    if (mb_strlen($tag) > 30) {
        jsonResponse(['success' => false, 'error' => '标签不能超过30字'], 400);
    }

    // 构建文章（存原文，前端渲染时统一转义，避免重复转义）
    $article = [
        'id' => bin2hex(random_bytes(8)),
        'title' => $title,
        'tag' => $tag,
        'content' => $content,
        'date' => date('Y.m.d'),
        'timestamp' => time()
    ];

    // 保存（最新在前，独占锁内完成读-改-写）
    mutateJsonFile(DATA_FILE, function (array $articles) use ($article) {
        array_unshift($articles, $article);
        return $articles;
    });

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
    if (!is_string($token) || $token === '' || !verifyCsrfToken($token)) {
        jsonResponse(['success' => false, 'error' => 'CSRF 令牌无效，请刷新页面重试'], 403);
    }

    $deleteId = inputString($_GET['id'] ?? null);
    if ($deleteId === '') {
        jsonResponse(['success' => false, 'error' => '缺少文章ID'], 400);
    }

    $found = false;
    mutateJsonFile(DATA_FILE, function (array $articles) use ($deleteId, &$found) {
        $before = count($articles);
        $articles = array_values(array_filter($articles, function ($art) use ($deleteId) {
            return ($art['id'] ?? '') !== $deleteId;
        }));
        $found = count($articles) !== $before;
        return $articles;
    });

    if ($found) {
        jsonResponse(['success' => true, 'message' => '文章已删除']);
    } else {
        jsonResponse(['success' => false, 'error' => '文章不存在'], 404);
    }

} else {
    jsonResponse(['success' => false, 'error' => '不支持的请求方法'], 405);
}
