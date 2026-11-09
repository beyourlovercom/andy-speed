<?php
/** Standalone PairDoll performance safety test (does not require WordPress). */
define( 'ABSPATH', '/' );
$GLOBALS['pd_state'] = array( 'host' => 'pairdoll.com', 'theme' => 'woodmart-child', 'admin' => false, 'home' => true,
    'mode' => 'safe', 'off' => false, 'enabled' => true, 'guest' => '1', 'guest_optm' => '1' );
function home_url( $path = '/' ) { return 'https://' . $GLOBALS['pd_state']['host'] . $path; }
function get_stylesheet() { return $GLOBALS['pd_state']['theme']; }
function wp_parse_url( $url, $component ) { return parse_url( $url, $component ); }
function is_admin() { return $GLOBALS['pd_state']['admin']; }
function is_front_page() { return $GLOBALS['pd_state']['home']; }
function get_option( $key, $fallback = null ) {
    return $GLOBALS['pd_state'][ $key === 'litespeed.conf.guest' ? 'guest' : 'guest_optm' ] ?? $fallback;
}
final class Andy_Speed {
    public static function settings() {
        return array( 'mode' => $GLOBALS['pd_state']['mode'], 'emergency_off' => $GLOBALS['pd_state']['off'],
            'pairdoll_mobile_slider_stability' => $GLOBALS['pd_state']['enabled'] );
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
echo "PASS: PairDoll safety harness complete\n";
