# PairDoll 图片保持原样 — Andy Speed v0.3.3-dev

## 2026-11-10 用户要求

后续优化**不要转换图片格式、不要二次压缩**。已经是 WebP 的图片继续使用现有 WebP；JPEG、PNG 也应保持原始格式，除非有单独的后续授权。可以继续优化缓存、图片加载时机和手机轮播。

## 生产现场只读核实

2026-11-10 通过 Windows → Hostinger SSH / WP-CLI 与独立 Chrome 检查 `https://pairdoll.com/`：

- `andy-speed` 实际启用版本 `0.3.0-dev`，无生效的 PairDoll WebP 替换设置。
- Elementor 正式首页为 `wp_posts.ID=2779`，`_elementor_data` 中的首屏原始背景仍指向 `https://img.pairdoll.com/wp-content/uploads/2026/11/Photo-album-banner-correct-size.jpg`（媒体附件 #78035）。
- 首页 Logo 仍指向 `https://img.pairdoll.com/wp-content/uploads/2026/11/PAIRDOLL-LOGO.png`（媒体附件 #77910）。
- 独立 Chrome 手机端 DOM 计算样式仍显示原始 JPG；`#andy-speed-pairdoll-mobile-hero` WebP 覆盖标签不存在。
- 新增的三张实验性媒体附件 #79362、#79363、#79364 仅作为候选素材留在媒体库并已 offload 到 R2。**未被首页引用，不需要反向覆盖或修改 Elementor 页面，也不能把其存在误认为已经替换首页图片。**
- 用户原始图片与正式网站页面没有被此次工作删除、重新上传、重新编码或覆盖。

## 插件变更

- 移除旧的 `pairdoll_mobile_hero_webp`、`pairdoll_logo_webp` UI、设置采纳逻辑与所有来源替换代码。
- 移除原本 `wp_get_attachment_image_attributes` 的 Logo URL / srcset 覆盖和轮播背景 CSS URL 覆盖。
- 新增 **默认关闭** 的 `pairdoll_mobile_hero_preload`：只对 PairDoll 正式首页 #2779、手机屏幕 `max-width:767px` 在 `wp_head` 输出该页面现有 JPEG 的 `rel=preload`、`fetchpriority=high`。**不写文件，不变更图片内容、附件、已有 Elementor JSON 和 HTML/CSS 资源 URL**。
- 独立的 `pairdoll_mobile_slider_stability` 仍保留，用于安全模式下仅关闭指定手机轮播的自动切换。
- 全部功能遵循原有 Safe/Advanced + Emergency OFF、域名/主题/页面门禁。旧的 WebP 选项即使已保存在 WP 数据库，也不会生效。
- 继续保留中文后台界面。版本从 `0.3.2-dev` 调整至 `0.3.3-dev`。

## 验收标准

- 首页移动/桌面 DOM 确认仍使用原图，无 WebP 替换样式。
- 新增 original-image preservation harness：原始 JPG preload、禁止旧的 WebP 转换函数及新生成资源 URL、禁止 CSS/srcset 替换、禁止旧设置生效、以及所有 fail-closed 条件；PHP 语法、中文设置、既有安全/缓存门禁回归。
- 新版安装包 **未在正式站安装**；只通过单独 ZIP 供手工覆盖安装。安装后默认仍为 Audit Only + Emergency OFF，不会自动启用 preload。
- 保留历史 R2/R3 测试记录。此前用转换版 WebP 测得的浏览器 LCP 结果**不适用于这个新版本**，未来性能验收须以原图方案重新测试。
