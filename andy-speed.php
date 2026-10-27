<?php
/**
 * Plugin Name: Andy Speed
 * Description: Independent read-only performance audit and opt-in, reversible WordPress integration gates.
 * Version: 0.2.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: beyourlovercom
 * License: GPL-2.0-or-later
 * Text Domain: andy-speed
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Andy_Speed' ) ) {
final class Andy_Speed {
    const VERSION = '0.2.0';
    const OPTION = 'andy_speed_settings_v1';
    const SLUG = 'andy-speed';

    public static function defaults() {
        return array(
            'mode' => 'audit',
            'emergency_off' => true,
            'analytics_diagnostics_debug_only' => false,
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

    public static function boot() {
        // Only this optional filter is active on frontend requests.
        add_filter( 'andy_core_analytics_debug_only', array( __CLASS__, 'analytics_debug_only' ), 10, 1 );
        if ( ! is_admin() ) { return; }
        add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
        add_action( 'admin_post_andy_speed_settings', array( __CLASS__, 'save' ) );
        add_action( 'admin_post_andy_speed_export', array( __CLASS__, 'export' ) );
    }

    private static function require_admin() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Access denied.', 'andy-speed' ), '', array( 'response' => 403 ) );
        }
    }

    public static function menu() {
        add_management_page( 'Andy Speed', 'Andy Speed', 'manage_options', self::SLUG, array( __CLASS__, 'page' ) );
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
            <p><strong>Independent opt-in performance gate.</strong> Default is Audit Only, with emergency OFF. No rewriting of CSS/JS/font files, cache flush, background jobs, payments, or customer data.</p>
            <?php if ( isset( $_GET['saved'] ) ) : ?><div class="notice notice-success"><p>Settings saved.</p></div><?php endif; ?>
            <h2>Safe runtime settings</h2>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="andy_speed_settings">
                <?php wp_nonce_field( 'andy_speed_settings' ); ?>
                <table class="form-table"><tbody>
                <tr><th>Execution mode</th><td><select name="andy_speed[mode]">
                <?php foreach ( array( 'audit' => 'Level 0 — Audit only', 'safe' => 'Level 1 — Safe opt-in', 'advanced' => 'Level 2 — Reserved; no advanced optimizer yet' ) as $value => $label ) : ?>
                <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['mode'], $value ); ?>><?php echo esc_html( $label ); ?></option>
                <?php endforeach; ?></select></td></tr>
                <tr><th>Emergency OFF</th><td><label><input type="checkbox" name="andy_speed[emergency_off]" value="1" <?php checked( $settings['emergency_off'] ); ?>> Keep all performance modifications OFF</label></td></tr>
                <tr><th>Core diagnostics policy</th><td><label><input type="checkbox" name="andy_speed[analytics_diagnostics_debug_only]" value="1" <?php checked( $settings['analytics_diagnostics_debug_only'] ); ?>> On compatible Andy Core, load Analytics GTM inspector script only when its debug setting is ON</label>
                <p class="description">Effective only if the source-side Core compatibility PR is installed and this switch is enabled with emergency OFF cleared. This does not disable GTM, tracking or analytics business logic.</p></td></tr>
                </tbody></table>
                <?php submit_button( 'Save Andy Speed settings' ); ?>
            </form>
            <p><strong>Effective Core diagnostics opt-in:</strong> <?php echo $active ? 'ON (requires Core integration)' : 'OFF'; ?></p>
            <h2>Read-only environment</h2>
            <table class="widefat striped" style="max-width:820px"><tbody>
            <tr><th>Site</th><td><?php echo esc_html( $data['site']['home'] ); ?></td></tr>
            <tr><th>WordPress / PHP</th><td><?php echo esc_html( $data['site']['wordpress'] . ' / ' . $data['runtime']['php_version'] ); ?></td></tr>
            <tr><th>Theme</th><td><?php echo esc_html( $data['theme']['name'] . ' ' . $data['theme']['version'] ); ?></td></tr>
            <tr><th>Registered plugins</th><td><?php echo esc_html( count( $data['plugins']['regular'] ) ); ?></td></tr>
            <tr><th>Autoloaded option bytes</th><td><?php echo esc_html( number_format_i18n( $data['autoload']['bytes'] ) ); ?></td></tr>
            </tbody></table>
            <h2>Export private comparison snapshot</h2>
            <p>Same operational inventory fields as the GTranslater audit, with mode and safety settings added. Probe runs only on explicit export.</p>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="andy_speed_export">
                <?php wp_nonce_field( 'andy_speed_export' ); ?>
                <?php submit_button( 'Export read-only JSON snapshot', 'secondary' ); ?>
            </form>
        </div>
        <?php
    }
}
Andy_Speed::boot();
}
