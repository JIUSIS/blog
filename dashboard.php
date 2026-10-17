<?php
/**
 * 管理后台 - 留言板总览（需要登录）
 */

require_once __DIR__ . '/auth.php';
requireAuthRedirect();

$csrfToken = generateCsrfToken();

// 读取数据
$comments = readJsonFile(COMMENT_FILE);
$articles = readJsonFile(DATA_FILE);

// 已读状态（本次访问之前的未读数）
$lastRead = (int)(readJsonFile(STATE_FILE)['last_read'] ?? 0);
$unreadCount = 0;
foreach ($comments as $c) {
    if ((int)($c['timestamp'] ?? 0) > $lastRead) $unreadCount++;
}

// 处理删除评论（需要 CSRF）
$deleted = false;
$deleteError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (!is_string($_POST['_csrf'] ?? null) || !verifyCsrfToken($_POST['_csrf'])) {
        $deleteError = '操作已过期，请刷新后重试';
    } else {
        $deleteId = is_string($_POST['delete_id']) ? $_POST['delete_id'] : '';
        mutateJsonFile(COMMENT_FILE, function (array $all) use ($deleteId) {
            return array_values(array_filter($all, function ($c) use ($deleteId) {
                return ($c['id'] ?? '') !== $deleteId;
            }));
        });
        $comments = readJsonFile(COMMENT_FILE);
        $deleted = true;
    }
}

// 标记已读：保存当前时间
writeJsonFile(STATE_FILE, ['last_read' => time()]);

// 按文章 ID 建索引
$articleMap = [];
foreach ($articles as $a) {
    $articleMap[$a['id']] = $a['title'];
}

// 按时间排序（最新在前）
usort($comments, function($a, $b) { return (int)($b['timestamp'] ?? 0) - (int)($a['timestamp'] ?? 0); });

$totalComments = count($comments);
$totalArticles = count($articles);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>留言管理 · <?php echo esc(BLOG_NAME); ?></title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=Noto+Serif+SC:wght@200;300;400;600&display=swap" rel="stylesheet">

<link rel="stylesheet" href="admin.css">
</head>
<body>

<nav>
  <div class="inner">
    <a href="index.html" class="logo"><?php echo esc(BLOG_NAME); ?><span class="ver">v1.0</span></a>
    <div class="links">
      <a href="write.php">写文章</a>
      <a href="dashboard.php" class="active">留言管理<?php if ($unreadCount > 0): ?><span class="badge"><?php echo $unreadCount; ?></span><?php endif; ?></a>
      <a href="write.php?action=logout">登出</a>
      <button class="theme-toggle" id="themeToggle">☽</button>
    </div>
  </div>
</nav>

<div class="container">

  <div class="page-head">
    <h1 class="page-title page-title--admin">留言管理</h1>
    <p class="page-desc">所有留言按时间倒序排列，删除后不可恢复。</p>
  </div>

  <div class="stats">
    <div class="stat-card">
      <div class="stat-num"><?php echo $totalComments; ?></div>
      <div class="stat-label">总留言数</div>
    </div>
    <div class="stat-card">
      <div class="stat-num"><?php echo $totalArticles; ?></div>
      <div class="stat-label">文章数</div>
    </div>
    <div class="stat-card">
      <div class="stat-num"><?php echo $totalArticles > 0 ? round($totalComments / $totalArticles, 1) : 0; ?></div>
      <div class="stat-label">平均留言/文章</div>
    </div>
  </div>

  <h2 class="section-title">所有留言</h2>

  <?php if ($deleted): ?>
    <div class="toast" id="toast" style="display:block;">留言已删除</div>
    <script>setTimeout(function(){ document.getElementById('toast').style.display='none'; }, 2000);</script>
  <?php elseif ($deleteError): ?>
    <div class="toast" id="toast" style="display:block;background:var(--error);color:#fff;"><?php echo esc($deleteError); ?></div>
    <script>setTimeout(function(){ document.getElementById('toast').style.display='none'; }, 2000);</script>
  <?php endif; ?>

  <?php if (empty($comments)): ?>
    <div class="empty-state">还没有留言</div>
  <?php else: ?>
    <?php foreach ($comments as $c): ?>
      <div class="comment-card" id="comment-<?php echo esc($c['id']); ?>">
        <div class="comment-header">
          <span class="comment-article">
            <?php echo esc($articleMap[$c['article_id']] ?? '(文章已删除)'); ?>
          </span>
          <span class="comment-meta">
            <span class="comment-nick"><?php echo esc($c['nickname']); ?></span>
            <?php if (!empty($c['contact'])): ?>
              <span class="comment-contact"><?php echo esc($c['contact']); ?></span>
            <?php endif; ?>
            &nbsp;·&nbsp;<?php echo esc($c['date']); ?>
          </span>
        </div>
        <div class="comment-content"><?php echo nl2br(esc($c['content'])); ?></div>
        <div class="comment-actions">
          <form method="POST" onsubmit="return confirm('确定删除这条留言？');">
            <input type="hidden" name="_csrf" value="<?php echo esc($csrfToken); ?>">
            <input type="hidden" name="delete_id" value="<?php echo esc($c['id']); ?>">
            <button type="submit" class="btn-del">删除</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

</div>

<script>
  // 深色模式
  (function() {
    var saved = localStorage.getItem('mimo-theme');
    if (saved === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
    var btn = document.getElementById('themeToggle');
    if (btn) {
      if (saved === 'dark') btn.textContent = '☀';
      btn.addEventListener('click', function() {
        var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        if (isDark) { document.documentElement.removeAttribute('data-theme'); btn.textContent = '☽'; localStorage.setItem('mimo-theme', 'light'); }
        else { document.documentElement.setAttribute('data-theme', 'dark'); btn.textContent = '☀'; localStorage.setItem('mimo-theme', 'dark'); }
      });
    }
  })();
</script>

</body>
</html>
