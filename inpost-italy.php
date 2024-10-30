<?php
/*
	Plugin Name: InPost Italy
	Plugin URI: https://inpost.it/soluzioni-le-aziende
	Description: Invia, crea e stampa Spedizioni InPost attraverso il nostro Plugin ufficiale.
	Version: 1.1.8
	Author: InPost
	Author URI: https://inpost.it/
	License: GPLv3
	License URI: https://www.gnu.org/licenses/gpl-3.0.html
	Text Domain: inpost-italy
	Domain Path: /languages/
	Tested up to: 6.6

	InPost Italy for Woocommerce is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 2 of the License, or
	any later version.

	InPost Italy for Woocommerce is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with InPost Italy for Woocommerce. If not, see https://www.gnu.org/licenses/gpl-3.0.html.

*/
if ( ! defined('ABSPATH') ) {
    exit;
}

use InspireLabs\InpostItaly\admin\EasyPack_Italy_Shipment_Manager;
use InspireLabs\InpostItaly\EasyPack_Italy;
use InspireLabs\InpostItaly\EasyPack_Italy_AJAX;
use InspireLabs\InpostItaly\EasyPack_Italy_API;
use InspireLabs\InpostItaly\EasyPack_Italy_Helper;

define( 'INPOST_ITALY_PLUGIN_FILE', __FILE__ );
define( 'INPOST_ITALY_PLUGIN_DIR', __DIR__ );
define( 'INPOST_ITALY_PLUGIN_VERSION', '1.1.8' );


add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'inpost_italy_links_filter' );
function inpost_italy_links_filter( $links )
{

    $plugin_links = array(
        '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=easypack_italy' ) . '">' . esc_html__( 'Settings', 'inpost-italy' ) . '</a>',
        '<a href="https://inpost.it/contatti-e-assistenza">' . esc_html__( 'Documentation', 'inpost-italy' ) . '</a>',
        '<a href="/wp-admin/admin.php?page=wc-settings&tab=easypack_italy&section=help">' . esc_html__( 'Support', 'inpost-italy' ) . '</a>',
    );

    return array_merge( $plugin_links, $links );
}

require_once __DIR__ . "/vendor/autoload.php";


/**
 * @return EasyPack_Italy
 */
function inpost_italy()
{
    return EasyPack_Italy::Easypack_Italy();
}

/**
 * @return EasyPack_Italy_API
 */
function inpost_italy_api()
{
    return EasyPack_Italy_API::EasyPack_Italy_API();
}

/**
 * @return EasyPack_Italy_Helper
 */
function inpost_italy_helper()
{
    return EasyPack_Italy_Helper::EasyPack_Italy_Helper();
}

inpost_italy_helper();
EasyPack_Italy_AJAX::init();
EasyPack_Italy_Shipment_Manager::init();


if (in_array('woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    $_GLOBALS['EasyPack_Italy'] = inpost_italy();

    add_action( 'before_woocommerce_init', function() {
        if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
        }
    } );

}

// redirect to the settings page after activation if API key or token is empty
register_activation_hook( INPOST_ITALY_PLUGIN_FILE, function () {
    add_option( 'inpost_italy_do_activation_redirect', true );
} );

add_action( 'admin_init', function () {
    if ( get_option( 'inpost_italy_do_activation_redirect', false ) ) {
        delete_option( 'inpost_italy_do_activation_redirect' );
        if ( in_array('woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
            if( empty( get_option( 'easypack_organization_id_italy', false ) )
                || empty( get_option( 'easypack_token_italy', false ) )
            ) {
                wp_safe_redirect(esc_url(admin_url() . 'admin.php?page=wc-settings&tab=easypack_italy'));
            }
        }
    }
} );


register_deactivation_hook( __FILE__, 'inpost_italy_clear_wc_shipping_cache' );
function inpost_italy_clear_wc_shipping_cache() {
    if ( in_array('woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
        \WC_Cache_Helper::get_transient_version( 'shipping', true );
    }
}
