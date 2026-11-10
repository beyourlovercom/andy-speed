<?php
/**
 * PairDoll-only, opt-in Elementor mobile hero stability gate.
 *
 * No global asset dequeueing, caching, WooCommerce mutations, editor-data writes,
 * or device-specific server caching. Disabled by default and by Emergency OFF.
 */
defined( 'ABSPATH' ) || exit;

final class Andy_Speed_Pairdoll {
    const HOME_SLIDER_ID = '9c0199b';

    public static function is_pairdoll() {
        if ( ! function_exists( 'home_url' ) || ! function_exists( 'get_stylesheet' ) ) {
            return false;
        }
        return 'pairdoll.com' === strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) )
            && 'woodmart-child' === get_stylesheet();
    }

    public static function stable_mobile_slider( $widget ) {
        if ( ! self::is_pairdoll() || ( function_exists( 'is_admin' ) && is_admin() )
            || ! function_exists( 'is_front_page' ) || ! is_front_page() ) {
            return;
        }
        $settings = Andy_Speed::settings();
        if ( ! empty( $settings['emergency_off'] )
            || ! in_array( $settings['mode'], array( 'safe', 'advanced' ), true )
            || empty( $settings['pairdoll_mobile_slider_stability'] ) ) {
            return;
        }
        if ( ! is_object( $widget ) || ! method_exists( $widget, 'get_name' )
            || ! method_exists( $widget, 'get_id' ) || ! method_exists( $widget, 'set_settings' )
            || 'slides' !== $widget->get_name() || self::HOME_SLIDER_ID !== $widget->get_id() ) {
            return;
        }
        // This Elementor widget is hidden on tablet/desktop in PairDoll's frozen
        // homepage markup. Keep it visible and manually navigable on mobile,
        // but avoid a timed slide change during LCP measurement and first reading.
        // No wp_is_mobile() branching: the same cacheable HTML goes to all users.
        $widget->set_settings( 'autoplay', 'no' );
    }


    const MOBILE_HERO_SLIDE_ID = 'ad189c2';
    const MOBILE_HERO_IMAGE = 'https://img.pairdoll.com/wp-content/uploads/2026/11/Photo-album-banner-1300.webp';
    const BRAND_LOGO_ATTACHMENT_ID = 77910;
    const BRAND_LOGO_300 = 'https://img.pairdoll.com/wp-content/uploads/2026/11/PAIRDOLL-LOGO-300.webp';
    const BRAND_LOGO_500 = 'https://img.pairdoll.com/wp-content/uploads/2026/11/PAIRDOLL-LOGO-500.webp';

    private static function frontend_enabled( $key ) {
        if ( ! self::is_pairdoll() || ( function_exists( 'is_admin' ) && is_admin() )
            || ! function_exists( 'is_front_page' ) || ! is_front_page() ) {
            return false;
        }
        $settings = Andy_Speed::settings();
        return empty( $settings['emergency_off'] )
            && in_array( $settings['mode'], array( 'safe', 'advanced' ), true )
            && ! empty( $settings[ $key ] );
    }

    /**
     * PairDoll mobile homepage's first Elementor slide only. This explicit
     * preload and CSS override leave cached HTML, page JSON and other sliders alone.
     */
    public static function mobile_hero_webp() {
        if ( ! self::frontend_enabled( 'pairdoll_mobile_hero_webp' ) ) { return; }
        if ( ! function_exists( 'get_queried_object_id' ) || 2779 !== (int) get_queried_object_id() ) { return; }
        $url = esc_url( self::MOBILE_HERO_IMAGE );
        if ( '' === $url ) { return; }
        echo '<link rel="preload" as="image" media="(max-width: 767px)" fetchpriority="high" href="' . $url . '">' . "\n";
        echo '<style id="andy-speed-pairdoll-mobile-hero">@media(max-width:767px){body.home .elementor-2779 .elementor-element.elementor-element-' . esc_attr( self::HOME_SLIDER_ID )
            . ' .elementor-repeater-item-' . esc_attr( self::MOBILE_HERO_SLIDE_ID )
            . ' .swiper-slide-bg{background-image:url("' . $url . '")!important;}}</style>' . "\n";
    }

    /**
     * Apply only to full-size PairDoll home header logo attachment. Original
     * remains in WordPress; modern variants preserve aspect and transparency.
     */
    public static function logo_srcset( $attr, $attachment, $size ) {
        if ( ! function_exists( 'get_queried_object_id' ) || 2779 !== (int) get_queried_object_id() ) { return $attr; }
        if ( ! self::frontend_enabled( 'pairdoll_logo_webp' ) || ! is_object( $attachment )
            || self::BRAND_LOGO_ATTACHMENT_ID !== (int) ( $attachment->ID ?? 0 )
            || 'full' !== $size || ! is_array( $attr ) ) {
            return $attr;
        }
        $small = esc_url( self::BRAND_LOGO_300 );
        $large = esc_url( self::BRAND_LOGO_500 );
        $attr['src'] = $large;
        $attr['srcset'] = $small . ' 300w, ' . $large . ' 500w';
        $attr['sizes'] = '(max-width: 767px) 180px, 221px';
        $attr['width'] = '500';
        $attr['height'] = '250';
        return $attr;
    }

    public static function readiness() {
        if ( ! self::is_pairdoll() ) {
            return array( 'applicable' => false );
        }
        // Read-only booleans: no access keys, customer data, cookies or option dumps.
        return array(
            'applicable' => true,
            'guest_mode_enabled' => '1' === (string) get_option( 'litespeed.conf.guest', '0' ),
            'guest_optimization_enabled' => '1' === (string) get_option( 'litespeed.conf.guest_optm', '0' ),
            'mobile_hero_widget_id' => self::HOME_SLIDER_ID,
            'note' => 'LiteSpeed guest reload and mobile autoplay need independent browser A/B measurements. No cache settings are changed by Andy Speed.',
        );
    }
}
