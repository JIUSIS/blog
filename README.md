# Blog

一个轻量级个人博客系统，使用原生 HTML、CSS、JavaScript 和 PHP 构建，不依赖数据库。文章、评论和后台状态都以 JSON 文件保存，适合部署在普通 PHP 虚拟主机、轻量服务器或个人站点目录中。

项目风格偏极简线条设计，前台侧重阅读体验，后台侧重快速写作和留言管理。

## 功能特性

- 极简个人博客首页
- 文章列表展示
- 文章详情弹窗阅读
- 深色 / 浅色主题切换
- 阅读进度条
- 文章搜索
- 回到顶部
- 评论提交
- 评论列表展示
- 评论频率限制
- 管理员登录
- 后台写文章
- 文章预览
- 自定义文章标签
- 文章删除
- 留言管理后台
- 未读留言计数
- 评论删除
- CSRF Token 防护
- Session 登录态管理
- HTML 转义，降低 XSS 风险
- JSON 文件存储，无需 MySQL

## 技术栈

- 前端：HTML、CSS、JavaScript
- 后端：PHP
- 数据存储：JSON 文件
- 字体：Google Fonts
- 鉴权：PHP Session
- 接口格式：JSON API

## 页面和文件说明

```text
blog/
├── index.html        # 博客前台首页，负责文章展示、搜索、阅读弹窗、评论交互
├── write.php         # 管理员登录页、写文章页、文章管理页
├── dashboard.php     # 留言管理后台
├── api.php           # 文章 API：读取、发布、删除
├── comments.php      # 评论 API：读取、提交
├── auth.php          # 登录、退出、Session、CSRF、公共响应方法
├── config.php        # 博客名称、管理员密码、数据文件路径
├── articles.json     # 文章数据
├── comments.json     # 评论数据
└── read_state.json   # 留言已读状态
```

## 前台功能

### 首页阅读

`index.html` 是博客前台入口。页面会从 `api.php` 获取文章列表，并在前端完成渲染。

主要能力：

- 展示文章标题、日期、标签和摘要
- 点击文章后以弹窗形式阅读全文
- 根据文章标题、标签和内容搜索
- 阅读弹窗内展示评论
- 支持提交评论
- 顶部阅读进度条
- 深色 / 浅色主题切换
- 回到顶部按钮

### 评论

评论由 `comments.php` 处理。

支持：

- 按文章 ID 获取评论
- 提交昵称、联系方式和评论内容
- 评论内容长度限制
- 同一 IP 简单频率限制
- 评论内容 HTML 转义

评论会保存到：

```text
comments.json
```

## 后台功能

### 登录和写作

`write.php` 同时承担登录页和写作后台。

登录后可以：

- 发布新文章
- 选择内置标签
- 输入自定义标签
- 预览文章
- 删除已发布文章
- 查看未读留言数量

文章会保存到：

```text
articles.json
```

### 留言管理

`dashboard.php` 是留言管理后台。

功能包括：

- 查看所有评论
- 显示评论所属文章
- 显示留言者昵称和联系方式
- 删除评论
- 统计总留言数、文章数和平均留言数
- 记录留言已读时间

已读状态会保存到：

```text
read_state.json
```

## API 说明

### 文章 API

文件：

```text
api.php
```

接口：

```text
GET    api.php          获取文章列表
POST   api.php          发布文章，需要登录和 CSRF Token
DELETE api.php?id=xxx   删除文章，需要登录和 CSRF Token
```

### 评论 API

文件：

```text
comments.php
```

接口：

```text
GET  comments.php?article_id=xxx   获取指定文章评论
POST comments.php                  提交评论
```

## 部署方式

把项目文件放到支持 PHP 的 Web 目录即可，例如：

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

## 本地运行

可以用 PHP 内置服务器测试：

```bash
php -S 127.0.0.1:8000
```

然后访问：

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

## 配置

主要配置位于：

```text
config.php
```

可以配置：

- 管理员密码
- 博客名称
- 文章数据文件路径

部署前必须修改默认管理员密码。

## 安全注意事项

这个项目适合个人轻量站点，但部署前需要注意：

- 不要使用默认管理员密码。
- 确保 `articles.json`、`comments.json`、`read_state.json` 可写但不要暴露不必要的目录权限。
- 如果站点公开访问，建议给后台路径加服务器层面的额外保护。
- 评论系统只做了基础频率限制，不适合高流量或强对抗场景。
- JSON 文件存储适合小型博客，不适合大量文章和高并发写入。
- 如果开启 HTTPS，请配置 PHP Session Cookie 的 secure 属性。

## 数据格式

文章示例：

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

评论示例：

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

## 适用场景

- 个人博客
- 轻量写作站点
- 无数据库虚拟主机
- 小型作品展示
- PHP 入门级内容管理系统

## 不适合的场景

- 高并发博客
- 多用户 CMS
- 权限复杂的内容平台
- 大量文章和评论数据
- 需要全文索引和复杂查询的站点

## License

MIT
