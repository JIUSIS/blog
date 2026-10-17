<?php
/**
 * 凡人生平 博客配置文件
 *
 * 密码推荐通过环境变量提供（见 README「配置说明」）；
 * 也可以直接把明文密码写在这里，但不要把真实密码提交到公开仓库。
 */

// 明文密码（可选，二选一；有哈希时优先使用哈希）
define('ADMIN_PASSWORD', getenv('BLOG_ADMIN_PASSWORD') ?: '');

// 密码哈希（推荐）：password_hash('你的密码', PASSWORD_DEFAULT) 的输出
define('ADMIN_PASSWORD_HASH', getenv('BLOG_ADMIN_PASSWORD_HASH') ?: '');

// 登录失败限制：连续失败达到次数后锁定一段时间（秒）
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCK_SECONDS', 600);

// 博客名称（后台页面使用；首页标题在 index.html 里改）
define('BLOG_NAME', '凡人生平');

// 数据文件
define('DATA_FILE', __DIR__ . '/articles.json');
define('COMMENT_FILE', __DIR__ . '/comments.json');
define('STATE_FILE', __DIR__ . '/read_state.json');
define('ATTEMPTS_FILE', __DIR__ . '/login_attempts.json');
