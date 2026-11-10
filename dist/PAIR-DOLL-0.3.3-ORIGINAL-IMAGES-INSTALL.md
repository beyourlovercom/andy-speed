# Andy Speed V0.3.3-dev 原图保留版 — 手工安装

- **安装包**：`andy-speed-pairdoll-v0.3.3-dev-original-images-20261110.zip`
- **来源提交**：`e085785c27bbcbb02136022f68048229b0d5d7df`
- **SHA-256**：`ebe075011bd926923615e8c134b211f3aa5b48de33ceec54fdeacbad515ae682`
- **ZIP 大小**：20,569 字节
- **PHP / 插件安全 / 中文界面 / 原图保留测试**：PASS；GitHub CI Run #104 PASS
- **新图片策略**：Andy Speed 不进行图片转码、WebP 替换或二次压缩，保留当前图片文件本身、现有 URL 和 Elementor 配置。
- **R2 保留**：可选的 PairDoll 手机轮播停止自动切换。
- **R3 改为**：默认关闭的「PairDoll 手机首屏原图预加载」，只对已有的 JPEG 原图提高加载优先级；不改变文件格式或内容。
- **旧设置**：此前 `pairdoll_mobile_hero_webp` 和 `pairdoll_logo_webp` 即使在数据库中残留也不会被执行，旧开关不再展示。
- **验证事实**：2026-11-10 生产站手机及桌面浏览器均确认首页轮播仍为原始 `Photo-album-banner-correct-size.jpg`、Logo 仍为 `PAIRDOLL-LOGO.png`；不需要对正式站执行反向媒体覆盖。此前生成的三份独立压缩资源未在首页启用。
- **手工覆盖**：WordPress 后台 > 插件 > 安装插件 > 上传 ZIP > 替换已安装版本。
- **启用门禁**：默认 Audit Only 和 Emergency OFF；升级不会自动打开新预加载开关，也不会改动 LiteSpeed 缓存或支付。
- **状态**：实验版本，非 Stable；尚未在 pairdoll.com 部署此版本，未完成 Google / GTmetrix 双 90+ 认证。
