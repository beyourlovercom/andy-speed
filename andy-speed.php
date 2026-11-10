<?php
/**
 * Plugin Name: Andy Speed
 * Description: WordPress 性能诊断与可回滚优化插件；默认仅审计，所有优化默认关闭。
 * Version: 0.3.2-dev
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: beyourlovercom
 * License: GPL-2.0-or-later
 * Text Domain: andy-speed
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Andy_Speed' ) ) {
final class Andy_Speed {
    const VERSION = '0.3.2-dev';
    const OPTION = 'andy_speed_settings_v1';
    const SLUG = 'andy-speed';

    public static function defaults() {
        return array(
            'mode' => 'audit',
            'emergency_off' => true,
            'analytics_diagnostics_debug_only' => false,
            'byl_v3_common_css_bundle' => false,
            'newsletter_defer' => false,
            'pairdoll_mobile_slider_stability' => false,
            'pairdoll_mobile_hero_webp' => false,
            'pairdoll_logo_webp' => false,
        );
    }

    public static function sanitize( $raw ) {
        $raw = is_array( $raw ) ? $raw : array();
        $mode = isset( $raw['mode'] ) ? sanitize_key( $raw['mode'] ) : 'audit';
        if ( ! in_array( $mode, array( 'audit', 'safe', 'advanced' ), true ) ) {
            $mode = 'audit';
        }
        return array(
            'mode' => $mode,
            'emergency_off' => ! empty( $raw['emergency_off'] ),
            'analytics_diagnostics_debug_only' => ! empty( $raw['analytics_diagnostics_debug_only'] ),
            'byl_v3_common_css_bundle' => ! empty( $raw['byl_v3_common_css_bundle'] ),
            'newsletter_defer' => ! empty( $raw['newsletter_defer'] ),
            'pairdoll_mobile_slider_stability' => ! empty( $raw['pairdoll_mobile_slider_stability'] ),
            'pairdoll_mobile_hero_webp' => ! empty( $raw['pairdoll_mobile_hero_webp'] ),
            'pairdoll_logo_webp' => ! empty( $raw['pairdoll_logo_webp'] ),
        );
    }

    public static function settings() {
        return self::sanitize( wp_parse_args( (array) get_option( self::OPTION, array() ), self::defaults() ) );
    }

    public static function analytics_debug_only( $existing ) {
        // Cooperation uses an optional Core filter; no frontend rewriting and
        // no effect unless an admin explicitly enables safe mode AND this gate.
        $settings = self::settings();
        if ( $settings['emergency_off'] || ! in_array( $settings['mode'], array( 'safe', 'advanced' ), true ) ) {
            return $existing;
        }
        return $settings['analytics_diagnostics_debug_only'] ? true : $existing;
    }

    public static function byl_v3_common_css_bundle( $existing ) {
        $settings = self::settings();
        if ( $settings['emergency_off'] || ! in_array( $settings['mode'], array( 'safe', 'advanced' ), true ) ) {
            return $existing;
        }
        return $settings['byl_v3_common_css_bundle'] ? true : $existing;
    }

    public static function newsletter_defer( $existing ) {
        $settings = self::settings();
        if ( $settings['emergency_off'] || ! in_array( $settings['mode'], array( 'safe', 'advanced' ), true ) ) {
            return $existing;
        }
        return $settings['newsletter_defer'] ? true : $existing;
    }

    public static function maybe_defer_newsletter() {
        // Attach after Core enqueues the script; never remove/rewrite its source.
        if ( ! self::newsletter_defer( false ) ) { return; }
        if ( function_exists( 'wp_script_is' ) && wp_script_is( 'yby-newsletter', 'enqueued' ) && function_exists( 'wp_script_add_data' ) ) {
            wp_script_add_data( 'yby-newsletter', 'strategy', 'defer' );
        }
    }

    /**
     * BYL Dev adapter: immutable, explicitly opt-in replacement of six
     * static theme CSS URLs with one pre-built asset. Other theme files,
     * styles from third-party plugins, fonts, payments, and JS are untouched.
     * If any source changes (including CRLF), fail closed to legacy CSS.
     */
    public static function maybe_bundle_theme_css() {
        if ( ! self::byl_v3_common_css_bundle( false ) || is_admin() ) { return; }
        if ( ! function_exists( 'get_stylesheet' ) || get_stylesheet() !== 'beyourlover-v3' ) { return; }
        if ( ! function_exists( 'wp_styles' ) || ! function_exists( 'get_stylesheet_directory' ) ) { return; }
        $styles = wp_styles();
        if ( ! is_object( $styles ) || ! isset( $styles->registered ) ) { return; }
        $root = get_stylesheet_directory();
        $base_url = function_exists( 'get_stylesheet_directory_uri' ) ? get_stylesheet_directory_uri() : '';
        if ( ! is_string( $base_url ) || '' === $base_url ) { return; }
        $bundle_rel = 'assets/byl-v3/common.bundle.css';
        $manifest_rel = 'assets/byl-v3/common.bundle.manifest.json';
        $bundle = plugin_dir_path( __FILE__ ) . $bundle_rel;
        $manifest_path = plugin_dir_path( __FILE__ ) . $manifest_rel;
        if ( ! is_file( $bundle ) || ! is_file( $manifest_path ) ) { return; }
        $manifest = json_decode( (string) file_get_contents( $manifest_path ), true );
        if ( ! is_array( $manifest ) || ( $manifest['schema'] ?? null ) !== 1 ) { return; }
        $names = array( 'style.css','assets/css/tokens.css','assets/css/components.css',
            'assets/css/component-library.css','assets/css/shell.css','assets/css/motion.css' );
        $handles = array( 'byl-v3-foundation','byl-v3-tokens','byl-v3-components',
            'byl-v3-component-library','byl-v3-shell','byl-v3-motion' );
        if ( array_keys( $manifest['sources'] ?? array() ) !== $names ) { return; }
        if ( ! is_string( $manifest['bundle_sha256'] ?? null )
            || ! hash_equals( $manifest['bundle_sha256'], hash_file( 'sha256', $bundle ) ) ) { return; }
        foreach ( $names as $index => $name ) {
            $path = $root . '/' . $name;
            if ( ! is_file( $path ) || ! hash_equals( $manifest['sources'][ $name ], hash_file( 'sha256', $path ) ) ) { return; }
            $handle = $handles[ $index ];
            if ( ! isset( $styles->registered[ $handle ] ) || ! wp_style_is( $handle, 'enqueued' ) ) { return; }
            $entry = $styles->registered[ $handle ];
            $expected_url = $index === 0 ? get_stylesheet_uri() : $base_url . '/' . $name;
            if ( $entry->src !== $expected_url ) { return; }
            // Inline CSS and conditional media rules could be lost: never pack those handles.
            foreach ( array( 'after','before','conditional','rtl' ) as $extra ) {
                if ( ! empty( $entry->extra[ $extra ] ) ) { return; }
            }
            if ( ! empty( $entry->args ) && $entry->args !== 'all' ) { return; }
        }
        // All checks complete before ANY URL is modified; one atomic, reversible change.
        $styles->registered['byl-v3-foundation']->src = plugins_url( $bundle_rel, __FILE__ );
        $styles->registered['byl-v3-foundation']->ver = substr( $manifest['bundle_sha256'], 0, 12 );
        for ( $i = 1; $i < count( $handles ); $i++ ) {
            $styles->registered[ $handles[ $i ] ]->src = false;
        }
    }

    public static function boot() {
        require_once __DIR__ . '/includes/pairdoll-performance.php';
        add_action( 'elementor/frontend/widget/before_render', array( 'Andy_Speed_Pairdoll', 'stable_mobile_slider' ), 10, 1 );
        add_action( 'wp_head', array( 'Andy_Speed_Pairdoll', 'mobile_hero_webp' ), 99, 0 );
        add_filter( 'wp_get_attachment_image_attributes', array( 'Andy_Speed_Pairdoll', 'logo_srcset' ), 10, 3 );
        // Only optional filters: no global rewrite of scripts, styles or fonts.
        add_filter( 'andy_core_analytics_debug_only', array( __CLASS__, 'analytics_debug_only' ), 10, 1 );
        add_filter( 'andy_speed_byl_v3_common_css_bundle', array( __CLASS__, 'byl_v3_common_css_bundle' ), 10, 1 );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'maybe_defer_newsletter' ), 1000 );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'maybe_bundle_theme_css' ), 1001 );
        if ( ! is_admin() ) { return; }
        add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
        add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( __CLASS__, 'plugin_settings_link' ) );
        add_action( 'admin_post_andy_speed_settings', array( __CLASS__, 'save' ) );
        add_action( 'admin_post_andy_speed_export', array( __CLASS__, 'export' ) );
    }

    private static function require_admin() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( '无权访问此页面。', 'andy-speed' ), '', array( 'response' => 403 ) );
        }
    }

    public static function menu() {
        add_management_page( 'Andy Speed', 'Andy Speed', 'manage_options', self::SLUG, array( __CLASS__, 'page' ) );
    }

    /**
     * One-click entry from Plugins > Andy Speed, beside Deactivate.
     * Reuses the existing Tools > Andy Speed page without creating a second screen.
     */
    public static function plugin_settings_link( $links ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return $links;
        }
        $url = admin_url( 'tools.php?page=' . self::SLUG );
        $settings_link = '<a href="' . esc_url( $url ) . '">' . esc_html__( '设置', 'andy-speed' ) . '</a>';
        array_unshift( $links, $settings_link );
        return $links;
    }

    public static function save() {
        self::require_admin();
        check_admin_referer( 'andy_speed_settings' );
        $raw = isset( $_POST['andy_speed'] ) && is_array( $_POST['andy_speed'] ) ? wp_unslash( $_POST['andy_speed'] ) : array();
        $settings = self::sanitize( $raw );
        // The emergency-off state can be restored with one Save operation.
        update_option( self::OPTION, $settings, false );
        wp_safe_redirect( admin_url( 'tools.php?page=' . self::SLUG . '&saved=1' ) );
        exit;
    }

    private static function inventory() {
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $active = (array) get_option( 'active_plugins', array() );
        $network = is_multisite() ? (array) get_site_option( 'active_sitewide_plugins', array() ) : array();
        $items = array();
        foreach ( (array) get_plugins() as $path => $meta ) {
            $items[] = array(
                'slug' => explode( '/', $path )[0],
                'name' => wp_strip_all_tags( $meta['Name'] ?? '' ),
                'version' => (string) ( $meta['Version'] ?? '' ),
                'status' => isset( $network[ $path ] ) ? 'network-active' : ( in_array( $path, $active, true ) ? 'active' : 'inactive' ),
            );
        }
        usort( $items, static function ( $a, $b ) { return strcmp( $a['slug'], $b['slug'] ); } );
        $mu = array();
        foreach ( (array) get_mu_plugins() as $path => $meta ) {
            $mu[] = array( 'slug' => basename( $path ), 'name' => wp_strip_all_tags( $meta['Name'] ?? '' ) );
        }
        return array(
            'regular' => $items,
            'must_use' => $mu,
            'dropins' => array_keys( (array) get_dropins() ),
        );
    }

    private static function identify_optimizers( $plugins ) {
        // Installed/active is only a clue; it does not prove an optimization worked.
        $groups = array(
            'page-cache' => array( 'wp-rocket', 'litespeed-cache', 'wp-super-cache', 'w3-total-cache', 'wp-fastest-cache', 'cache-enabler', 'flying-press', 'nitropack' ),
            'asset-loading' => array( 'perfmatters', 'autoptimize', 'wp-asset-clean-up', 'asset-cleanup', 'flying-press', 'wp-rocket' ),
            'object-cache' => array( 'redis-cache', 'object-cache-pro', 'memcached' ),
            'image-optimization' => array( 'shortpixel-image-optimiser', 'imagify', 'ewww-image-optimizer', 'wp-smushit', 'webp-express', 'converter-for-media' ),
        );
        $result = array();
        foreach ( $groups as $group => $slugs ) {
            foreach ( $plugins['regular'] as $plugin ) {
                if ( 'inactive' === $plugin['status'] ) { continue; }
                foreach ( $slugs as $slug ) {
                    if ( $plugin['slug'] === $slug || 0 === strpos( $plugin['slug'], $slug . '-' ) ) {
                        $result[] = array( 'category' => $group, 'plugin' => $plugin['slug'], 'confidence' => 'active only; impact unverified' );
                        break;
                    }
                }
            }
        }
        return $result;
    }

    private static function autoload_inventory() {
        global $wpdb;
        $where = "autoload IN ('yes','on','auto','auto-on')";
        // Never retrieve option values. Names with possible secrets are redacted.
        $total = $wpdb->get_var( "SELECT COALESCE(SUM(LENGTH(option_value)),0) FROM {$wpdb->options} WHERE {$where}" );
        $rows = $wpdb->get_results( "SELECT option_name, LENGTH(option_value) AS bytes FROM {$wpdb->options} WHERE {$where} ORDER BY bytes DESC LIMIT 15", ARRAY_A );
        $top = array();
        foreach ( (array) $rows as $row ) {
            $name = (string) $row['option_name'];
            if ( preg_match( '/(@|password|secret|token|credential|cookie|session|api[_-]?key|private[_-]?key|auth[_-]?key|license[_-]?key)/i', $name ) ) {
                $name = '[sensitive option name redacted]';
            }
            $top[] = array( 'name' => $name, 'bytes' => (int) $row['bytes'] );
        }
        return array( 'bytes' => (int) $total, 'top_15' => $top, 'threshold_bytes' => 800000 );
    }

    private static function probe() {
        $url = home_url( '/' );
        $out = array( 'url' => $url, 'status' => 'unavailable' );
        $start = microtime( true );
        $response = wp_safe_remote_get( $url, array(
            'timeout' => 8, 'redirection' => 0, 'sslverify' => true,
            'headers' => array( 'Accept' => 'text/html', 'User-Agent' => 'AndySpeed-Audit/0.2' ),
        ) );
        $out['request_ms'] = round( ( microtime( true ) - $start ) * 1000 );
        if ( is_wp_error( $response ) ) {
            $out['error'] = sanitize_text_field( $response->get_error_message() );
            return $out;
        }
        $out['status'] = (int) wp_remote_retrieve_response_code( $response );
        $out['headers'] = array();
        foreach ( array( 'server','cache-control','cf-cache-status','x-cache','x-litespeed-cache','age','vary','content-encoding','content-type' ) as $h ) {
            $value = wp_remote_retrieve_header( $response, $h );
            if ( is_scalar( $value ) && '' !== (string) $value ) {
                $out['headers'][ $h ] = sanitize_text_field( (string) $value );
            }
        }
        $html = wp_remote_retrieve_body( $response );
        if ( ! is_string( $html ) || '' === $html ) { return $out; }
        $out['markup_bytes'] = strlen( $html );
        $patterns = array(
            'script_elements' => '/<script\b/i',
            'style_elements' => '/<style\b/i',
            'image_elements' => '/<img\b/i',
            'lazy_loading_attributes' => '/\bloading\s*=\s*[\x27\x22]lazy[\x27\x22]/i',
            'fetchpriority_high_attributes' => '/\bfetchpriority\s*=\s*[\x27\x22]high[\x27\x22]/i',
            'preload_mentions' => '/\brel\s*=\s*[\x27\x22]preload[\x27\x22]/i',
            'defer_attributes' => '/\bdefer(?:\s|=|>)/i',
            'async_attributes' => '/\basync(?:\s|=|>)/i',
            'webp_mentions' => '/\.webp(?:\?|[\x27\x22\s>])/i',
            'avif_mentions' => '/\.avif(?:\?|[\x27\x22\s>])/i',
        );
        $counts = array();
        foreach ( $patterns as $key => $pattern ) {
            $counts[ $key ] = (int) preg_match_all( $pattern, $html );
        }
        $out['html_indicators'] = $counts;
        $out['note'] = 'Anonymous request and HTML hints only, not Lighthouse, PageSpeed or proof of a cache hit.';
        return $out;
    }

    public static function snapshot( $probe = false ) {
        global $wp_version;
        $inventory = self::inventory();
        $theme = wp_get_theme();
        $dropins = $inventory['dropins'];
        $settings = self::settings();
        return array(
            'audit' => array( 'name' => 'Andy Speed — Performance Audit', 'version' => self::VERSION, 'timestamp_utc' => gmdate( 'c' ), 'mode' => 'read-only' ),
            'site' => array( 'home' => home_url( '/' ), 'wordpress' => $wp_version, 'multisite' => is_multisite() ),
            'settings' => $settings,
            'runtime' => array(
                'php_version' => PHP_VERSION,
                'php_memory_limit' => (string) ini_get( 'memory_limit' ),
                'php_opcache_enabled_ini' => (string) ini_get( 'opcache.enable' ),
                'wp_cache_constant' => defined( 'WP_CACHE' ) ? (bool) WP_CACHE : null,
                'wp_debug_constant' => defined( 'WP_DEBUG' ) ? (bool) WP_DEBUG : null,
                'persistent_object_cache_function' => function_exists( 'wp_using_ext_object_cache' ) ? (bool) wp_using_ext_object_cache() : null,
                'advanced_cache_dropin_present' => in_array( 'advanced-cache.php', $dropins, true ),
                'object_cache_dropin_present' => in_array( 'object-cache.php', $dropins, true ),
            ),
            'theme' => array(
                'name' => $theme->get( 'Name' ),
                'stylesheet' => $theme->get_stylesheet(),
                'version' => $theme->get( 'Version' ),
                'parent_stylesheet' => $theme->parent() ? $theme->parent()->get_stylesheet() : null,
            ),
            'plugins' => $inventory,
            'optimization_indicators' => self::identify_optimizers( $inventory ),
            'autoload' => self::autoload_inventory(),
            'homepage_probe' => $probe ? self::probe() : null,
            'pairdoll_readiness' => Andy_Speed_Pairdoll::readiness(),
            'limitations' => array(
                'Audit is not a browser or Lighthouse measurement.',
                'No secret values, customer data or server config file contents are exported.',
                'Source-side feature changes require separate GitHub and Dev validation.',
            ),
        );
    }

    public static function export() {
        self::require_admin();
        check_admin_referer( 'andy_speed_export' );
        $snapshot = self::snapshot( true );
        nocache_headers();
        header( 'Content-Type: application/json; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="andy-speed-audit-' . gmdate( 'Ymd-His' ) . '.json"' );
        echo wp_json_encode( $snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
        exit;
    }

    public static function page() {
        self::require_admin();
        $settings = self::settings();
        $data = self::snapshot( false );
        $active = ! $settings['emergency_off'] && in_array( $settings['mode'], array( 'safe', 'advanced' ), true ) && $settings['analytics_diagnostics_debug_only'];
        ?>
        <div class="wrap">
            <h1>Andy Speed <small><?php echo esc_html( self::VERSION ); ?></small></h1>
            <p><strong>独立性能诊断与可选优化插件。</strong> 默认处于「仅审计」模式，并开启紧急关闭保护。插件不会擅自修改 CSS、JS、字体文件，不会自动清理缓存、创建后台任务，也不会修改付款流程或客户数据。</p>
            <?php if ( isset( $_GET['saved'] ) ) : ?><div class="notice notice-success"><p>设置已保存。</p></div><?php endif; ?>
            <h2>安全运行设置</h2>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="andy_speed_settings">
                <?php wp_nonce_field( 'andy_speed_settings' ); ?>
                <table class="form-table"><tbody>
                <tr><th>运行模式</th><td><select name="andy_speed[mode]">
                <?php foreach ( array( 'audit' => '0 级 — 仅审计（不启用优化）', 'safe' => '1 级 — 安全优化（需逐项开启）', 'advanced' => '2 级 — 预留高级模式（暂未提供高级优化）' ) as $value => $label ) : ?>
                <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['mode'], $value ); ?>><?php echo esc_html( $label ); ?></option>
                <?php endforeach; ?></select></td></tr>
                <tr><th>紧急关闭优化</th><td><label><input type="checkbox" name="andy_speed[emergency_off]" value="1" <?php checked( $settings['emergency_off'] ); ?>> 勾选后关闭全部性能优化（用于紧急回退）</label></td></tr>
                <tr><th>Andy Core 诊断功能</th><td><label><input type="checkbox" name="andy_speed[analytics_diagnostics_debug_only]" value="1" <?php checked( $settings['analytics_diagnostics_debug_only'] ); ?>> 仅在兼容的 Andy Core 已开启调试时，加载 GTM 分析诊断脚本</label>
                <p class="description">需要安装兼容的 Andy Core 版本，并启用此项、取消紧急关闭后才会生效；不会关闭 GTM、转化跟踪或其他分析业务功能。</p></td></tr>
                <tr><th>订阅脚本延迟加载</th><td><label><input type="checkbox" name="andy_speed[newsletter_defer]" value="1" <?php checked( $settings['newsletter_defer'] ); ?>> 对 Andy Core 的邮件订阅脚本启用延迟执行（不会移除脚本）</label><p class="description">保留邮件订阅表单、确认订阅及退订功能；需要兼容的 Andy Core 版本。</p></td></tr>
                <tr><th>BYL V3 样式合并</th><td><label><input type="checkbox" name="andy_speed[byl_v3_common_css_bundle]" value="1" <?php checked( $settings['byl_v3_common_css_bundle'] ); ?>> 在兼容的 BYL V3 主题上使用已验证的公共 CSS 合并文件</label><p class="description">默认关闭。需要配套的 BYL 主题版本；任何源 CSS 校验值不匹配时，自动恢复为原来的独立样式文件。</p></td></tr>
                <tr><th>PairDoll 手机轮播稳定性</th><td><label><input type="checkbox" name="andy_speed[pairdoll_mobile_slider_stability]" value="1" <?php checked( $settings['pairdoll_mobile_slider_stability'] ); ?>> 关闭 PairDoll 首页手机轮播的自动切换</label><p class="description">实验功能，默认关闭。仅匹配 PairDoll 指定域名、Woodmart 子主题、首页及手机轮播组件；保留手动切换，桌面轮播不变。须启用「安全优化」并取消「紧急关闭优化」，建议对比手机端 Lighthouse 测速及页面实际效果。</p></td></tr>
                <tr><th>PairDoll 手机首屏 WebP</th><td><label><input type="checkbox" name="andy_speed[pairdoll_mobile_hero_webp]" value="1" <?php checked( $settings['pairdoll_mobile_hero_webp'] ); ?>> 手机首页首张轮播图使用已验证的 1300px R2 WebP 图片</label><p class="description">默认关闭。仅对首页 #2779、手机轮播 #9c0199b 和首张幻灯片 #ad189c2 生效。只在手机端预加载，不删除原图。</p></td></tr>
                <tr><th>PairDoll Logo 响应式 WebP</th><td><label><input type="checkbox" name="andy_speed[pairdoll_logo_webp]" value="1" <?php checked( $settings['pairdoll_logo_webp'] ); ?>> 对原始 Logo 附件 #77910 使用已验证的 R2 WebP（300px / 500px）</label><p class="description">默认关闭。保留原始 Logo 附件及其他尺寸。启用后需检查电脑、手机端页头显示和图片清晰度。</p></td></tr>
                </tbody></table>
                <?php submit_button( '保存 Andy Speed 设置' ); ?>
            </form>
            <p><strong>Andy Core 诊断功能实际状态：</strong> <?php echo $active ? '已启用（需要 Core 兼容支持）' : '未启用'; ?></p>
            <h2>只读环境信息</h2>
            <table class="widefat striped" style="max-width:820px"><tbody>
            <tr><th>网站地址</th><td><?php echo esc_html( $data['site']['home'] ); ?></td></tr>
            <tr><th>WordPress / PHP 版本</th><td><?php echo esc_html( $data['site']['wordpress'] . ' / ' . $data['runtime']['php_version'] ); ?></td></tr>
            <tr><th>当前主题</th><td><?php echo esc_html( $data['theme']['name'] . ' ' . $data['theme']['version'] ); ?></td></tr>
            <tr><th>已安装插件数量</th><td><?php echo esc_html( count( $data['plugins']['regular'] ) ); ?></td></tr>
            <tr><th>自动加载配置大小（字节）</th><td><?php echo esc_html( number_format_i18n( $data['autoload']['bytes'] ) ); ?></td></tr>
            </tbody></table>
            <h2>导出只读诊断报告</h2>
            <p>导出当前插件、服务器环境及安全开关等信息，方便对比优化前后的状态。只有点击导出按钮时才会执行首页探测，不会修改网站内容。</p>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="andy_speed_export">
                <?php wp_nonce_field( 'andy_speed_export' ); ?>
                <?php submit_button( '导出只读 JSON 报告', 'secondary' ); ?>
            </form>
        </div>
        <?php
    }
}
Andy_Speed::boot();
}
