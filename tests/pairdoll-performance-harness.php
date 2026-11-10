<?php
/** PairDoll performance safety contract: no image format/quality changes. */
define( 'ABSPATH', '/' );
$GLOBALS['pd_state'] = array(
    'host' => 'pairdoll.com', 'theme' => 'woodmart-child', 'admin' => false,
    'home' => true, 'mode' => 'safe', 'off' => false, 'enabled' => true,
    'preload' => true, 'queried_id' => 2779, 'guest' => '1', 'guest_optm' => '1',
);
function home_url( $path = '/' ) { return 'https://' . $GLOBALS['pd_state']['host'] . $path; }
function get_stylesheet() { return $GLOBALS['pd_state']['theme']; }
function wp_parse_url( $url, $component ) { return parse_url( $url, $component ); }
function is_admin() { return $GLOBALS['pd_state']['admin']; }
function is_front_page() { return $GLOBALS['pd_state']['home']; }
function get_queried_object_id() { return $GLOBALS['pd_state']['queried_id']; }
function esc_url( $url ) { return $url; }
function get_option( $key, $fallback = null ) {
    return $GLOBALS['pd_state'][ $key === 'litespeed.conf.guest' ? 'guest' : 'guest_optm' ] ?? $fallback;
}
final class Andy_Speed {
    public static function settings() {
        return array(
            'mode' => $GLOBALS['pd_state']['mode'],
            'emergency_off' => $GLOBALS['pd_state']['off'],
            'pairdoll_mobile_slider_stability' => $GLOBALS['pd_state']['enabled'],
            'pairdoll_mobile_hero_preload' => $GLOBALS['pd_state']['preload'],
            // Legacy options may remain saved after upgrades; they cannot be used.
            'pairdoll_mobile_hero_webp' => true,
            'pairdoll_logo_webp' => true,
        );
    }
}
final class Fake_Slider {
    public $autoplay = 'yes';
    public $writes = 0;
    private $id;
    private $name;
    public function __construct( $id, $name = 'slides' ) { $this->id = $id; $this->name = $name; }
    public function get_name() { return $this->name; }
    public function get_id() { return $this->id; }
    public function set_settings( $key, $value ) {
        $this->writes++;
        if ( 'autoplay' === $key ) { $this->autoplay = $value; }
    }
}
require dirname( __DIR__ ) . '/includes/pairdoll-performance.php';
function check( $condition, $label ) {
    if ( ! $condition ) { fwrite( STDERR, 'FAIL: ' . $label . "\n" ); exit( 1 ); }
    echo "PASS: $label\n";
}
$mobile = new Fake_Slider( '9c0199b' );
Andy_Speed_Pairdoll::stable_mobile_slider( $mobile );
check( 'no' === $mobile->autoplay && 1 === $mobile->writes, 'exact mobile slider autoplay disabled' );
$desktop = new Fake_Slider( '6287346' );
Andy_Speed_Pairdoll::stable_mobile_slider( $desktop );
check( 'yes' === $desktop->autoplay && 0 === $desktop->writes, 'desktop slider unchanged' );
$other = new Fake_Slider( '9c0199b', 'image-carousel' );
Andy_Speed_Pairdoll::stable_mobile_slider( $other );
check( 0 === $other->writes, 'unrelated widget unchanged' );
$audit = Andy_Speed_Pairdoll::readiness();
check( true === $audit['guest_mode_enabled'] && true === $audit['guest_optimization_enabled'], 'guest flags read-only' );
ob_start();
Andy_Speed_Pairdoll::original_mobile_hero_preload();
$markup = ob_get_clean();
$original_url = 'https://img.pairdoll.com/wp-content/uploads/2026/11/Photo-album-banner-correct-size.jpg';
check( 1 === substr_count( $markup, 'rel="preload"' )
    && false !== strpos( $markup, 'href="' . $original_url . '"' )
    && false !== strpos( $markup, 'media="(max-width: 767px)"' )
    && false !== strpos( $markup, 'fetchpriority="high"' ), 'preload points to exact original JPEG' );
check( false === strpos( $markup, '<style' )
    && false === strpos( $markup, '.webp' )
    && false === strpos( $markup, '<img' ), 'no image format change, CSS replacement or srcset override' );
check( ! method_exists( 'Andy_Speed_Pairdoll', 'logo_srcset' )
    && ! method_exists( 'Andy_Speed_Pairdoll', 'mobile_hero_webp' ),
    'legacy WebP image replacement methods removed' );
$src = file_get_contents( dirname( __DIR__ ) . '/includes/pairdoll-performance.php' );
foreach ( array( 'setImageFormat', 'imagewebp', 'Imagick', 'wp_update_attachment_metadata',
    'PAIRDOLL-LOGO-300.webp', 'Photo-album-banner-1300.webp' ) as $forbidden ) {
    check( false === strpos( $src, $forbidden ), 'no image re-encoding or derived-image references' );
}
foreach ( array(
    array( 'host', 'another.example' ), array( 'theme', 'other-child' ),
    array( 'admin', true ), array( 'home', false ), array( 'mode', 'audit' ),
    array( 'off', true ), array( 'queried_id', 42 ), array( 'preload', false ),
) as $case ) {
    $key = $case[0];
    $old = $GLOBALS['pd_state'][$key];
    $GLOBALS['pd_state'][$key] = $case[1];
    ob_start();
    Andy_Speed_Pairdoll::original_mobile_hero_preload();
    $out = ob_get_clean();
    check( '' === $out, 'preload fail-closed: ' . $key );
    $GLOBALS['pd_state'][$key] = $old;
}
$GLOBALS['pd_state']['preload'] = false;
ob_start();
Andy_Speed_Pairdoll::original_mobile_hero_preload();
check( '' === ob_get_clean(), 'old saved WebP options have no effect' );
echo "PASS: PairDoll original-image preservation harness\n";
