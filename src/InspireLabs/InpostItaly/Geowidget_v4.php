<?php

namespace InspireLabs\InpostItaly;

use InspireLabs\InpostItaly\EasyPack_Italy;

class Geowidget_v4 {

    // we use local files with changes: OSM tiles except Google Maps

    private $assets_js_uri;

    public function __construct() {
        $this->assets_js_uri = EasyPack_Italy::get_assets_js_uri();
    }

    /**
     * @return string
     */
    private function get_geowidget_js_src(): string {
        return inpost_italy()->getPluginJs() . 'sdk-for-javascript.js';
    }

    /**
     * @return string
     */
    private function get_geowidget_css_src(): string {
        return inpost_italy()->getPluginCss() . 'easypack.css';
    }

    public function init_assets() {
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ], 76 );
        add_action( 'wp_enqueue_scripts', [ $this, 'geowidget_css_lib_code_front' ], 76 );
    }


    // lib code for map on frontend
    function geowidget_css_lib_code_front() {
        // only on checkout page
        if( is_checkout() ) {
            wp_enqueue_style('geowidget-css', $this->get_geowidget_css_src(), [], INPOST_ITALY_PLUGIN_VERSION );
            wp_enqueue_style( 'easypack-front-font-awesome', inpost_italy()->getPluginCss() . 'font-awesome.min.css', [], INPOST_ITALY_PLUGIN_VERSION );
            wp_enqueue_script('easypack-it-sdk', $this->get_geowidget_js_src(), [],INPOST_ITALY_PLUGIN_VERSION, true );
        }
    }


    public function enqueue_admin_scripts() {

        $current_screen = get_current_screen();
        // only on settings page
        if ( is_a( $current_screen, 'WP_Screen' ) && 'woocommerce_page_wc-settings' === $current_screen->id ) {
            if( isset( $_GET['tab'] ) && $_GET['tab'] == 'easypack_italy') {
                wp_enqueue_style('geowidget-css', $this->get_geowidget_css_src(), [], INPOST_ITALY_PLUGIN_VERSION );

                wp_enqueue_script('easypack-it-sdk', $this->get_geowidget_js_src(),
                    [],
                    INPOST_ITALY_PLUGIN_VERSION,
                    array('in_footer' => true)
                );
                wp_enqueue_script('easypack-it-checkout-map', $this->assets_js_uri . 'lockers-map.js',
                    array('jquery'),
                    INPOST_ITALY_PLUGIN_VERSION,
                    array('in_footer' => true)
                );

                // color picker on settings page
                wp_enqueue_style( 'wp-color-picker' );
                wp_enqueue_script( 'wp-color-picker' );
                wp_enqueue_script('easypack-it-color-picker', $this->assets_js_uri . 'color-picker.js',
                    array('jquery'),
                    INPOST_ITALY_PLUGIN_VERSION,
                    array('in_footer' => true) );

            }
        }

        // only on edit shop order page
        if ( is_a( $current_screen, 'WP_Screen' ) && 'shop_order' === $current_screen->id
            || 'woocommerce_page_wc-orders' === $current_screen->id )
        {
            if( isset( $_GET['action'] ) && $_GET['action'] == 'edit') {

                if( isset($_GET['post']) ) {
                    $order_id = sanitize_text_field($_GET['post']);
                } else {
                    if( isset($_GET['id']) ) {
                        $order_id = sanitize_text_field($_GET['id']);
                    }
                }

                if( $order_id && is_numeric($order_id)) {
                    $order = wc_get_order($order_id);
                    if (is_object($order) && !is_wp_error($order)) {
                        foreach ($order->get_shipping_methods() as $shipping_method) {
                            if ($shipping_method->get_method_id() === 'easypack_italy_parcel_machines') {

                                wp_enqueue_style('geowidget-css', $this->get_geowidget_css_src(), [], INPOST_ITALY_PLUGIN_VERSION );
                                wp_enqueue_style('easypack-front-font-awesome', inpost_italy()->getPluginCss() . 'font-awesome.min.css', [], INPOST_ITALY_PLUGIN_VERSION );

                                wp_enqueue_script('easypack-it-create-shipment',
                                    $this->assets_js_uri . 'create-shipment-get-sticker.js',
                                    array('jquery'),
                                    INPOST_ITALY_PLUGIN_VERSION,
                                    array('in_footer' => true)
                                );
                                wp_localize_script(
                                    'easypack-it-create-shipment',
                                    'easypack_it',
                                    array(
                                        'easypack_nonce' => wp_create_nonce( 'easypack_nonce' ),
                                        'order_id' => $order_id
                                    )
                                );

                                wp_enqueue_script('easypack-it-admin-map',
                                    $this->assets_js_uri . 'lockers-map.js',
                                    array('jquery'),
                                    INPOST_ITALY_PLUGIN_VERSION,
                                    array('in_footer' => true)
                                );
                                wp_localize_script(
                                    'easypack-it-admin-map',
                                    'easypack_admin_map',
                                    array(
                                        'placeholder_text' => esc_html__('Type a city, address or postal code and select your choice. Or type an Inpost machine number and press magnifier icon', 'inpost-italy'),
                                    )
                                );
                            }
                        }
                    }
                }
            }
        }
    }



}
