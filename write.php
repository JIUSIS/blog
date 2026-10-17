<?php
/**
 * 写文章管理页面（需要登录）
 * 
 * 功能：
 *   - 登录表单
 *   - 写文章表单
 *   - 文章管理（删除）
 */

require_once __DIR__ . '/auth.php';

// 处理登出
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    doLogout();
    header('Location: write.php');
    exit;
}

$csrfToken = generateCsrfToken();
$loginError = '';

// 处理登录表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    if (!is_string($_POST['_csrf'] ?? null) || !verifyCsrfToken($_POST['_csrf'])) {
        $loginError = '页面已过期，请刷新后重试';
    } elseif (!isPasswordConfigured()) {
        $loginError = '尚未配置管理员密码，见 README「配置说明」';
    } else {
        $lockRemaining = loginLockRemaining();
        if ($lockRemaining > 0) {
            $loginError = '尝试次数过多，请 ' . (int)ceil($lockRemaining / 60) . ' 分钟后再试';
        } elseif (doLogin(is_string($_POST['password'] ?? null) ? $_POST['password'] : '')) {
            header('Location: write.php');
            exit;
        } else {
            $loginError = '密码错误，请重试';
        }
    }
}

$loggedIn = isLoggedIn();

// 未读留言计数
$unreadCount = 0;
if ($loggedIn) {
    $comments = readJsonFile(COMMENT_FILE);
    $lastRead = (int)(readJsonFile(STATE_FILE)['last_read'] ?? 0);
    foreach ($comments as $c) {
        if ((int)($c['timestamp'] ?? 0) > $lastRead) $unreadCount++;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<title><?php echo $loggedIn ? '写文章' : '登录'; ?> · <?php echo esc(BLOG_NAME); ?></title>

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
      <a href="index.html">首页</a>
      <?php if ($loggedIn): ?>
        <a href="write.php" style="color:var(--ink);">写文章</a>
        <a href="dashboard.php">留言管理<?php if ($unreadCount > 0): ?><span class="badge"><?php echo $unreadCount; ?></span><?php endif; ?></a>
        <a href="write.php?action=logout" class="btn-logout">登出</a>
      <?php endif; ?>
      <button class="theme-toggle" id="themeToggle">☽</button>
    </div>
  </div>
</nav>

<div class="container">

<?php if (!$loggedIn): ?>
  <!-- ═══ 登录表单 ═══ -->
  <div class="login-box">
    <h1 class="login-title">管理员登录</h1>
    <p class="login-desc">输入密码后即可写文章</p>

    <?php if ($loginError): ?>
      <p class="error-msg"><?php echo esc($loginError); ?></p>
    <?php endif; ?>

    <?php if (!isPasswordConfigured()): ?>
      <p class="error-msg">尚未配置管理员密码，见 README「配置说明」</p>
    <?php endif; ?>

    <form method="POST" action="write.php">
      <input type="hidden" name="action" value="login">
      <input type="hidden" name="_csrf" value="<?php echo esc($csrfToken); ?>">
      <div class="form-group">
        <label class="form-label">密码</label>
        <input type="password" class="form-input" name="password" placeholder="输入管理员密码..." autofocus required>
      </div>
      <button type="submit" class="btn">登 录</button>
    </form>
  </div>

<?php else: ?>
  <!-- ═══ 写文章页面 ═══ -->
  <div class="page-head">
    <h1 class="page-title">写一篇新文章</h1>
    <p class="page-desc">在下方填写内容，点击发布即可出现在博客首页。</p>
  </div>

  <form id="articleForm" onsubmit="return false;">
    <input type="hidden" id="csrfToken" value="<?php echo esc($csrfToken); ?>">

    <div class="form-group">
      <label class="form-label">文章标题</label>
      <input type="text" class="form-input" id="inputTitle" placeholder="输入一个吸引人的标题..." maxlength="100" required>
    </div>

    <div class="form-group">
      <label class="form-label">分类标签</label>
      <select class="form-select" id="inputTag" onchange="if(this.value==='__custom__'){document.getElementById('customTagWrap').style.display='block';document.getElementById('customTag').focus();}else{document.getElementById('customTagWrap').style.display='none';}">
        <option value="随笔">随笔</option>
        <option value="设计">设计</option>
        <option value="技术">技术</option>
        <option value="生活">生活</option>
        <option value="教程">教程</option>
        <option value="观点">观点</option>
        <option value="__custom__">✏️ 自定义标签...</option>
      </select>
      <div id="customTagWrap" style="display:none;margin-top:0.6rem;">
        <input type="text" class="form-input" id="customTag" placeholder="输入自定义标签名" maxlength="20">
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">文章正文</label>
      <textarea class="form-textarea" id="inputContent" placeholder="写下你的想法...

每段之间空一行，会自动分段。" required></textarea>
      <p class="form-hint">支持纯文本，段落之间用空行分隔。Ctrl + Enter 快速发布。</p>
    </div>

    <div class="btn-row">
      <button type="button" class="btn-primary" id="btnPublish" onclick="publishArticle()">发布文章</button>
      <button type="button" class="btn-secondary" onclick="previewArticle()">预览</button>
      <button type="button" class="btn-secondary" onclick="clearForm()">清空</button>
    </div>
  </form>

  <!-- 预览区 -->
  <div id="previewArea" style="display:none;margin-top:3rem;padding-top:2rem;border-top:1px solid var(--line);">
    <div style="font-size:0.75rem;color:var(--muted);margin-bottom:1rem;display:flex;gap:1rem;">
      <span id="previewDate"></span>
      <span id="previewTag"></span>
    </div>
    <h2 id="previewTitle" style="font-family:var(--serif);font-size:1.4rem;font-weight:400;margin-bottom:1.5rem;"></h2>
    <div id="previewBody" style="font-family:var(--serif);font-weight:300;line-height:2;"></div>
  </div>

  <!-- 已发布文章 -->
  <div class="manage-section">
    <div class="manage-title">已发布的文章</div>
    <div id="articleList"><div class="empty-state">加载中...</div></div>
  </div>

<?php endif; ?>

</div>

<div class="toast" id="toast"></div>

<script>
  // ─── 深色模式 ───
  const themeToggle = document.getElementById('themeToggle');
  if (localStorage.getItem('mimo-theme') === 'dark') {
    document.documentElement.setAttribute('data-theme', 'dark');
    themeToggle.textContent = '☀';
  }
  themeToggle.addEventListener('click', function() {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    if (isDark) {
      document.documentElement.removeAttribute('data-theme');
      themeToggle.textContent = '☽';
      localStorage.setItem('mimo-theme', 'light');
    } else {
      document.documentElement.setAttribute('data-theme', 'dark');
      themeToggle.textContent = '☀';
      localStorage.setItem('mimo-theme', 'dark');
    }
  });

<?php if ($loggedIn): ?>
  // ─── 写文章功能 ───
  const API_URL = 'api.php';

  function showToast(msg, type) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'toast ' + type + ' show';
    setTimeout(function() { t.classList.remove('show'); }, 3000);
  }

  function escapeHtml(s) {
    if (!s) return '';
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function getTag() {
    var sel = document.getElementById('inputTag');
    if (sel.value === '__custom__') {
      return document.getElementById('customTag').value.trim() || '随笔';
    }
    return sel.value;
  }

  async function publishArticle() {
    const title = document.getElementById('inputTitle').value.trim();
    const tag = getTag();
    const content = document.getElementById('inputContent').value.trim();

    if (!title) { showToast('请输入标题', 'error'); return; }
    if (!content) { showToast('请输入内容', 'error'); return; }

    const btn = document.getElementById('btnPublish');
    btn.disabled = true;
    btn.textContent = '发布中...';

    try {
      const csrf = document.getElementById('csrfToken').value;
      const res = await fetch(API_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
        body: JSON.stringify({ title, tag, content, _csrf: csrf })
      });
      const data = await res.json();

      if (data.success) {
        showToast('✅ 文章发布成功！', 'success');
        clearForm();
        loadArticles();
      } else if (data.need_login) {
        // session 过期，刷新页面
        showToast('登录已过期，请重新登录', 'error');
        setTimeout(function() { location.reload(); }, 1500);
      } else {
        showToast('❌ ' + (data.error || '发布失败'), 'error');
      }
    } catch (err) {
      showToast('❌ 网络错误', 'error');
    }

    btn.disabled = false;
    btn.textContent = '发布文章';
  }

  function previewArticle() {
    const title = document.getElementById('inputTitle').value.trim();
    const tag = getTag();
    const content = document.getElementById('inputContent').value.trim();
    if (!title && !content) { showToast('先写点内容再预览', 'error'); return; }

    const now = new Date();
    document.getElementById('previewDate').textContent = now.getFullYear() + '.' + String(now.getMonth()+1).padStart(2,'0') + '.' + String(now.getDate()).padStart(2,'0');
    document.getElementById('previewTag').textContent = tag;
    document.getElementById('previewTitle').textContent = title || '（无标题）';

    const paragraphs = content.split(/\n\s*\n/).filter(function(p) { return p.trim(); });
    document.getElementById('previewBody').innerHTML = paragraphs.map(function(p) {
      return '<p style="margin-bottom:1.5rem;">' + p.trim().replace(/\n/g, '<br>') + '</p>';
    }).join('');

    document.getElementById('previewArea').style.display = 'block';
    document.getElementById('previewArea').scrollIntoView({ behavior: 'smooth' });
  }

  function clearForm() {
    document.getElementById('inputTitle').value = '';
    document.getElementById('inputContent').value = '';
    document.getElementById('inputTag').value = '随笔';
    document.getElementById('customTagWrap').style.display = 'none';
    document.getElementById('customTag').value = '';
    document.getElementById('previewArea').style.display = 'none';
  }

  async function loadArticles() {
    const listEl = document.getElementById('articleList');
    try {
      const res = await fetch(API_URL);
      const data = await res.json();
      if (!data.success || !data.articles || data.articles.length === 0) {
        listEl.innerHTML = '<div class="empty-state">还没有文章，写一篇试试 ✍️</div>';
        return;
      }
      listEl.innerHTML = data.articles.map(function(art) {
        return '<div class="article-manage-item">' +
          '<div class="article-manage-info">' +
            '<h4>' + escapeHtml(art.title) + '</h4>' +
            '<span>' + escapeHtml(art.date) + ' · ' + escapeHtml(art.tag) + '</span>' +
          '</div>' +
          '<button class="btn-delete" onclick="deleteArticle(\'' + art.id + '\')">删除</button>' +
        '</div>';
      }).join('');
    } catch (err) {
      listEl.innerHTML = '<div class="empty-state">加载失败</div>';
    }
  }

  async function deleteArticle(id) {
    if (!confirm('确定删除这篇文章？')) return;
    try {
      const csrf = document.getElementById('csrfToken').value;
      const res = await fetch(API_URL + '?id=' + encodeURIComponent(id) + '&_csrf=' + encodeURIComponent(csrf), {
        method: 'DELETE',
        headers: { 'X-CSRF-Token': csrf }
      });
      const data = await res.json();
      if (data.success) {
        showToast('文章已删除', 'success');
        loadArticles();
      } else if (data.need_login) {
        showToast('登录已过期，请重新登录', 'error');
        setTimeout(function() { location.reload(); }, 1500);
      } else {
        showToast('删除失败', 'error');
      }
    } catch (err) {
      showToast('网络错误', 'error');
    }
  }

  // Ctrl+Enter 快捷发布
  document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
      e.preventDefault();
      publishArticle();
    }
  });

  loadArticles();
<?php endif; ?>
</script>

</body>
</html>
