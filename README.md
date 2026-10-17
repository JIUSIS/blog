# 凡人生平 Blog

> 一个极简线条风格的个人博客系统。使用原生 HTML / CSS / JavaScript + PHP 构建，文章和评论以 JSON 文件保存，无需数据库，适合个人写作、作品展示和轻量部署。

![PHP](https://img.shields.io/badge/PHP-轻量后端-777BB4?style=flat-square&logo=php&logoColor=white)
![Vanilla JS](https://img.shields.io/badge/JavaScript-原生交互-F7DF1E?style=flat-square&logo=javascript&logoColor=111)
![JSON](https://img.shields.io/badge/Storage-JSON-555?style=flat-square)
![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)

## ✨ 项目亮点

- **无需数据库**：文章、评论、已读状态都存储在 JSON 文件中，普通 PHP 虚拟主机即可部署。
- **极简线条视觉**：黑白灰配色、细线分隔、衬线标题，适合个人博客和作品集展示。
- **前后台一体**：前台阅读、评论提交、后台写文章、文章管理和留言管理都已包含。
- **轻量可修改**：没有复杂构建流程，不需要 npm / webpack，直接修改文件即可运行。
- **基础安全处理**：密码哈希、登录失败锁定、CSRF Token、输出转义、评论频率限制、JSON 文件加锁、评论 IP 不对外返回。

## 🖼️ 功能预览

### 前台阅读

| 功能 | 说明 |
| --- | --- |
| 文章列表 | 展示标题、日期、标签和摘要 |
| 文章弹窗 | 点击文章后以弹窗形式阅读全文 |
| 深色 / 浅色主题 | 一键切换阅读主题 |
| 阅读进度条 | 页面顶部显示滚动阅读进度 |
| 搜索 | 按标题、标签和正文内容搜索 |
| 评论 | 每篇文章可查看和提交评论 |
| 回到顶部 | 长页面阅读时快速返回顶部 |

### 后台管理

| 功能 | 说明 |
| --- | --- |
| 管理员登录 | 使用密码哈希或环境变量中的密码登录，连续失败会锁定 |
| 写文章 | 支持标题、标签、正文发布 |
| 自定义标签 | 内置标签外可输入自定义分类 |
| 文章预览 | 发布前可预览内容 |
| 删除文章 | 后台管理已发布文章 |
| 留言管理 | 查看所有评论、关联文章和联系方式 |
| 未读统计 | 显示新留言数量和已读状态 |

## 🧱 技术栈

- **前端**：HTML、CSS、原生 JavaScript
- **后端**：PHP（需要 `mbstring` 扩展）
- **数据存储**：JSON 文件 + `flock` 文件锁
- **鉴权**：PHP Session、`password_hash` / `password_verify`
- **接口格式**：JSON API
- **字体**：Google Fonts（`Noto Serif SC` / `Space Grotesk`）

## 📂 项目结构

```text
blog/
├── index.html        # 博客前台：首页、文章列表、搜索、阅读弹窗、评论交互
├── write.php         # 管理员登录、写文章、文章管理
├── dashboard.php     # 留言管理后台
├── api.php           # 文章 API：读取、发布、删除
├── comments.php      # 评论 API：读取、提交
├── auth.php          # 登录、退出、Session、CSRF、登录锁定
├── lib.php           # JSON 读写（文件锁）、客户端 IP 等公共工具
├── config.php        # 博客名称、密码来源、数据文件路径
├── .htaccess         # 拒绝直接下载 JSON 数据文件（Apache）
├── articles.json     # 文章数据（作为初始内容提交在仓库里）
├── comments.json     # 评论数据（含访客 IP，建议部署后不再提交变更）
├── read_state.json   # 留言已读状态
├── login_attempts.json  # 登录失败记录（自动创建，已加入 .gitignore）
├── LICENSE
└── README.md
```

## 🚀 快速开始

### 1. 准备环境

需要一个支持 PHP 的环境：

- PHP 7.4+ / PHP 8.x（需要 `mbstring`，`json` 默认内置）
- 本地 PHP 内置服务器、Apache、Nginx + PHP-FPM 或普通虚拟主机均可

### 2. 配置管理员密码

推荐使用密码哈希，通过环境变量传入：

```bash
# Linux / macOS
export BLOG_ADMIN_PASSWORD_HASH='§password_hash 的输出§'

# 也可以临时用明文（不推荐长期使用）
export BLOG_ADMIN_PASSWORD='你的密码'
```

生成哈希：

```bash
php -r "echo password_hash('你的密码', PASSWORD_DEFAULT), PHP_EOL;"
```

Apache 可在虚拟主机配置里用 `SetEnv BLOG_ADMIN_PASSWORD_HASH "..."`；
PHP-FPM 可在 pool 配置里用 `env[BLOG_ADMIN_PASSWORD_HASH] = "..."`。

> 不配置密码时后台无法登录，登录页会给出提示。
> 也可以直接改 `config.php` 里的 `ADMIN_PASSWORD`，但不要把真实密码提交到公开仓库。

### 3. 本地运行

进入项目目录后运行：

```bash
php -S 127.0.0.1:8000
```

访问博客首页：

```text
http://127.0.0.1:8000/index.html
```

后台入口：

```text
http://127.0.0.1:8000/write.php
```

留言管理：

```text
http://127.0.0.1:8000/dashboard.php
```

### 4. 部署到服务器

把项目文件上传到支持 PHP 的 Web 目录即可，例如：

```text
/var/www/html/blog
```

确保 PHP 对数据文件所在目录有写权限（缺失的 `comments.json` / `read_state.json` / `login_attempts.json` 会自动创建）：

```text
articles.json
comments.json
read_state.json
login_attempts.json
```

`.htaccess` 只对 Apache 生效（需要允许 `AllowOverride`）。
使用 Nginx 时请自己在站点配置里屏蔽 JSON：

```nginx
location ~ \.json$ {
    deny all;
}
```

## ⚙️ 配置说明

主要配置位于 `config.php`：

- `ADMIN_PASSWORD_HASH`：管理员密码哈希（来自环境变量，推荐）
- `ADMIN_PASSWORD`：明文密码（来自环境变量，作为兜底）
- `LOGIN_MAX_ATTEMPTS` / `LOGIN_LOCK_SECONDS`：登录失败锁定策略，默认 5 次 / 10 分钟
- `BLOG_NAME`：博客名称（后台页面标题与导航使用；首页标题文字在 `index.html` 里改）
- `DATA_FILE`：文章数据文件路径

## 🔌 API 简表

### 文章 API：`api.php`

| 方法 | 路径 | 说明 | 权限 |
| --- | --- | --- | --- |
| GET | `api.php` | 获取文章列表 | 公开 |
| POST | `api.php` | 发布文章 | 需要登录 + CSRF Token |
| DELETE | `api.php?id=xxx` | 删除文章 | 需要登录 + CSRF Token |

### 评论 API：`comments.php`

| 方法 | 路径 | 说明 | 权限 |
| --- | --- | --- | --- |
| GET | `comments.php?article_id=xxx` | 获取指定文章评论（不返回 IP） | 公开 |
| POST | `comments.php` | 提交评论（文章必须存在；同 IP 10 秒限一条） | 公开 |

参数错误返回 `400`，文章不存在返回 `404`，发送过快返回 `429`，未登录返回 `401`。

## 🗃️ 数据格式示例

文章（存原文，展示时统一转义，避免双重转义）：

```json
{
  "id": "1156b075a2da8ca7",
  "title": "文章标题",
  "tag": "随笔",
  "content": "文章正文",
  "date": "2026.05.25",
  "timestamp": 1779639913
}
```

评论：

```json
{
  "id": "846e9f39417465c7",
  "article_id": "1156b075a2da8ca7",
  "nickname": "读者",
  "contact": "",
  "content": "评论内容",
  "date": "2026.05.24 22:38",
  "timestamp": 1779633538,
  "ip": "127.0.0.1"
}
```

> `ip` 只保存在服务端用于频率限制，评论接口不会返回它。

## 🔐 安全注意事项

- 仓库不再内置默认密码；部署前必须配置管理员密码（哈希优先）。
- 登录连续失败会按 IP 锁定（默认 5 次 / 10 分钟），失败记录在 `login_attempts.json`。
- `articles.json`、`comments.json`、`read_state.json` 需要可写，但不要给整个目录过宽权限。
- JSON 数据文件不应能被直接下载；Apache 用仓库自带 `.htaccess`，Nginx 参考上面的 `location` 配置。
- 如果本站有真实访客评论，建议执行 `git rm --cached comments.json read_state.json`，避免访客 IP 等数据继续提交到公开仓库。
- 评论系统只有基础频率限制，不适合高流量或强对抗场景。
- JSON 文件存储适合小型站点，不适合大量文章、高并发写入或复杂查询。
- 如果开启 HTTPS，Session Cookie 会自动加上 `secure`（同时保留 `httponly` / `samesite=Strict`）。
- `index.html` 里的字体来自 Google Fonts，中国大陆网络下可能加载失败并回退到系统字体，可自行换成本地字体。

## ✅ 适用场景

- 个人博客
- 轻量写作站点
- 作品集展示
- PHP 虚拟主机部署
- 小型内容管理系统练习项目

## ❌ 不适合场景

- 多用户 CMS
- 高并发内容平台
- 大量文章和评论数据
- 复杂权限系统
- 需要全文索引和复杂查询的站点

## 📄 License

MIT，见 [LICENSE](LICENSE)。
