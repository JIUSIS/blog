<?php
/**
 * 评论 API
 * GET  comments.php?article_id=xxx → 获取文章评论
 * POST comments.php               → 提交评论
 */

header('Content-Type: application/json; charset=utf-8');

define('COMMENT_FILE', __DIR__ . '/comments.json');

if (!file_exists(COMMENT_FILE)) {
    file_put_contents(COMMENT_FILE, '[]');
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $articleId = $_GET['article_id'] ?? '';
    if (empty($articleId)) {
        echo json_encode(['success' => false, 'error' => '缺少 article_id'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $all = json_decode(file_get_contents(COMMENT_FILE), true) ?? [];
    $comments = array_filter($all, function($c) use ($articleId) {
        return $c['article_id'] === $articleId;
    });
    $comments = array_values($comments);
    // 按时间正序
    usort($comments, function($a, $b) { return $a['timestamp'] - $b['timestamp']; });

    echo json_encode([
        'success' => true,
        'count' => count($comments),
        'comments' => $comments
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;

} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        echo json_encode(['success' => false, 'error' => '无效的请求数据'], 400, JSON_UNESCAPED_UNICODE);
        exit;
    }

    $articleId = trim($input['article_id'] ?? '');
    $nickname  = trim($input['nickname'] ?? '');
    $contact   = trim($input['contact'] ?? '');
    $content   = trim($input['content'] ?? '');

    // 验证
    if (empty($articleId)) {
        echo json_encode(['success' => false, 'error' => '缺少文章ID'], 400, JSON_UNESCAPED_UNICODE);
        exit;
    }
    if (empty($nickname) || mb_strlen($nickname) > 30) {
        echo json_encode(['success' => false, 'error' => '昵称不能为空且不超过30字'], 400, JSON_UNESCAPED_UNICODE);
        exit;
    }
    if (empty($content) || mb_strlen($content) > 1000) {
        echo json_encode(['success' => false, 'error' => '评论内容不能为空且不超过1000字'], 400, JSON_UNESCAPED_UNICODE);
        exit;
    }
    if (!empty($contact) && mb_strlen($contact) > 100) {
        echo json_encode(['success' => false, 'error' => '联系方式过长'], 400, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 简单频率限制：同一 IP 10秒内只能发一条
    $all = json_decode(file_get_contents(COMMENT_FILE), true) ?? [];
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $now = time();
    foreach (array_reverse($all) as $c) {
        if (($c['ip'] ?? '') === $ip && ($now - $c['timestamp']) < 10) {
            echo json_encode(['success' => false, 'error' => '发送太快，请稍后再试'], 429, JSON_UNESCAPED_UNICODE);
            exit;
        }
        break; // 只看最新一条
    }

    $comment = [
        'id' => bin2hex(random_bytes(8)),
        'article_id' => $articleId,
        'nickname' => htmlspecialchars($nickname, ENT_QUOTES, 'UTF-8'),
        'contact' => htmlspecialchars($contact, ENT_QUOTES, 'UTF-8'),
        'content' => htmlspecialchars($content, ENT_QUOTES, 'UTF-8'),
        'date' => date('Y.m.d H:i'),
        'timestamp' => $now,
        'ip' => $ip
    ];

    $all[] = $comment;
    file_put_contents(COMMENT_FILE, json_encode($all, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

    // 不返回 ip 字段
    unset($comment['ip']);
    echo json_encode([
        'success' => true,
        'message' => '评论成功！',
        'comment' => $comment
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;

} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => '不支持的请求方法'], JSON_UNESCAPED_UNICODE);
    exit;
}
