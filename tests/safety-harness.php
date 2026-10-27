<?php
declare(strict_types=1);
define( 'ABSPATH', __DIR__ );
$GLOBALS['speed_options'] = array();
$GLOBALS['speed_filters'] = array();
function add_filter( $hook, $fn, $priority = 10, $accepted = 1 ) { $GLOBALS['speed_filters'][ $hook ] = $fn; }
function is_admin() { return false; }
function get_option( $key, $default = false ) { return $GLOBALS['speed_options'][ $key ] ?? $default; }
function wp_parse_args( $args, $defaults ) { return array_merge( $defaults, (array) $args ); }
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) ); }
require_once dirname( __DIR__ ) . '/andy-speed.php';
function check_speed( $condition, $message ) {
    if ( ! $condition ) { fwrite( STDERR, "FAIL: {$message}\n" ); exit( 1 ); }
}
check_speed( isset( $GLOBALS['speed_filters']['andy_core_analytics_debug_only'] ), 'Core optional hook registered' );
check_speed( Andy_Speed::analytics_debug_only( false ) === false, 'default OFF' );
check_speed( Andy_Speed::settings()['mode'] === 'audit', 'default audit' );
check_speed( Andy_Speed::settings()['emergency_off'] === true, 'default emergency OFF' );
check_speed( Andy_Speed::sanitize( array( 'mode' => 'bogus', 'emergency_off' => 0 ) )['mode'] === 'audit', 'invalid mode fail closed' );
$GLOBALS['speed_options'][ Andy_Speed::OPTION ] = array( 'mode' => 'safe', 'emergency_off' => 1, 'analytics_diagnostics_debug_only' => 1 );
check_speed( Andy_Speed::analytics_debug_only( false ) === false, 'kill switch always wins' );
$GLOBALS['speed_options'][ Andy_Speed::OPTION ]['emergency_off'] = 0;
check_speed( Andy_Speed::analytics_debug_only( false ) === true, 'explicit opt-in works' );
$GLOBALS['speed_options'][ Andy_Speed::OPTION ]['analytics_diagnostics_debug_only'] = 0;
check_speed( Andy_Speed::analytics_debug_only( false ) === false, 'per-feature OFF works' );
check_speed( Andy_Speed::analytics_debug_only( true ) === true, 'never overrides another enabled gate' );
$GLOBALS['speed_options'][ Andy_Speed::OPTION ]['mode'] = 'audit';
$GLOBALS['speed_options'][ Andy_Speed::OPTION ]['analytics_diagnostics_debug_only'] = 1;
check_speed( Andy_Speed::analytics_debug_only( false ) === false, 'audit mode never optimizes' );
echo "PASS Andy Speed safe defaults, opt-in, kill switch, and reversible filter\n";
