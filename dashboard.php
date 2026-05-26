<?php
/**
 * 管理后台 - 留言板总览（需要登录）
 */

require_once __DIR__ . '/auth.php';
requireAuthRedirect();

// 读取数据
$comments = json_decode(file_get_contents(__DIR__ . '/comments.json'), true) ?? [];
$articles = json_decode(file_get_contents(DATA_FILE), true) ?? [];

// 标记已读：保存当前时间
$stateFile = __DIR__ . '/read_state.json';
$now = time();
$lastRead = 0;
if (file_exists($stateFile)) {
    $state = json_decode(file_get_contents($stateFile), true);
    $lastRead = $state['last_read'] ?? 0;
}
file_put_contents($stateFile, json_encode(['last_read' => $now]));

// 按文章 ID 建索引
$articleMap = [];
foreach ($articles as $a) {
    $articleMap[$a['id']] = $a['title'];
}

// 按时间排序（最新在前）
usort($comments, function($a, $b) { return $b['timestamp'] - $a['timestamp']; });

$totalComments = count($comments);
$totalArticles = count($articles);

// 未读数（标记已读之前的）
$unreadCount = 0;
foreach ($comments as $c) {
    if (($c['timestamp'] ?? 0) > $lastRead) $unreadCount++;
}

// 处理删除评论
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = $_POST['delete_id'];
    $comments = array_filter($comments, function($c) use ($deleteId) {
        return $c['id'] !== $deleteId;
    });
    $comments = array_values($comments);
    file_put_contents(__DIR__ . '/comments.json', json_encode($comments, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    $totalComments = count($comments);
    $deleted = true;
}

$loggedIn = isLoggedIn();
$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>留言管理 · 凡人生平</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Serif+SC:wght@200;300;400;600&family=Space+Grotesk:wght@300;400;500&display=swap" rel="stylesheet">

<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }

  :root {
    --bg: #fafaf9; --ink: #1a1a1a; --muted: #8a8a8a;
    --line: #d4d4d4; --card-bg: #ffffff; --nav-bg: rgba(250,250,249,0.85);
    --serif: 'Noto Serif SC', 'Georgia', serif;
    --sans: 'Space Grotesk', 'Helvetica Neue', sans-serif;
    --error: #c0392b;
  }
  [data-theme="dark"] {
    --bg: #0f0f0f; --ink: #e8e8e8; --muted: #6a6a6a;
    --line: #2a2a2a; --card-bg: #1a1a1a; --nav-bg: rgba(15,15,15,0.9);
    --error: #f87171;
  }
  body {
    font-family: var(--sans); background: var(--bg); color: var(--ink);
    line-height: 1.7; -webkit-font-smoothing: antialiased;
    transition: background 0.4s, color 0.4s;
  }

  nav { position: fixed; top: 0; left: 0; right: 0; z-index: 100; background: var(--nav-bg); backdrop-filter: blur(12px); border-bottom: 1px solid var(--line); }
  nav .inner { max-width: 960px; margin: 0 auto; padding: 0 2rem; display: flex; align-items: center; justify-content: space-between; height: 60px; }
  nav .logo { font-family: var(--serif); font-size: 1.1rem; font-weight: 300; letter-spacing: 0.08em; color: var(--ink); text-decoration: none; }
  nav .logo span { font-weight: 600; }
  nav .links { display: flex; gap: 1.5rem; align-items: center; }
  nav .links a { font-size: 0.8rem; letter-spacing: 0.1em; text-transform: uppercase; color: var(--muted); text-decoration: none; transition: color 0.3s; }
  nav .links a:hover { color: var(--ink); }
  nav .links a.active { color: var(--ink); font-weight: 500; }
  .theme-toggle { background: none; border: 1px solid var(--line); border-radius: 50%; width: 32px; height: 32px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; color: var(--ink); transition: all 0.3s; }
  .theme-toggle:hover { border-color: var(--ink); transform: rotate(30deg); }

  /* 通知徽章 */
  .badge {
    display: inline-block;
    background: var(--ink); color: var(--bg);
    font-size: 0.62rem; min-width: 18px; height: 18px;
    line-height: 18px; text-align: center; border-radius: 9px;
    padding: 0 5px; margin-left: 4px; vertical-align: middle;
    font-weight: 500; transition: background 0.4s, color 0.4s;
  }

  .container { max-width: 800px; margin: 0 auto; padding: 120px 2rem 4rem; }

  /* 统计卡片 */
  .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 3rem; }
  .stat-card {
    border: 1px solid var(--line); padding: 1.5rem; text-align: center;
    background: var(--card-bg); transition: background 0.4s;
  }
  .stat-num { font-family: var(--serif); font-size: 2.5rem; font-weight: 200; }
  .stat-label { font-size: 0.7rem; letter-spacing: 0.15em; text-transform: uppercase; color: var(--muted); margin-top: 0.3rem; }

  /* 留言列表 */
  .comment-card {
    border: 1px solid var(--line); padding: 1.5rem; margin-bottom: 1rem;
    background: var(--card-bg); transition: background 0.4s;
  }
  .comment-header {
    display: flex; justify-content: space-between; align-items: flex-start;
    margin-bottom: 0.5rem; flex-wrap: wrap; gap: 0.5rem;
  }
  .comment-article {
    font-size: 0.7rem; letter-spacing: 0.1em; text-transform: uppercase;
    color: var(--muted); padding: 0.2em 0.8em; border: 1px solid var(--line); border-radius: 2px;
  }
  .comment-meta { font-size: 0.8rem; color: var(--muted); }
  .comment-nick { font-weight: 600; color: var(--ink); }
  .comment-contact { color: var(--muted); font-size: 0.75rem; margin-left: 0.5rem; }
  .comment-content { margin-top: 0.8rem; font-size: 0.95rem; line-height: 1.7; }
  .comment-actions { margin-top: 0.8rem; display: flex; gap: 1rem; }
  .btn-del {
    background: none; border: none; color: var(--error); font-size: 0.75rem;
    cursor: pointer; letter-spacing: 0.05em; opacity: 0.6; transition: opacity 0.3s;
  }
  .btn-del:hover { opacity: 1; }

  .empty-state { text-align: center; padding: 3rem; color: var(--muted); }
  .toast { display: none; position: fixed; bottom: 2rem; right: 2rem; padding: 0.8rem 1.5rem; border-radius: 4px; font-size: 0.85rem; z-index: 999; background: var(--ink); color: var(--bg); }

  @media (max-width: 640px) {
    .stats { grid-template-columns: 1fr; }
    nav .links { display: none; }
  }
</style>
</head>
<body>

<nav>
  <div class="inner">
    <a href="index.html" class="logo">凡人生平</a>
    <div class="links">
      <a href="write.php">写文章</a>
      <a href="dashboard.php" class="active">留言管理<?php if ($unreadCount > 0): ?><span class="badge"><?php echo $unreadCount; ?></span><?php endif; ?></a>
      <a href="write.php?action=logout">登出</a>
      <button class="theme-toggle" id="themeToggle">☽</button>
    </div>
  </div>
</nav>

<div class="container">

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

  <h2 style="font-family:var(--serif);font-weight:300;margin-bottom:1.5rem;">所有留言</h2>

  <?php if (isset($deleted)): ?>
    <div class="toast" id="toast" style="display:block;">留言已删除</div>
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
