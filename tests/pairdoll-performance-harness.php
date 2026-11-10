<?php
/** Standalone PairDoll performance safety test (does not require WordPress). */
define( 'ABSPATH', '/' );
$GLOBALS['pd_state'] = array( 'host' => 'pairdoll.com', 'theme' => 'woodmart-child', 'admin' => false, 'home' => true,
    'mode' => 'safe', 'off' => false, 'enabled' => true, 'hero' => true, 'logo' => true, 'queried_id' => 2779, 'guest' => '1', 'guest_optm' => '1' );
function home_url( $path = '/' ) { return 'https://' . $GLOBALS['pd_state']['host'] . $path; }
function get_stylesheet() { return $GLOBALS['pd_state']['theme']; }
function wp_parse_url( $url, $component ) { return parse_url( $url, $component ); }
function is_admin() { return $GLOBALS['pd_state']['admin']; }
function is_front_page() { return $GLOBALS['pd_state']['home']; }
function get_queried_object_id() { return $GLOBALS['pd_state']['queried_id']; }
function esc_url( $url ) { return $url; }
function esc_attr( $value ) { return htmlspecialchars( $value, ENT_QUOTES ); }
function get_option( $key, $fallback = null ) {
    return $GLOBALS['pd_state'][ $key === 'litespeed.conf.guest' ? 'guest' : 'guest_optm' ] ?? $fallback;
}
final class Andy_Speed {
    public static function settings() {
        return array( 'mode' => $GLOBALS['pd_state']['mode'], 'emergency_off' => $GLOBALS['pd_state']['off'],
            'pairdoll_mobile_slider_stability' => $GLOBALS['pd_state']['enabled'],
            'pairdoll_mobile_hero_webp' => $GLOBALS['pd_state']['hero'],
            'pairdoll_logo_webp' => $GLOBALS['pd_state']['logo'] );
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
check( 'no' === $mobile->autoplay && 1 === $mobile->writes, 'mobile autoplay disabled' );
$desktop = new Fake_Slider( '6287346' );
Andy_Speed_Pairdoll::stable_mobile_slider( $desktop );
check( 'yes' === $desktop->autoplay && 0 === $desktop->writes, 'desktop unchanged' );
foreach ( array(
    array( 'host', 'another.example' ), array( 'theme', 'other-child' ), array( 'admin', true ),
    array( 'home', false ), array( 'mode', 'audit' ), array( 'off', true ), array( 'enabled', false ),
) as $case ) {
    $key = $case[0];
    $original = $GLOBALS['pd_state'][$key];
    $GLOBALS['pd_state'][$key] = $case[1];
    $test = new Fake_Slider( '9c0199b' );
    Andy_Speed_Pairdoll::stable_mobile_slider( $test );
    check( 'yes' === $test->autoplay && 0 === $test->writes, 'fail closed: ' . $key );
    $GLOBALS['pd_state'][$key] = $original;
}
$other = new Fake_Slider( '9c0199b', 'image-carousel' );
Andy_Speed_Pairdoll::stable_mobile_slider( $other );
check( 0 === $other->writes, 'other widget unchanged' );
$audit = Andy_Speed_Pairdoll::readiness();
check( true === $audit['guest_mode_enabled'] && true === $audit['guest_optimization_enabled'], 'read-only guest flags' );

// PairDoll R3: exactly one mobile hero CSS+preload on approved homepage.
ob_start();
Andy_Speed_Pairdoll::mobile_hero_webp();
$markup = ob_get_clean();
check( 1 === substr_count( $markup, 'rel="preload"' )
    && false !== strpos( $markup, 'Photo-album-banner-1300.webp' )
    && false !== strpos( $markup, 'ad189c2' )
    && false !== strpos( $markup, 'max-width:767px' ), 'mobile hero preload + scoped CSS' );
$logo = (object) array( 'ID' => 77910 );
$normal = array( 'src' => 'https://img.pairdoll.com/wp-content/uploads/2026/11/PAIRDOLL-LOGO.png',
    'width' => 800, 'height' => 400, 'class' => 'attachment-full size-full' );
$adjusted = Andy_Speed_Pairdoll::logo_srcset( $normal, $logo, 'full' );
check( false !== strpos( $adjusted['src'], 'PAIRDOLL-LOGO-500.webp' )
    && false !== strpos( $adjusted['srcset'], 'PAIRDOLL-LOGO-300.webp' )
    && 500 === (int) $adjusted['width'], 'logo responsive WebP from exact source attachment' );
check( $normal === Andy_Speed_Pairdoll::logo_srcset( $normal, (object) array( 'ID' => 77911 ), 'full' ),
    'unrelated images left unchanged' );
check( $normal === Andy_Speed_Pairdoll::logo_srcset( $normal, $logo, 'thumbnail' ),
    'other logo sizes left unchanged' );
foreach( array( array( 'off', true ), array( 'mode', 'audit' ), array( 'host', 'not-pairdoll.com' ),
    array( 'theme', 'not-woodmart-child' ), array( 'home', false ), array( 'queried_id', 42 ) ) as $case ) {
    $key = $case[0];
    $prior = $GLOBALS['pd_state'][$key];
    $GLOBALS['pd_state'][$key] = $case[1];
    ob_start();
    Andy_Speed_Pairdoll::mobile_hero_webp();
    $output = ob_get_clean();
    check( '' === $output, 'hero fail closed: ' . $key );
    check( $normal === Andy_Speed_Pairdoll::logo_srcset( $normal, $logo, 'full' ),
        'logo fail closed: ' . $key );
    $GLOBALS['pd_state'][$key] = $prior;
}
$GLOBALS['pd_state']['hero'] = false;
$GLOBALS['pd_state']['logo'] = false;
ob_start();
Andy_Speed_Pairdoll::mobile_hero_webp();
$disabled_output = ob_get_clean();
check( '' === $disabled_output && $normal === Andy_Speed_Pairdoll::logo_srcset( $normal, $logo, 'full' ),
    'R3 independent options default-OFF behavior' );

echo "PASS: PairDoll safety harness complete\n";
