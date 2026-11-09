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
