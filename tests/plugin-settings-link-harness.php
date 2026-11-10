<?php
/** Andy Speed plugin-list Settings action must point to the existing admin page. */
define( 'ABSPATH', '/' );
$GLOBALS['speed_test_admin'] = true;
$GLOBALS['speed_test_manage'] = true;
$GLOBALS['speed_test_filters'] = array();
function is_admin() { return $GLOBALS['speed_test_admin']; }
function plugin_basename( $filename ) { return 'andy-speed/andy-speed.php'; }
function add_action( $hook, $callback, $priority = 10, $accepted = 1 ) {}
function add_filter( $hook, $callback, $priority = 10, $accepted = 1 ) { $GLOBALS['speed_test_filters'][$hook] = $callback; }
function current_user_can( $capability ) { return 'manage_options' === $capability && $GLOBALS['speed_test_manage']; }
function admin_url( $path ) { return 'https://pairdoll.com/wp-admin/' . $path; }
function esc_url( $url ) { return htmlspecialchars( $url, ENT_QUOTES, 'UTF-8' ); }
function esc_html__( $text, $domain ) { return $text; }
require dirname( __DIR__ ) . '/andy-speed.php';
function check_link( $ok, $message ) {
    if ( ! $ok ) { fwrite( STDERR, "FAIL: $message\n" ); exit( 1 ); }
    echo "PASS: $message\n";
}
$key = 'plugin_action_links_andy-speed/andy-speed.php';
check_link( isset( $GLOBALS['speed_test_filters'][$key] ), 'exact plugin action hook registered' );
$original = array( '<a href="plugins.php?action=deactivate">Deactivate</a>' );
$result = call_user_func( $GLOBALS['speed_test_filters'][$key], $original );
check_link( count( $result ) === 2 && $result[1] === $original[0], 'Deactivate and existing links preserved' );
check_link( strpos( $result[0], '>设置</a>' ) !== false, 'Chinese settings link visible next to Deactivate' );
check_link( strpos( $result[0], '/wp-admin/tools.php?page=andy-speed' ) !== false, 'Settings goes to existing Tools page' );
$GLOBALS['speed_test_manage'] = false;
check_link( Andy_Speed::plugin_settings_link( $original ) === $original, 'settings link hidden without manage_options' );
check_link( Andy_Speed::defaults()['mode'] === 'audit' && Andy_Speed::defaults()['emergency_off'] === true, 'optimization defaults unchanged' );
check_link( Andy_Speed::VERSION === '0.3.2-dev', 'development package version bumped' );
echo "PASS: plugin-list Settings link contract\n";
