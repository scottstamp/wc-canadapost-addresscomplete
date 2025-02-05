<?php
/*
Plugin Name: Canada Post AddressComplete for WooCommerce (free)
Plugin URI: https://github.com/scottstamp/wc-canadapost-addresscomplete
Description: Address validation for WooCommerce provided by <a href="https://www.canadapost.ca/pca/">Canada Post's AddressComplete</a> service
Version: 1.0.0
Author: Desaulniers Simard
Author URI: https://desaulniers-simard.com/
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: wc-canadapost-addresscomplete
Domain Path: /languages
*/

namespace DesaulniersSimard\WooCommerce\AddressComplete;

/**
 * Canada Post API JS URI. No way to get current version programmatically right now, apparently.
 */
const CP_JS_URI  = 'https://xksc.org/postes/addresscomplete-2.30.min.js';

/**
 * Canada Post API CSS URI. Makes things look consistent.
 */
const CP_CSS_URI = 'https://xksc.org/postes/addresscomplete-2.30.min.css';

add_action( 'wp_enqueue_scripts', 'DesaulniersSimard\WooCommerce\AddressComplete\scripts');

/**
 * Embedding our scripts and JS data
 */
function scripts() {
	if ( function_exists( 'wc_get_page_id' ) && is_page( wc_get_page_id( 'checkout' ) ) && get_cp_api_key() ) {
		wp_enqueue_style( 'cp-addresscomplete', CP_CSS_URI . '?key=' . get_cp_api_key() );
		wp_enqueue_script( 'cp-addresscomplete', CP_JS_URI . '?key=' . get_cp_api_key() );
		wp_enqueue_script( 'wc-canadapost-addresscomplete', plugin_dir_url( __FILE__ ) . 'js/checkout.js', array( 'cp-addresscomplete' ) );
		wp_localize_script( 'wc-canadapost-addresscomplete', 'wcCanadaPostOptions', get_api_options() );
	}
}

add_filter( 'woocommerce_shipping_settings', 'DesaulniersSimard\WooCommerce\AddressComplete\settings' );

/**
 * Add our settings to the WooCommerce's Settings > Shipping > Shipping Options
 *
 * @param array $shipping_settings WooCommerce shipping settings
 *
 * @return array Augmented shipping settings, with our field added
 */
function settings( $shipping_settings ) {
	if ( !current_user_can( 'manage_woocommerce' ) ) {
		return $shipping_settings;
	}
	$additional_settings = array(
		array(
			'id' => 'address_validation',
			'title' => __( 'Address Validation', 'wc-canadapost-addresscomplete' ),
			'type' => 'title',
		),
		array(
			'id' => 'wc_cp_addresscomplete_api_key',
			'title' => __( 'AddressComplete API Key', 'wc-canadapost-addresscomplete' ),
			'desc_tip' => __( "Enter the API key you generated at Canada Post's AddressComplete site.", 'wc-canadapost-addresscomplete' ),
			'placeholder' => 'AA00-BB11-CC22-DD33',
			'type' => 'text',
			'class' => 'input-text regular-input',
		),
		array(
			'type' => 'sectionend',
			'id' => 'address_validation',
		),
	);
	return array_merge( $shipping_settings, $additional_settings );
}

add_filter( 'woocommerce_admin_settings_sanitize_option_wc_cp_addresscomplete_api_key', 'DesaulniersSimard\WooCommerce\AddressComplete\sanitize_key', 10, 1 );

function sanitize_key( $value ) {
	return sanitize_title( $value, '', 'save' );
}

add_action( 'plugins_loaded', 'DesaulniersSimard\WooCommerce\AddressComplete\languages' );

function languages() {
	load_plugin_textdomain( 'wc-canadapost-addresscomplete', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

/**
 * @return array Options for instantiating Canada Post' JS API
 */
function get_api_options() {
	return array(
		'culture' => get_locale(),
		'key' => get_cp_api_key(),
	);
}

/**
 * Get the API key we saved earlier
 * @return mixed|void
 */
function get_cp_api_key() {
	return wp_kses( get_option( 'wc_cp_addresscomplete_api_key'), array() );
}