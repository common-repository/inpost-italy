<?php

namespace InspireLabs\InpostItaly\admin;

use InspireLabs\InpostItaly\EasyPack_Italy;
use Exception;
use InspireLabs\InpostItaly\EasyPack_Italy_API;
use InspireLabs\InpostItaly\shipx\models\courier_pickup\ShipX_Dispatch_Order_Point_Model;
use WC_Admin_Settings;
use WC_Settings_Page;

/**
 * EasyPack General Settings
 *
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'EasyPack_Italy_Settings_General' ) ) :

	/**
	 * EasyPack_Italy_Settings_General
	 */
	class EasyPack_Italy_Settings_General extends WC_Settings_Page {

		static $prevent_duplicate = [];

		/**
		 * Constructor.
		 */
		public function __construct() {
            parent::__construct();
			$this->id    = 'easypack_italy';
			$this->label = esc_html__( 'InPost Italy', 'inpost-italy' );

			add_action( 'woocommerce_settings_' . $this->id, [ $this, 'output' ] );
			add_action( 'woocommerce_settings_save_' . $this->id, [ $this, 'save' ] );
            add_action( 'woocommerce_sections_' . $this->id, array( $this, 'output_sections' ) );
		}


        /**
         * Get sections.
         *
         * @return array
         */
        public function get_sections() {
            $sections = [
                ''     => esc_html__( 'Settings', 'inpost-italy' ),
                'help' => esc_html__( 'Support & Additional services', 'inpost-italy' ),
            ];

            $sections =  apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );

            return $sections;
        }


        /**
         * Output the settings
         */
        public function output() {

            global $current_section;

            if ( 'help' === $current_section ) {
                require_once untrailingslashit( inpost_italy()->getPluginFullPath() )  . '/resources/templates/help.php';

            } else {

                $easypack_api_change = get_option('easypack_api_error_login', 0);
                ?>
                <input id="easypack_api_change" type="hidden"
                       name="easypack_api_change"
                       value="<?php echo esc_attr($easypack_api_change); ?>"
                >
                <?php
                $settings = $this->get_settings();
                WC_Admin_Settings::output_fields($settings);
            }
        }


		/**
		 * @param ShipX_Dispatch_Order_Point_Model[] $points
		 *
		 * @return array
		 */
		public function getDispathPointsOptions( $points ) {
			$return = [];

			foreach ( $points as $point ) {
				$return[ $point->getId() ] = $point->getName();
			}

			return $return;
		}

		/**
		 * Get settings array
		 *
		 * @return array
		 * @throws Exception
		 */
		public function get_settings() {
			$send_methods = [
				'parcel_machine' => esc_html__( 'Parcel Locker', 'inpost-italy' )
			];


            $settings = [

                [
                    'title' => '',
                    'type'  => 'title',
                    'desc'  => '<img style="width:60px; height:auto" src="' .  inpost_italy()->getPluginImages() . 'logo/small/white.png">'
                        . '<br>'
                    ,
                    'id'    => 'help_options',
                ],

                [ 'type' => 'sectionend', 'id' => 'country_options' ],

                [
                    'title' => esc_html__( 'Authorization settings', 'inpost-italy' ),
                    'type'  => 'title',
                    'desc'  => '',
                    'id'    => 'general_options',
                ],


                [
                    'title'             => esc_html__( 'Organization ID', 'inpost-italy' ),
                    'id'                => 'easypack_organization_id_italy',
                    'css'               => 'min-width:300px;',
                    'default'           => '',
                    'type'              => 'text',
                    'desc_tip'          =>  esc_html__( 'This credential will be provided by InPost after signing a contract. For support, please write to italysales@inpost.it', 'inpost-italy' ),
                    'desc'              => sprintf( '<p class="easypack_access_notice">%1s</p>',
                        esc_html__('Please write to italysales@inpost.it', 'inpost-italy') ),
                    'custom_attributes' => [ 'required' => 'required' ],
                ],

                [
                    'title'             => esc_html__( 'Token', 'inpost-italy' ),
                    'id'                => 'easypack_token_italy',
                    'css'               => 'min-width:300px;',
                    'default'           => '',
                    'type'              => 'text',
                    'desc_tip'          => esc_html__( 'This credential will be provided by InPost after signing a contract. For support, please write to italysales@inpost.it', 'inpost-italy' ),
                    'desc'              => sprintf( '<p class="easypack_access_notice">%1s</p>',
                        esc_html__('Please write to italysales@inpost.it', 'inpost-italy') ),
                    'custom_attributes' => [ 'required' => 'required' ],
                ],


                [
                    'title'   => esc_html__( 'API type', 'inpost-italy' ),
                    'id'      => 'easypack_italy_api_environment',
                    'default' => 'production',
                    'type'    => 'select',
                    'css'     => 'min-width: 300px;',
                    'options' => [
                        'production' => esc_html__( 'Production', 'inpost-italy' ),
                        'sandbox'    => esc_html__( 'Sandbox', 'inpost-italy' ),
                    ],
                ],


                [ 'type' => 'sectionend', 'id' => 'general_options' ],

                // Sender block start
                [
                    'title' => esc_html__( 'Merchant', 'inpost-italy' ),
                    'type'  => 'title',
                    'desc'  => '',
                    'id'    => 'sender_options',
                ],

                [
                    'title'             => esc_html__( 'Company Name', 'inpost-italy' ),
                    'id'                => 'easypack_italy_sender_company_name',
                    'css'               => 'min-width:300px;',
                    'default'           => '',
                    'type'              => 'text',
                    'desc_tip'          => false,
                    'custom_attributes' => [ 'required' => 'required' ],
                ],
                [
                    'title'             => esc_html__( 'Email to receive delivery updates', 'inpost-italy' ),
                    'id'                => 'easypack_italy_sender_email',
                    'css'               => 'min-width:300px;',
                    'default'           => '',
                    'type'              => 'email',
                    'desc_tip'          => false,
                    'custom_attributes' => [ 'required' => 'required' ],
                ],

                [
                    'title'             => esc_html__( 'Mobile phone number to receive delivery updates', 'inpost-italy' ),
                    'id'                => 'easypack_italy_sender_phone',
                    'css'               => 'min-width:300px;',
                    'default'           => '',
                    'type'              => 'text',
                    'desc_tip'          => false,
                    'custom_attributes' => [ 'required' => 'required' ],
                ],
                [ 'type' => 'sectionend', 'id' => 'sender_options' ],

                [
                    'title' => esc_html__( 'Delivery Address for returns to sender', 'inpost-italy' ),
                    'type'  => 'title',
                    'desc'  => '',
                    'id'    => 'address_options',
                ],
                [
                    'title'             => esc_html__( 'City', 'inpost-italy' ),
                    'id'                => 'easypack_italy_sender_city',
                    'css'               => 'min-width:300px;',
                    'default'           => '',
                    'type'              => 'text',
                    'desc_tip'          => false,
                    'custom_attributes' => [ 'required' => 'required' ],
                ],
                [
                    'title'             => esc_html__( 'Post code', 'inpost-italy' ),
                    'id'                => 'easypack_italy_sender_post_code',
                    'css'               => 'min-width:300px;',
                    'default'           => '',
                    'type'              => 'text',
                    'custom_attributes' => [ 'required' => 'required' ],
                ],
                [
                    'title'    => esc_html__( 'Street', 'inpost-italy' ),
                    'id'       => 'easypack_italy_sender_street',
                    'css'      => 'min-width:300px;',
                    'default'  => '',
                    'type'     => 'text',
                    'desc_tip' => false,
                    'custom_attributes' => [ 'required' => 'required' ],
                ],
                [
                    'title'    => esc_html__( 'Building no', 'inpost-italy' ),
                    'id'       => 'easypack_italy_sender_building_no',
                    'css'      => 'min-width:300px;',
                    'default'  => '',
                    'type'     => 'text',
                    'desc_tip' => false,
                    'custom_attributes' => [ 'required' => 'required' ],
                ],

                [ 'type' => 'sectionend', 'id' => 'address_options' ],
                // Sender block end


                [
                    'title' => esc_html__( 'Delivery options', 'inpost-italy' ),
                    'type'  => 'title',
                    'desc'  => '',
                    'id'    => 'send_options',
                ],

                [
                    'title'    => esc_html__( 'Default package size', 'inpost-italy' ),
                    'id'       => 'easypack_italy_default_package_size',
                    'type'     => 'select',
                    'class'    => 'wc-enhanced-select',
                    'css'      => 'min-width: 300px;',
                    'desc_tip' => false,
                    'default'  => 'S',
                    'options'  => inpost_italy()->get_package_sizes(),
                ],

                [
                    'title'    => esc_html__( 'Label format', 'inpost-italy' ),
                    'id'       => 'easypack_italy_label_format',
                    'type'     => 'select',
                    'class'    => 'wc-enhanced-select',
                    'css'      => 'min-width: 300px;',
                    'desc_tip' => false,
                    'default'  => 'A6',
                    'options'  => [
                        'A6' => 'A6',
                        'A4' => 'A4',
                    ],
                ],

                [
                    'title'    => esc_html__( 'Hide InPost if the cart exceeds the amount',
                        'inpost-italy' ),
                    'id'       => 'easypack_italy_cart_limit',
                    'type'              => 'number',
                    'custom_attributes' => [
                        'step' => 'any',
                        'min'  => '0',
                    ],
                    'class'    => '',
                    'css'      => 'min-width: 300px;',
                    'desc_tip' => false,
                    'default'  => '',
                    'placeholder'       => '0.00',

                ],

                [
                    'title'    => esc_html__( 'Do not provide InPost service if the weight of goods exceeds 25 Kg',
                        'inpost-italy' ),
                    'id'       => 'easypack_italy_over_weight',
                    'type'     => 'checkbox',
                    'class'    => '',
                    'css'      => 'min-width: 300px;',
                    'desc_tip' => false,
                    'default'  => '',
                    'options'  => []

                ],

                [
                    'title'    => esc_html__( 'Link to the external return portal', 'inpost-italy' ),
                    'id'       => 'easypack_fast_return',
                    'css'      => 'min-width:300px;',
                    'default'  => '',
                    'type'     => 'text',
                    'desc_tip' => false,
                    'class'    => 'easypack-api-url',
                ],
				
				[
                    'title'    => esc_html__( 'Type of flow', 'inpost-italy' ),
                    'id'       => 'easypack_italy_flow_type',
                    'type'     => 'select',
                    'class'    => 'wc-enhanced-select',
                    'css'      => 'min-width: 300px;',
                    'desc_tip' => false,
                    'default'  => 'L2L',
                    'options'  => [
                        'L2L' => 'L2L',
                        'A2L' => 'A2L',
                    ],
                ],

                [ 'type' => 'sectionend', 'id' => 'send_options' ],
				
				// Pick Up Address section start				
				[
                    'title' => esc_html__( 'Pick Up Address', 'inpost-italy' ),
                    'type'  => 'title',
                    'desc'  => '',
                    'id'    => 'pickup_address_options',
					'class'   => 'easypack_italy_hidden_setting'
                ],
				
                [
                    'title'             => esc_html__( 'City', 'inpost-italy' ),
                    'id'                => 'easypack_italy_pickup_city',
                    'css'               => 'min-width:300px;',
                    'default'           => '',
                    'type'              => 'text',
                    'desc_tip'          => false,
					'class'   => 'easypack_italy_hidden_setting',
                ],
                [
                    'title'             => esc_html__( 'Post code', 'inpost-italy' ),
                    'id'                => 'easypack_italy_pickup_post_code',
                    'css'               => 'min-width:300px;',
                    'default'           => '',
                    'type'              => 'text',
					'class'   => 'easypack_italy_hidden_setting',
                ],
                [
                    'title'    => esc_html__( 'Street', 'inpost-italy' ),
                    'id'       => 'easypack_italy_pickup_street',
                    'css'      => 'min-width:300px;',
                    'default'  => '',
                    'type'     => 'text',
                    'desc_tip' => false,
					'class'   => 'easypack_italy_hidden_setting',
                ],
                [
                    'title'    => esc_html__( 'Building no', 'inpost-italy' ),
                    'id'       => 'easypack_italy_pickup_building_no',
                    'css'      => 'min-width:300px;',
                    'default'  => '',
                    'type'     => 'text',
                    'desc_tip' => false,
					'class'   => 'easypack_italy_hidden_setting',
                ],

                [ 'type' => 'sectionend', 'id' => 'pickup_address_options' ],
                // Pick Up Address block end



                // Custom CSS
                [
                    'title' => esc_html__( 'Graphic settings', 'inpost-italy' ),
                    'type'  => 'title',
                    'desc'  => '',
                    'id'    => 'easypack_custom_css_options',
                ],

                [
                    'name'  => esc_html__( 'Color setting for Inpost call to actions at the checkout', 'inpost-italy' ),
                    'type'  => 'text',
                    'desc'  => '',
                    'id'    => 'easypack_italy_custom_button_css'
                ],


                [
                    'name'  => esc_html__( 'Customize the Inpost service description at the checkout in line with your current visual identity', 'inpost-italy' ),
                    'type'  => 'textarea',
                    'desc'  => sprintf( '%1s<br>%1s<b>%2s</b>',
                        esc_html__('Set other custom CSS if need.', 'inpost-italy'),
                        esc_html__('For example call map button has id: ', 'inpost-italy' ),
                        '#easypack_italy_geowidget'
                    ),
                    'id'    => 'easypack_italy_custom_css'
                ],

                [
                    'title'    => esc_html__( 'Enable map button debug-mode', 'inpost-italy' ),
                    'id'       => 'easypack_italy_map_debug',
                    'type'     => 'checkbox',
                    'class'    => '',
                    'css'      => 'min-width: 300px;',
                    'desc_tip' => false,
                    'default'  => '',
                    'options'  => []

                ],

                [ 'type' => 'sectionend', 'id' => 'easypack_custom_css_options' ],

            ];

            if ( empty( $current_section ) || 'settings' === $current_section ) {
                return $settings;
            }

            return [];
		}

		/**
		 * @param string|int $key
		 * @param array $array
		 *
		 * @return mixed
		 */
		private function get_from_array( $key, $array ) {
			return isset( $array[ $key ] ) ? $array[ $key ] : null;
		}

		/**
		 * Save settings
		 *
		 * @throws Exception
		 */
		public function save() {
			$settings = $this->get_settings();
			WC_Admin_Settings::save_fields( $settings );
            inpost_italy_api()->clear_cache();

            if( ! empty( $_REQUEST['easypack_organization_id_italy'] ) && ! empty( $_REQUEST['easypack_token_italy'] ) ) {
                delete_option('inpost_italy_organisation' ); // delete previous settings
                update_option('inpost_italy_api_limit_connection', time() ); // set time for limit API connection retry
                $ping = inpost_italy_api()->ping();
            }

		}

	}

endif;
