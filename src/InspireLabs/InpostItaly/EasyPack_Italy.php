<?php

namespace InspireLabs\InpostItaly;

use Exception;
use InspireLabs\InpostItaly\EasyPack_Italy_Helper;
use InspireLabs\InpostItaly\admin\Alerts;
use InspireLabs\InpostItaly\admin\EasyPack_Italy_Product_Shipping_Method_Selector;
use InspireLabs\InpostItaly\admin\EasyPack_Italy_Settings_General;
use InspireLabs\InpostItaly\EmailFilters\NewOrderEmail;
use InspireLabs\InpostItaly\shipping\Easypack_Shipping_Rates;
use InspireLabs\InpostItaly\shipping\EasyPack_Italy_Shipping_Parcel_Machines;
use InspireLabs\InpostItaly\shipx\models\shipment\ShipX_Shipment_Model;
use InspireLabs\InpostItaly\shipx\services\courier_pickup\ShipX_Courier_Pickup_Service;
use InspireLabs\InpostItaly\shipx\services\organization\ShipX_Organization_Service;
use InspireLabs\InpostItaly\shipx\services\shipment\ShipX_Shipment_Price_Calculator_Service;
use InspireLabs\InpostItaly\shipx\services\shipment\ShipX_Shipment_Service;
use InspireLabs\InpostItaly\shipx\services\shipment\ShipX_Shipment_Status_Service;
use WC_Order;
use WC_Shipping_Method;


class EasyPack_Italy extends inspire_Plugin4 {

    const LABELS_DIRECTORY = __DIR__ . DIRECTORY_SEPARATOR . 'web' . DIRECTORY_SEPARATOR . 'labels';

    const CLASSES_DIRECTORY = __DIR__ . DIRECTORY_SEPARATOR . 'classes';

    const ATTRIBUTE_PREFIX = 'inpost_italy';

    const ENVIRONMENT_PRODUCTION = 'production';

    const ENVIRONMENT_SANDBOX = 'sandbox';


    public static $instance;

    public static $text_domain = 'inpost-italy';

    protected $_pluginNamespace = "inpost-italy";

    /**
     * @var WC_Shipping_Method[]
     */
    protected $shipping_methods = [];

    protected $settings;

    /**
     * @var string
     */
    private static $environment;

    private static $assets_js_uri;
    private static $assets_css_uri;
    private static $assets_img_uri;


    /**
     * @return string
     */
    public static function getLabelsUri() {
        return plugins_url() . '/woo-inpost/web/labels/';
    }

    public function __construct() {
        parent::__construct();
        add_action( 'plugins_loaded', [ $this, 'init_easypack_italy' ], 100 );
    }

    /**
     * @return mixed
     */
    public static function get_assets_img_uri() {
        return self::$assets_img_uri;
    }

    /**
     * @return mixed
     */
    public static function get_assets_js_uri() {
        return self::$assets_js_uri;
    }

    /**
     * @return mixed
     */
    public static function get_assets_css_uri() {
        return self::$assets_css_uri;
    }

    public static function EasyPack_Italy() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function init_easypack_italy() {
        $this->setup_environment();
        self::$assets_js_uri  = $this->getPluginJs();
        self::$assets_css_uri = $this->getPluginCss();
        self::$assets_img_uri = $this->getPluginImages();
        ( new Geowidget_v4() )->init_assets();
        $this->init_alerts();
        $this->loadPluginTextDomain();

        add_filter( 'woocommerce_get_settings_pages', [ $this, 'woocommerce_get_settings_pages' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ], 75 );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_block_script' ], 100 );
        //add_action( 'woocommerce_checkout_after_order_review', [ $this, 'woocommerce_checkout_after_order_review' ] );
        add_filter( 'woocommerce_order_item_display_meta_value', [ $this, 'change_order_item_meta_value' ], 20, 3 );
        add_action('woocommerce_cart_item_removed', [ $this, 'clear_wc_shipping_cache' ] );
        add_action('woocommerce_add_to_cart', [ $this, 'clear_wc_shipping_cache' ] );
        add_action('woocommerce_after_cart_item_quantity_update', [ $this, 'clear_wc_shipping_cache' ] );
        add_action( 'woocommerce_before_checkout_form', [ $this, 'clear_wc_shipping_cache' ] );
        add_filter( 'woocommerce_locate_template', [ $this, 'easypack_woo_templates' ], 1, 3 );


        add_filter( 'woocommerce_email_customer_details', [ $this, 'send_custom_email_addresses' ], 5, 4 );
        add_action('woocommerce_thankyou', [ $this,'hide_customer_shipping_on_thankyou_page' ], 10, 1 );
        add_action( 'woocommerce_view_order', [ $this, 'hide_customer_shipping_on_myaccount_page' ], 10 );


        try {
            ( new Easypack_Shipping_Rates() )->init();
            $this->init_shipping_methods();

            ( new EasyPack_Webhook() )->hooks();
            ( new EasyPackBulkOrders() )->hooks();

            // integration with Woocommerce blocks start
            add_action(
                'woocommerce_blocks_checkout_block_registration',
                function( $integration_registry ) {
                    $integration_registry->register( new EasyPack_Italy_WooBlocks() );
                }
            );
            add_action('woocommerce_store_api_checkout_update_order_from_request', array( $this, 'block_checkout_save_parcel_locker_in_order_meta'), 10, 2 );
            // integration with Woocommerce blocks end

            add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ], 75 );
            add_filter( 'woocommerce_shipping_methods', [ $this, 'woocommerce_shipping_methods' ], 1000 );

            add_filter( 'woocommerce_shipping_packages', [ $this, 'woocommerce_shipping_packages' ], 1000 );
			
			add_filter( 'woocommerce_package_rates', [ $this, 'filter_shipping_methods' ], PHP_INT_MAX );
			
            add_filter( 'woocommerce_get_order_item_totals', [ $this, 'show_parcel_machine_in_order_details' ], 2, 100 );
            $this->init_email_filters();
            ( new EasyPack_Italy_Product_Shipping_Method_Selector )->handle_product_edit_hooks();
        } catch ( Exception $exception ) {
            \wc_get_logger()->debug( 'Exception init_shipping_methods: ', array( 'source' => 'inpost-it-log' ) );
            \wc_get_logger()->debug( print_r( $exception->getMessage(), true), array( 'source' => 'inpost-it-log' ) );
        }
    }

    public function woocommerce_checkout_after_order_review() {
        echo '<input type="hidden" id="parcel_machine_id"
                     name="parcel_machine_id" class="parcel_machine_id"/>
            <input type="hidden" id="parcel_machine_desc"
                   name="parcel_machine_desc" class="parcel_machine_desc"/>';
    }

    /**
     * @return string
     */
    public static function get_environment(): string {
        return self::$environment;
    }

    /**
     * @return void
     */
    private function setup_environment() {
        if ( self::ENVIRONMENT_SANDBOX === get_option( 'easypack_italy_api_environment' ) ) {
            self::$environment = self::ENVIRONMENT_SANDBOX;
        } else {
            self::$environment = self::ENVIRONMENT_PRODUCTION;
        }
    }

    private function init_email_filters() {
        ( new NewOrderEmail() )->init();
    }

    private function init_alerts() {
        $alerts = new Alerts();
    }

    /**
     * @param array $items
     *
     * @param WC_Order $wcOrder
     *
     * @return array
     */
    public function show_parcel_machine_in_order_details( $items, $wcOrder ) {
        $order_id = $wcOrder->get_id();
        $parcel_desc = html_entity_decode( get_post_meta( $order_id, '_parcel_machine_desc', true ) );

        $parcel_machine_id = html_entity_decode( get_post_meta( $order_id, '_parcel_machine_id', true ) );

        if ( isset( $items['shipping'] ) && ! empty( $parcel_machine_id ) && ! empty( $parcel_desc ) ) {
            $items['shipping']['value']
                .= '<br>'
                . sprintf( esc_html__( 'Selected InPost point', 'inpost-italy' )
                    . ': <br><span class="italic">%1s'
                    . '<br><span class="italic">%2s', $parcel_machine_id, $parcel_desc )
                . '</span>';
        }

        return $items;
    }


    public function init_shipping_methods() {

        $stored_organization = get_option( 'inpost_italy_organisation' );
        $servicesAllowed = [];

        $main_methods = [
            "inpost_locker_standard",
        ];

        // get Shipping methods from stored settings
        if( ! empty( $stored_organization ) && is_array( $stored_organization ) ) {
            if( isset( $stored_organization['services'] ) && is_array( $stored_organization['services'] ) ) {
                foreach ( $main_methods as $service ) {
                    if ( in_array( $service, $stored_organization['services'] ) ) {
                        $servicesAllowed[] = $service;
                    }
                }
            }
        }

        // trying to connect to API during 60 seconds and save data to settings or show special message
        if ( empty( $servicesAllowed ) || ! is_array( $servicesAllowed ) ) {
            $now = time();
            $limit_time_to_retry = 60 + (int) get_option( 'inpost_italy_api_limit_connection', 0 ); // saved during saving API key

            if ( $limit_time_to_retry > $now ) {
                // try to connect to API only for 60 sec to avoid make website slow
                $this->get_or_update_data_from_api();
            } else {

                if ( ! empty( get_option('easypack_organization_id_italy') )
                    && ! empty( get_option('easypack_token_italy') )
                ) {

                    $alerts = new Alerts();
                    $error = sprintf( '%s <a target="_blank" mailto="integrations@inpost.it">%s</a>',
                        esc_html__( 'Inpost Italy: We were unable to connect to the API within 60 seconds. Please try to re-save settings later or', 'inpost-italy'),
                        esc_html__( 'contact to support', 'inpost-italy' )
                    );
                    $alerts->add_error( $error );
                }
            }
        }

        if( is_array( $servicesAllowed ) && ! empty( $servicesAllowed ) ) {
            if (in_array(EasyPack_Italy_Shipping_Parcel_Machines::SERVICE_ID, $servicesAllowed)) {
                $EasyPack_Italy_Shipping_Parcel_Machines = new EasyPack_Italy_Shipping_Parcel_Machines();
                //if ( is_user_logged_in() ) {
                $this->shipping_methods[] = $EasyPack_Italy_Shipping_Parcel_Machines;
                //}
            }
        }

        EasyPack_Italy_Product_Shipping_Method_Selector::$inpost_methods = $this->shipping_methods;
    }

    public function woocommerce_shipping_methods( $methods ) {

        foreach ( $this->shipping_methods as $shipping_method ) {

            $methods[ $shipping_method->id ] = get_class( $shipping_method );
        }

        return $methods;
    }

    public function woocommerce_shipping_packages( $packages ) {

        if ( is_object( WC()->session ) ) {
            $cart = WC()->session->get( 'cart' );

            if ( empty( $cart ) ) {
                $methods_allowed_by_cart = [];
            } else {
                $methods_allowed_by_cart = ( new EasyPack_Italy_Product_Shipping_Method_Selector )->get_methods_allowed_by_cart( $cart );
            }

        }

        $rates         = $packages[0]['rates'];
        $rates_allowed = [];

        if( is_array( $rates ) && ! empty( $rates ) ) {
            foreach ($rates as $k => $rate_object) {

                $method_name = inpost_italy_helper()->validate_method_name( $k );

                if ( 0 === strpos( $k, 'easypack_') ) {
                    if ( in_array( $method_name, $methods_allowed_by_cart ) ) {
                        $rates_allowed[$k] = $rate_object;
                    }
                } else {
                    $rates_allowed[$k] = $rate_object;
                }
            }
        }

        if ( ! empty( $rates_allowed ) ) {
            $packages[0]['rates'] = $rates_allowed;
        }

        return $packages;
    }

    public	function woocommerce_get_settings_pages( $woocommerce_settings ) {
        new EasyPack_Italy_Settings_General;
        return $woocommerce_settings;
    }

    public function get_package_sizes() {
        return [
            'small'  => esc_html__( 'S 8 x 38 x 64 cm', 'inpost-italy' ),
            'medium' => esc_html__( 'M 19 x 38 x 64 cm', 'inpost-italy' ),
            'large'  => esc_html__( 'L 41 x 38 x 64 cm', 'inpost-italy' ),
        ];
    }

    public function get_package_sizes_display() {
        return [
            'small'  => esc_html__( 'S', 'inpost-italy' ),
            'medium' => esc_html__( 'M', 'inpost-italy' ),
            'large'  => esc_html__( 'L', 'inpost-italy' ),
        ];
    }

    public function get_package_weights_parcel_machines() {
        return [
            '1'  => esc_html__( '1 kg', 'inpost-italy' ),
            '2'  => esc_html__( '2 kg', 'inpost-italy' ),
            '5'  => esc_html__( '5 kg', 'inpost-italy' ),
            '10' => esc_html__( '10 kg', 'inpost-italy' ),
            '15' => esc_html__( '15 kg', 'inpost-italy' ),
            '20' => esc_html__( '20 kg', 'inpost-italy' ),
        ];
    }

    public function get_package_weights_courier() {
        return [
            '1'  => esc_html__( '1 kg', 'inpost-italy' ),
            '2'  => esc_html__( '2 kg', 'inpost-italy' ),
            '5'  => esc_html__( '5 kg', 'inpost-italy' ),
            '10' => esc_html__( '10 kg', 'inpost-italy' ),
            '15' => esc_html__( '15 kg', 'inpost-italy' ),
            '20' => esc_html__( '20 kg', 'inpost-italy' ),
            '25' => esc_html__( '25 kg', 'inpost-italy' ),
        ];
    }

    public function loadPluginTextDomain() {
        load_plugin_textdomain('inpost-italy',
            false,
            dirname(plugin_basename(INPOST_ITALY_PLUGIN_FILE)) . '/languages/');
    }

    function getTemplatePathFull() {
        return implode( '/', [ $this->_pluginPath, $this->getTemplatePath() ] );
    }


    public function enqueue_scripts() {

        wp_enqueue_style( 'easypack-front', $this->getPluginCss() . 'front.css', [], INPOST_ITALY_PLUGIN_VERSION );
		$custom_button_color = get_option('easypack_italy_custom_button_css' );
		$other_custom_css = get_option('easypack_italy_custom_css' );
		if ( ! empty( $custom_button_color ) || ! empty( $other_custom_css ) ) {
			$easypack_settings_css = '';
			$easypack_settings_css .= isset($other_custom_css) ? $other_custom_css : '';

			if (isset($custom_button_color)) {
				$easypack_settings_css .= '#easypack_italy_geowidget {
				  background:  ' . $custom_button_color . ';
				}';
			}
            wp_add_inline_style( 'easypack-front', esc_html( sanitize_text_field($easypack_settings_css) ) );
		}
		if ( is_wc_endpoint_url('order-received') ) {
			$css = '.woocommerce-columns--addresses { display:none !important; } .wp-block-columns.woocommerce-order-confirmation-address-wrapper { display:none !important; }';
			wp_add_inline_style( 'easypack-front', $css );
		}
		

        if( get_option( 'easypack_italy_map_debug' ) === 'yes' && ! has_block( 'woocommerce/checkout' ) ) {
            wp_enqueue_script('easypack-it-debug-map', $this->getPluginJs() . 'debug-map.js',
                ['jquery', 'wp-i18n'],
                INPOST_ITALY_PLUGIN_VERSION,
                true
            );
            wp_localize_script(
                'easypack-it-debug-map',
                'easypack_front_map',
                array(
                    'location_icon' => esc_url(EasyPack_Italy::get_assets_img_uri() . "mylocation-sprite-2x.png" )
                )
            );
        }

        if( get_option( 'easypack_italy_map_debug' ) !== 'yes' && ! has_block( 'woocommerce/checkout' ) ) {
            wp_enqueue_script('easypack-front-js', $this->getPluginJs() . 'front.js', ['jquery'], INPOST_ITALY_PLUGIN_VERSION, true );
            wp_localize_script(
                'easypack-front-js',
                'easypack_front_map',
                array(
                    'location_icon'      => esc_url(EasyPack_Italy::get_assets_img_uri() . "mylocation-sprite-2x.png" ),
                    'button_text1'  => esc_html__('Select InPost Point', 'inpost-italy'),
                    'button_text2'  => esc_html__('Change InPost point', 'inpost-italy'),
                    'selected_text' => esc_html__( 'Selected parcel locker', 'inpost-italy' ),
                    'placeholder_text' => esc_html__('Type a city, address or postal code and select your choice. Or type an Inpost machine number and press magnifier icon', 'inpost-italy'),
                )
            );
        }

        if( is_checkout() ) {
            wp_enqueue_style( 'easypack-jbox-css', $this->getPluginCss() . 'jBox.all.min.css', [], INPOST_ITALY_PLUGIN_VERSION );
            wp_enqueue_script( 'easypack-jquery-modal', $this->getPluginJs() . 'jBox.all.min.js', [ 'jquery' ], INPOST_ITALY_PLUGIN_VERSION, true );
        }

    }

    function enqueue_admin_scripts() {

        $current_screen = get_current_screen();

        if( ! strstr( sanitize_text_field($_SERVER['REQUEST_URI']), 'wp-admin/post-new.php' ) && ! strstr( sanitize_text_field($_SERVER['REQUEST_URI']), 'wp-admin/post.php' ) ) {
            //avoid broken CSS in tav "Variations" when editing a post, page or custom post type
            wp_enqueue_style('easypack-italy-admin', $this->getPluginCss() . 'admin.css', [], INPOST_ITALY_PLUGIN_VERSION );
        }

        if ( is_a( $current_screen, 'WP_Screen' ) && 'woocommerce_page_wc-orders' === $current_screen->id ) {
            wp_enqueue_style('easypack-italy-admin', $this->getPluginCss() . 'admin.css', [], INPOST_ITALY_PLUGIN_VERSION );
        }
        if ( is_a( $current_screen, 'WP_Screen' ) && 'inpost-italy_page_easypack_italy' === $current_screen->id ) {
            $css = '.form-table { border-bottom: 1px solid #ccc; }';
            wp_add_inline_style( 'easypack-italy-admin', $css );
        }


        wp_enqueue_style( 'easypack-italy-admin-modal', $this->getPluginCss() . 'modal.css', [], INPOST_ITALY_PLUGIN_VERSION );
        wp_enqueue_style( 'easypack-jbox-css', $this->getPluginCss() . 'jBox.all.min.css', [], INPOST_ITALY_PLUGIN_VERSION );


        if ( is_a( $current_screen, 'WP_Screen' ) ) {
            if ( 'inpost-italy_page_easypack_shipment_italy' === $current_screen->id 
				|| 'inpost-italia_page_easypack_shipment_italy' === $current_screen->id ) {

                $css = '.optional { display:none !important; }';
                wp_add_inline_style('easypack-italy-admin', $css);
				
                wp_enqueue_script('easypack-italy-admin', $this->getPluginJs() . 'admin.js',
                    ['jquery', 'wp-i18n'],
                    INPOST_ITALY_PLUGIN_VERSION,
                    ['in_footer' => true]
                );
                wp_localize_script(
                    'easypack-italy-admin',
                    'easypack_settings',
                    array(
                        'default_logo' => inpost_italy()->getPluginImages() . 'logo/small/white.png'
                    )
                );

                wp_enqueue_script('easypack-italy-shipment-manager', $this->getPluginJs() . 'shipment-manager.js',
                    ['jquery'],
                    INPOST_ITALY_PLUGIN_VERSION,
                    ['in_footer' => true]
                );
                wp_localize_script(
                    'easypack-italy-shipment-manager',
                    'easypack_shipment_manager',
                    array(
                        'nonce' => wp_create_nonce('easypack-italy-shipment-manager')
                    )
                );
            }
        }

        if ( is_a( $current_screen, 'WP_Screen' ) && 'woocommerce_page_wc-settings' === $current_screen->id ) {
            if (isset($_GET['tab']) && $_GET['tab'] == 'easypack_italy') {
                wp_enqueue_script('easypack-italy-admin', $this->getPluginJs() . 'admin.js',
                    ['jquery', 'wp-i18n'],
                    INPOST_ITALY_PLUGIN_VERSION,
                    ['in_footer' => true]
                );
                wp_localize_script(
                    'easypack-italy-admin',
                    'easypack_settings',
                    array(
                        'default_logo' => inpost_italy()->getPluginImages() . 'logo/small/white.png'
                    )
                );
            }
        }

        if ( is_a( $current_screen, 'WP_Screen' ) && 'woocommerce_page_wc-settings' === $current_screen->id ) {
            if (isset($_GET['tab']) && $_GET['tab'] == 'shipping' && isset($_GET['instance_id'])) {
                wp_enqueue_script('easypack-italy-admin', $this->getPluginJs() . 'admin.js',
                    ['jquery', 'wp-i18n'],
                    INPOST_ITALY_PLUGIN_VERSION,
                    ['in_footer' => true]
                );
                wp_localize_script(
                    'easypack-italy-admin',
                    'easypack_settings',
                    array(
                        'default_logo' => inpost_italy()->getPluginImages() . 'logo/small/white.png'
                    )
                );
            }
        }


        wp_enqueue_media(); // logo upload dependency
        wp_enqueue_script( 'easypack-admin-modal', $this->getPluginJs() . 'modal.js', [ 'jquery' ], INPOST_ITALY_PLUGIN_VERSION, true );
        wp_enqueue_script( 'easypack-jquery-modal', $this->getPluginJs() . 'jBox.all.min.js', [ 'jquery' ], INPOST_ITALY_PLUGIN_VERSION, true );

        $current_screen = get_current_screen();

        if ( is_a( $current_screen, 'WP_Screen' ) && 'woocommerce_page_wc-settings' === $current_screen->id ) {
            if( isset( $_GET['tab'] ) && $_GET['tab'] == 'easypack_italy') {

                wp_register_script( 'easypack-admin-settings-page',
                    $this->getPluginJs() . 'admin-settings-page.js',
                    [ 'jquery' ],
                    INPOST_ITALY_PLUGIN_VERSION,
                    true );

                wp_enqueue_script( 'easypack-admin-settings-page' );
            }
        }

        if ( is_a( $current_screen, 'WP_Screen' ) && 'woocommerce_page_wc-settings' === $current_screen->id ) {
            if( isset( $_GET['tab'] ) && $_GET['tab'] == 'shipping' && isset( $_GET['instance_id'] ) ) {
                wp_register_script( 'easypack-shipping-method-settings',
                    $this->getPluginJs() . 'shipping-settings-page.js',
                    [ 'jquery', 'wp-i18n' ],
                    INPOST_ITALY_PLUGIN_VERSION,
                    true );

                wp_enqueue_script( 'easypack-shipping-method-settings' );
            }
        }

    }


    /**
     * @return ShipX_Shipment_Service
     */
    public function get_shipment_service() {
        return new ShipX_Shipment_Service();
    }

    /**
     * @return ShipX_Organization_Service
     */
    public function get_organization_service() {
        return new ShipX_Organization_Service();
    }

    /**
     * @return ShipX_Shipment_Price_Calculator_Service
     */
    public function get_shipment_price_calculator_service() {
        return new ShipX_Shipment_Price_Calculator_Service();
    }

    /**
     * @return ShipX_Courier_Pickup_Service
     */
    public function get_courier_pickup_service() {
        return new ShipX_Courier_Pickup_Service();
    }

    /**
     * @return ShipX_Shipment_Status_Service
     */
    public function get_shipment_status_service() {
        return new ShipX_Shipment_Status_Service();
    }


    /**
     * Replace custom logo link of shipping method for correct view
     */
    public function change_order_item_meta_value( $value, $meta, $item ) {

        if( is_admin() && $item->get_type() === 'shipping' && $meta->key === 'logo' ) {
            if( !empty( $value ) ) {
                $value = '<img style="width: 60px; height: auto; background-size: cover;" src="' . esc_url( $value ) . '">';
            }
        }
        return $value;
    }

    /**
     * Clear WC shipping methods cache
     */
    public function clear_wc_shipping_cache() {
        \WC_Cache_Helper::get_transient_version( 'shipping', true );
    }

    /**
     * define path to Woocommerce templates in our plugin
     *
     */
    public function easypack_woo_templates( $template, $template_name, $template_path ) {
        global $woocommerce;
        $_template = $template;
        if ( ! $template_path ) {
            $template_path = $woocommerce->template_url;
        }

        $plugin_templates_path  = untrailingslashit( inpost_italy()->getPluginFullPath() )  . '/resources/templates/';

        $template = locate_template(
            array(
                $template_path . $template_name,
                $template_name
            )
        );

        if( ! $template && file_exists( $plugin_templates_path . $template_name ) ) {
            $template = $plugin_templates_path . $template_name;
        }

        if ( ! $template ) {
            $template = $_template;
        }

        return $template;
    }


    public function get_package_sizes_gabaryt() {
        return [
            'small'  => esc_html__( 'Size S (8 x 38 x 64 cm)', 'inpost-italy' ),
            'medium' => esc_html__( 'Size M (19 x 38 x 64 cm)', 'inpost-italy' ),
            'large'  => esc_html__( 'Size L (41 x 38 x 64 cm)', 'inpost-italy' ),
        ];
    }

    private function get_or_update_data_from_api() {
        try {

            $organization_service = EasyPack_Italy::EasyPack_Italy()->get_organization_service();
            $organization = $organization_service->query_organisation();
            if ( ! is_object( $organization ) ) {
                throw new Exception('InPost Italy: Cannot get data from API');
            }

        } catch(Exception $e) {
            \wc_get_logger()->debug( 'Exception in get_or_update_data_from_api: ', array( 'source' => 'inpost-it-log' ) );
            \wc_get_logger()->debug( print_r( $e->getMessage(), true), array( 'source' => 'inpost-it-log' ) );
        }
    }


    public function send_custom_email_addresses( $order, $sent_to_admin, $plain_text, $email ){
        if ( is_a( $order, 'WC_Order' ) ) {
            foreach($order->get_shipping_methods() as $shipping_method ){
                if ( $shipping_method->get_method_id() === 'easypack_italy_parcel_machines' ){
                    $mailer = WC()->mailer();
                    // remove standard customer details hooks
                    remove_action( 'woocommerce_email_customer_details', array( $mailer, 'customer_details' ), 10, 3 );
                    remove_action( 'woocommerce_email_customer_details', array( $mailer, 'email_addresses' ), 20, 3 );

                    // add custom details where shipping address replaced with Inpost point
                    add_action( 'woocommerce_email_customer_details', [ $this, 'custom_email_addresses' ], 30, 1 );
                }
            }
        }
    }


    public function custom_email_addresses( $order ) {
        if ( is_a( $order, 'WC_Order' ) ) :

            $text_align = is_rtl() ? 'right' : 'left';
            $address    = $order->get_formatted_billing_address();

            $order_id = $order->get_id();

            $parcel_desc = html_entity_decode( get_post_meta( $order_id, '_parcel_machine_desc', true ) );
            $parcel_machine_id = html_entity_decode( get_post_meta( $order_id, '_parcel_machine_id', true ) );

            $parcel_data = sprintf( '<span class="italic">%1s'
                    . '<br><span class="italic">%2s', $parcel_machine_id, $parcel_desc )
                . '</span>';

            // hide standard table with addresses via CSS
            $css = 'table#addresses { display:none !important; }';
            wp_add_inline_style( 'easypack-front', $css );
            ?>
            <table id="inpost-email-addresses" cellspacing="0" cellpadding="0" style="width: 100%; vertical-align: top; margin-bottom: 40px; padding:0;" border="0">
                <tr>
                    <td style="text-align:<?php echo esc_attr( $text_align ); ?>; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; border:0; padding:0;" valign="top" width="50%">
                        <h2><?php esc_html_e( 'Billing address', 'inpost-italy' ); ?></h2>

                        <address class="address">
                            <?php echo wp_kses_post( $address ? $address : esc_html__( 'N/A', 'inpost-italy' ) ); ?>
                            <?php if ( $order->get_billing_phone() ) : ?>
                                <br/><?php echo wp_kses_post( wc_make_phone_clickable( $order->get_billing_phone() ) ); ?>
                            <?php endif; ?>
                            <?php if ( $order->get_billing_email() ) : ?>
                                <br/><?php echo esc_html( $order->get_billing_email() ); ?>
                            <?php endif; ?>
                        </address>
                    </td>
                    <?php if ( $order->needs_shipping_address() && $parcel_data ) : ?>
                        <td style="text-align:<?php echo esc_attr( $text_align ); ?>; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; padding:0;" valign="top" width="50%">
                            <h2><?php esc_html_e( 'InPost point locker', 'inpost-italy' ); ?></h2>

                            <address class="address">
                                <?php echo wp_kses_post( $parcel_data ); ?>
                            </address>
                        </td>
                    <?php endif; ?>
                </tr>
            </table>
        <?php
        endif;
    }


    public function hide_customer_shipping_on_thankyou_page( $order_id ){
        //create an order instance
        $order = wc_get_order($order_id);
        
        foreach($order->get_shipping_methods() as $shipping_method ) {
            if ($shipping_method->get_method_id() === 'easypack_italy_parcel_machines') {
                $text_align = is_rtl() ? 'right' : 'left';
                $address    = $order->get_formatted_billing_address();

                $parcel_desc = html_entity_decode( get_post_meta( $order_id, '_parcel_machine_desc', true ) );
                $parcel_machine_id = html_entity_decode( get_post_meta( $order_id, '_parcel_machine_id', true ) );

                $parcel_data = sprintf( '<span class="italic">%1s'
                        . '<br><span class="italic">%2s', $parcel_machine_id, $parcel_desc )
                    . '</span>';

                // hide standard table with addresses via CSS
                ?>
                <table id="inpost-email-addresses" cellspacing="0" cellpadding="0" style="width: 100%; vertical-align: top; margin-bottom: 40px; padding:0;" border="0">
                    <tr>
                        <td style="text-align:<?php echo esc_attr( $text_align ); ?>; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; border:0; padding:0;" valign="top" width="50%">
                            <h2><?php esc_html_e( 'Billing address', 'inpost-italy' ); ?></h2>

                            <address class="address">
                                <?php echo wp_kses_post( $address ? $address : esc_html__( 'N/A', 'inpost-italy' ) ); ?>
                                <?php if ( $order->get_billing_phone() ) : ?>
                                    <br/><?php echo wp_kses_post( wc_make_phone_clickable( $order->get_billing_phone() ) ); ?>
                                <?php endif; ?>
                                <?php if ( $order->get_billing_email() ) : ?>
                                    <br/><?php echo esc_html( $order->get_billing_email() ); ?>
                                <?php endif; ?>
                            </address>
                        </td>
                        <?php if ( $order->needs_shipping_address() && $parcel_data ) : ?>
                            <td style="text-align:<?php echo esc_attr( $text_align ); ?>; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; padding:0;" valign="top" width="50%">
                                <h2 class="inpost-italy-thankyou-page"><?php esc_html_e( 'InPost point locker', 'inpost-italy' ); ?></h2>

                                <address class="address inpost-italy-thankyou-page">
                                    <?php echo wp_kses_post( $parcel_data ); ?>
                                </address>
                            </td>
                        <?php endif; ?>
                    </tr>
                </table>
                <?php
            }
        }
    }


    public function hide_customer_shipping_on_myaccount_page( $order_id ) {
        if ( ! $order_id ) {
            return;
        }

        $order = wc_get_order( $order_id );
        if( $order && ! is_wp_error( $order ) ) {
            $show_shipping = ! wc_ship_to_billing_address_only() && $order->needs_shipping_address();
            $parcel_desc = html_entity_decode( get_post_meta( $order_id, '_parcel_machine_desc', true ) );
            $parcel_machine_id = html_entity_decode( get_post_meta( $order_id, '_parcel_machine_id', true ) );

            $parcel_data = sprintf( '<span class="italic">%1s'
                    . '<br><span class="italic">%2s', $parcel_machine_id, $parcel_desc )
                . '</span>';

            $css = '.woocommerce-customer-details { display:none !important; } .woocommerce-customer-details.easypack-italy { display: block !important; }';
            wp_add_inline_style( 'easypack-front', $css );

            foreach ( $order->get_shipping_methods() as $shipping_method ) {
                if ( $shipping_method->get_method_id() === 'easypack_italy_parcel_machines' ) {
                    ?>
                    <section class="woocommerce-customer-details easypack-italy">

                        <?php if ( $show_shipping ) : ?>

                        <section class="woocommerce-columns woocommerce-columns--2 woocommerce-columns--addresses col2-set addresses">
                            <div class="woocommerce-column woocommerce-column--1 woocommerce-column--billing-address col-1">

                                <?php endif; ?>

                                <h2 class="woocommerce-column__title"><?php esc_html_e( 'Billing address', 'inpost-italy' ); ?></h2>

                                <address>
                                    <?php echo wp_kses_post( $order->get_formatted_billing_address( esc_html__( 'N/A', 'inpost-italy' ) ) ); ?>

                                    <?php if ( $order->get_billing_phone() ) : ?>
                                        <p class="woocommerce-customer-details--phone"><?php echo esc_html( $order->get_billing_phone() ); ?></p>
                                    <?php endif; ?>

                                    <?php if ( $order->get_billing_email() ) : ?>
                                        <p class="woocommerce-customer-details--email"><?php echo esc_html( $order->get_billing_email() ); ?></p>
                                    <?php endif; ?>
                                </address>

                                <?php if ( $show_shipping ) : ?>

                            </div><!-- /.col-1 -->

                            <div class="woocommerce-column woocommerce-column--2 woocommerce-column--shipping-address col-2">
                                <h2 class="woocommerce-column__title">
                                    <?php esc_html_e( 'Shipping address', 'inpost-italy' ); ?>
                                </h2>
                                <p class="easypack-italy-column__subtitle">
                                    <?php esc_html_e( 'Collection at InPost Point', 'inpost-italy' ); ?>
                                </p>
                                <address>
                                    <?php echo wp_kses_post( $parcel_data ); ?>
                                </address>
                            </div><!-- /.col-2 -->

                        </section><!-- /.col2-set -->

                    <?php endif; ?>

                        <?php do_action( 'woocommerce_order_details_after_customer_details', $order ); ?>

                    </section>
                <?php }
            }
        }

    }


    public function enqueue_block_script() {
        if( is_checkout() && has_block( 'woocommerce/checkout' )) {

            wp_enqueue_script('inpost-italy-front-blocks', $this->getPluginJs() . 'front-blocks.js', ['jquery'], INPOST_ITALY_PLUGIN_VERSION, true );
            wp_localize_script(
                'inpost-italy-front-blocks',
                'easypack_blocks',
                array(
                    'location_icon'      => esc_url(EasyPack_Italy::get_assets_img_uri() . "mylocation-sprite-2x.png" ),
                    'button_text1'       => esc_html__( 'Select Parcel Locker', 'inpost-italy' ),
                    'button_text2'       => esc_html__( 'Change Parcel Locker', 'inpost-italy' ),
                    'selected_text'      => esc_html__( 'Selected parcel locker', 'inpost-italy' ),
                    'placeholder_text'   => esc_html__('Type a city, address or postal code and select your choice. Or type an Inpost machine number and press magnifier icon', 'inpost-italy'),
                )
            );

        }
    }


    public function block_checkout_save_parcel_locker_in_order_meta( $order, $request ) {

        if( ! $order ) {
            return;
        }

        /*
        $shipping_method_id = null;
        foreach( $order->get_items( 'shipping' ) as $item_id => $item ){
            $shipping_method_id          = $item->get_method_id();
            $shipping_method_instance_id = $item->get_instance_id();
        }
        */

        $request_body = json_decode($request->get_body(), true);

        if( isset( $request_body['extensions']['inpost']['inpost-italy-parcel-locker-id'] )
            && ! empty( $request_body['extensions']['inpost']['inpost-italy-parcel-locker-id'] ) ) {

            $parcel_machine_id = sanitize_text_field( $request_body['extensions']['inpost']['inpost-italy-parcel-locker-id'] );

            update_post_meta( $order->get_ID(), '_parcel_machine_id', $parcel_machine_id );
            $order->update_meta_data( '_parcel_machine_id', $parcel_machine_id );
            $order->save();
        }
    }
	
	
	public function filter_shipping_methods( $rates ) {

        $methods_required_geowidget = [
            'easypack_italy_parcel_machines'
        ];

        $inpost_cart_limit = get_option( 'easypack_italy_cart_limit', 0 );

        if( floatval($inpost_cart_limit) > 0 ) {
            if (is_object( WC() ) && floatval(WC()->cart->cart_contents_total) > floatval($inpost_cart_limit) ) {
                foreach ( $rates as $key => $rate ) {
                    if( $rate->method_id === 'easypack_italy_parcel_machines') {
                        unset($rates[$key]);
                    }
                }
            }
        }

        if( !empty($rates) && is_array($rates) && count($rates) === 1 && has_block( 'woocommerce/checkout' ) ) {
            $single_rate = reset($rates);

            if( is_checkout() ) {
                if( in_array($single_rate->method_id, $methods_required_geowidget) ) {

                    wp_enqueue_script('easypack-italy-single', $this->getPluginJs() . '/blocks/single.js', ['jquery'], INPOST_ITALY_PLUGIN_VERSION, true);
                    wp_localize_script(
                        'easypack-italy-single',
                        'easypack_single',
                        array(
                            'need_map' => true
                        )
                    );
                }
            }
        }

        return $rates;
    }


}