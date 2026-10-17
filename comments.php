<?php
/**
 * 评论 API
 * GET  comments.php?article_id=xxx → 获取文章评论（不返回 IP）
 * POST comments.php               → 提交评论
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$method = $_SERVER['REQUEST_METHOD'];

/**
 * 取字符串字段（类型不对或缺失时返回空串）
 */
function inputString($value): string {
    return is_string($value) ? trim($value) : '';
}

if ($method === 'GET') {
    $articleId = inputString($_GET['article_id'] ?? null);
    if ($articleId === '') {
        jsonError('缺少 article_id', 400);
    }

    $comments = array_values(array_filter(readJsonFile(COMMENT_FILE), function ($c) use ($articleId) {
        return ($c['article_id'] ?? '') === $articleId;
    }));
    // 按时间正序
    usort($comments, function ($a, $b) {
        return (int)($a['timestamp'] ?? 0) - (int)($b['timestamp'] ?? 0);
    });
    // 服务端存了 IP 用于限速，但不对外返回
    $comments = array_map(function ($c) {
        unset($c['ip']);
        return $c;
    }, $comments);

    echo json_encode([
        'success' => true,
        'count' => count($comments),
        'comments' => $comments
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;

} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        jsonError('无效的请求数据', 400);
    }

    $articleId = inputString($input['article_id'] ?? null);
    $nickname  = inputString($input['nickname'] ?? null);
    $contact   = inputString($input['contact'] ?? null);
    $content   = inputString($input['content'] ?? null);

    // 验证
    if ($articleId === '') {
        jsonError('缺少文章ID', 400);
    }
    if ($nickname === '' || mb_strlen($nickname) > 30) {
        jsonError('昵称不能为空且不超过30字', 400);
    }
    if ($content === '' || mb_strlen($content) > 1000) {
        jsonError('评论内容不能为空且不超过1000字', 400);
    }
    if ($contact !== '' && mb_strlen($contact) > 100) {
        jsonError('联系方式过长', 400);
    }

    // 文章必须存在，避免产生无主评论
    $articleIds = array_column(readJsonFile(DATA_FILE), 'id');
    if (!in_array($articleId, $articleIds, true)) {
        jsonError('文章不存在', 404);
    }

    // 频率限制：同一 IP 10 秒内只能发一条（按该 IP 自己最近一条计算）
    $ip = clientIp();
    $now = time();
    foreach (array_reverse(readJsonFile(COMMENT_FILE)) as $c) {
        if (($c['ip'] ?? '') !== $ip) continue;
        if ($now - (int)($c['timestamp'] ?? 0) < 10) {
            jsonError('发送太快，请稍后再试', 429);
        }
        break;
    }

    // 存原文，输出时再转义
    $comment = [
        'id' => bin2hex(random_bytes(8)),
        'article_id' => $articleId,
        'nickname' => $nickname,
        'contact' => $contact,
        'content' => $content,
        'date' => date('Y.m.d H:i'),
        'timestamp' => $now,
        'ip' => $ip
    ];

    $stored = mutateJsonFile(COMMENT_FILE, function (array $comments) use ($comment) {
        $comments[] = $comment;
        return $comments;
    });
    if (count($stored) === 0) {
        jsonError('评论保存失败，请稍后再试', 500);
    }

    // 不返回 ip 字段
    unset($comment['ip']);
    echo json_encode([
        'success' => true,
        'message' => '评论成功！',
        'comment' => $comment
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;

} else {
    jsonError('不支持的请求方法', 405);
}
