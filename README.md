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
- **基础安全处理**：Session 登录、CSRF Token、HTML 转义、评论频率限制等基础防护。

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
| 管理员登录 | 使用 `config.php` 中的密码登录 |
| 写文章 | 支持标题、标签、正文发布 |
| 自定义标签 | 内置标签外可输入自定义分类 |
| 文章预览 | 发布前可预览内容 |
| 删除文章 | 后台管理已发布文章 |
| 留言管理 | 查看所有评论、关联文章和联系方式 |
| 未读统计 | 显示新留言数量和已读状态 |

## 🧱 技术栈

- **前端**：HTML、CSS、原生 JavaScript
- **后端**：PHP
- **数据存储**：JSON 文件
- **鉴权**：PHP Session
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
├── auth.php          # 登录、退出、Session、CSRF、公共响应方法
├── config.php        # 博客名称、管理员密码、数据文件路径
├── articles.json     # 文章数据
├── comments.json     # 评论数据
├── read_state.json   # 留言已读状态
└── README.md
```

## 🚀 快速开始

### 1. 准备环境

需要一个支持 PHP 的环境：

- PHP 7.4+ / PHP 8.x
- 本地 PHP 内置服务器、Apache、Nginx + PHP-FPM 或普通虚拟主机均可

### 2. 本地运行

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

### 3. 部署到服务器

把项目文件上传到支持 PHP 的 Web 目录即可，例如：

```text
/var/www/html/blog
```

确保 PHP 对这些 JSON 文件有读写权限：

```text
articles.json
comments.json
read_state.json
```

如果文件不存在，部分接口会自动初始化空 JSON 文件。

## ⚙️ 配置说明

主要配置位于：

```text
config.php
```

可以配置：

```php
// 管理员密码
ADMIN_PASSWORD

// 博客名称
BLOG_NAME

// 文章数据文件路径
DATA_FILE
```

> ⚠️ **重要：公开部署前必须修改默认管理员密码。**
> 当前示例项目中默认密码是为了本地测试方便，正式部署时请改成强密码，并避免把真实生产密码提交到公开仓库。

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
| GET | `comments.php?article_id=xxx` | 获取指定文章评论 | 公开 |
| POST | `comments.php` | 提交评论 | 公开，带基础频率限制 |

## 🗃️ 数据格式示例

文章：

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

## 🔐 安全注意事项

这个项目定位是个人轻量博客，不是高强度 CMS。公开部署前建议检查：

- 必须修改默认管理员密码。
- 建议给后台路径加服务器层面的额外保护。
- `articles.json`、`comments.json`、`read_state.json` 需要可写，但不要给整个目录过宽权限。
- 评论系统只有基础频率限制，不适合高流量或强对抗场景。
- JSON 文件存储适合小型站点，不适合大量文章、高并发写入或复杂查询。
- 如果开启 HTTPS，建议配置 PHP Session Cookie 的 `secure` / `httponly` / `samesite` 属性。

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

MIT
