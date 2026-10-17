<?php
/**
 * 公共工具：带文件锁的 JSON 读写、客户端 IP、JSON 错误响应
 */

/**
 * 读取 JSON 文件（共享锁）。文件不存在或内容损坏时返回空数组。
 */
function readJsonFile(string $file): array {
    if (!is_file($file)) return [];
    $fp = fopen($file, 'rb');
    if (!$fp) return [];
    flock($fp, LOCK_SH);
    $raw = stream_get_contents($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/**
 * 在独占锁内读取-修改-写回，避免并发写入丢数据或读到半截 JSON。
 */
function mutateJsonFile(string $file, callable $mutate): array {
    $fp = fopen($file, 'c+b');
    if (!$fp) return [];
    flock($fp, LOCK_EX);
    $raw = stream_get_contents($fp);
    $data = json_decode($raw, true);
    if (!is_array($data)) $data = [];
    $data = $mutate($data);
    if (!is_array($data)) $data = [];
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, $json);
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    return $data;
}

/**
 * 覆盖写入 JSON 文件（同样走独占锁）。
 */
function writeJsonFile(string $file, array $data): void {
    mutateJsonFile($file, function () use ($data) {
        return $data;
    });
}

/**
 * 客户端 IP（用于评论频率限制和登录锁定）。
 */
function clientIp(): string {
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/**
 * 输出 JSON 错误并结束请求。
 */
function jsonError(string $message, int $code): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => $message], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
