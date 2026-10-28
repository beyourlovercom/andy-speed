<?php
declare(strict_types=1);
$source = file_get_contents( dirname( __DIR__ ) . '/andy-speed.php' );
$assert = static function ( $ok, $message ) {
    if ( ! $ok ) { fwrite( STDERR, "FAIL: {$message}\n" ); exit( 1 ); }
};
foreach ( array( 'check_admin_referer', 'current_user_can', 'wp_safe_remote_get', 'wp_safe_redirect', 'update_option', 'get_plugins', 'get_mu_plugins', 'get_dropins', 'autoload', 'analytics_diagnostics_debug_only' ) as $needle ) {
    $assert( strpos( $source, $needle ) !== false, 'Missing audit/safety contract: ' . $needle );
}
foreach ( array( 'wp_dequeue_script', 'wp_dequeue_style', 'script_loader_tag', 'style_loader_tag', 'template_redirect', 'wp_head', 'wp_footer', 'register_activation_hook', 'wp_schedule_event', 'flush_rewrite_rules' ) as $forbidden ) {
    $assert( strpos( $source, $forbidden ) === false, 'Unsafe global frontend hook: ' . $forbidden );
}
echo "PASS Andy Speed audit-only default and no global asset rewriting\n";
