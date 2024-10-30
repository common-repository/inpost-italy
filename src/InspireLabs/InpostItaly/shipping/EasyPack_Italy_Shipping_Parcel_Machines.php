<?php

namespace InspireLabs\InpostItaly\shipping;

use Exception;
use InspireLabs\InpostItaly\admin\EasyPack_Italy_Product_Shipping_Method_Selector;
use InspireLabs\InpostItaly\EasyPack_Italy;
use InspireLabs\InpostItaly\EasyPack_Italy_Helper;
use InspireLabs\InpostItaly\EasyPack_Italy_API;
use InspireLabs\InpostItaly\Geowidget_v4;
use InspireLabs\InpostItaly\shipx\models\shipment\ShipX_Shipment_Model;
use InspireLabs\InpostItaly\shipx\models\shipment\ShipX_Shipment_Parcel_Dimensions_Model;
use InspireLabs\InpostItaly\shipx\models\shipment\ShipX_Shipment_Parcel_Model;
use InspireLabs\InpostItaly\shipx\services\shipment\ShipX_Shipment_Service;
use ReflectionException;
use WC_Shipping_Method;
use InspireLabs\InpostItaly\EmailFilters\TrackingInfoEmail;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * EasyPack Shipping Method Parcel Machines
 *
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'EasyPack_Italy_Shipping_Parcel_Machines' ) ) {
	class EasyPack_Italy_Shipping_Parcel_Machines extends WC_Shipping_Method {

		static $logo_printed;

		static $setup_hooks_once = false;

		const SERVICE_ID = ShipX_Shipment_Model::SERVICE_INPOST_LOCKER_STANDARD;

		const NONCE_ACTION = self::SERVICE_ID;

		static $prevent_duplicate = [];

		static $review_order_after_shipping_once = false;

		static $woocommerce_checkout_after_order_review_once = false;

        public $ignore_discounts;
		
		protected $free_shipping_cost;
		protected $flat_rate;
		protected $cost_per_order;
		protected $based_on;

		/**
		 * Constructor for shipping class
		 *
		 * @access public
		 * @return void
		 */
		public function __construct( $instance_id = 0 ) {
            parent::__construct();

			$this->instance_id = absint( $instance_id );
			$this->supports    = [
				'shipping-zones',
				'instance-settings',
			];

			$this->id = 'easypack_italy_parcel_machines';
			$this->method_description
			          = esc_html__( 'Allow customers to pick up orders themselves. By default, when using local pickup store base taxes will apply regardless of customer address.',
				'inpost-italy' );

			$this->method_title = esc_html__( 'InPost Point 24/7', 'inpost-italy' );
			$this->init();


		}


		/**
		 * Init your settings
		 *
		 *
		 * @access public
		 * @return void
		 */
		function init() {

			$this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings
			$this->init_settings(); // This is part of the settings API. Loads settings you previously init.
			$this->title          = $this->get_option( 'title' );
			$this->free_shipping_cost
			                      = $this->get_option( 'free_shipping_cost' );
			$this->flat_rate      = $this->get_option( 'flat_rate' );
			$this->cost_per_order = $this->get_option( 'cost_per_order' );
			$this->based_on       = $this->get_option( 'based_on' );

			$this->tax_status = $this->get_option( 'tax_status' );

            $this->ignore_discounts = $this->get_option( 'apply_minimum_order_rule_before_coupon' );

			$this->setup_hooks_once();
		}

		private function setup_hooks_once() {

            inpost_italy_helper()->include_inline_css();

            add_action( 'woocommerce_review_order_after_shipping',
                [ $this, 'woocommerce_review_order_after_shipping' ] );

			add_action( 'woocommerce_update_options_shipping_' . $this->id,
				[ $this, 'process_admin_options' ] );

			add_action( 'woocommerce_checkout_update_order_meta',
				[ $this, 'woocommerce_checkout_update_order_meta' ] );

			add_action( 'woocommerce_checkout_process', [ $this, 'woocommerce_checkout_process' ] );

			add_action( 'save_post', [ $this, 'save_post' ] );

			add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ], 10, 2 );

			add_filter( 'woocommerce_cart_shipping_method_full_label',
				[ $this, 'woocommerce_cart_shipping_method_full_label' ], 10, 2 );

			add_filter( 'woocommerce_order_shipping_to_display_shipped_via',
				[ $this, 'woocommerce_order_shipping_to_display_shipped_via' ], 10, 2 );

			add_filter( 'woocommerce_my_account_my_orders_actions',
				[ $this, 'woocommerce_my_account_my_orders_actions' ], 10, 2 );


			add_filter( 'woocommerce_order_shipping_to_display',
				[ $this, 'woocommerce_order_shipping_to_display' ], 9999, 3 );

		}

		public function admin_options() {
			?>
            <table class="form-table">
				<?php $this->generate_settings_html(); ?>
            </table>
			<?php
		}

		public function generate_rates_html( $key, $data ) {
			$rates = get_option( 'inpost_italy_' . $this->id . '_' . $this->instance_id . '_rates', [] );

			if( !is_array( $rates ) ) {
                $rates = array();
            }

			ob_start();
			include( 'views/html-rates.php' );

			return ob_get_clean();
		}


		public function init_form_fields() {

			$settings = [
				[
					'title'       => esc_html__( 'General settings', 'inpost-italy' ),
					'type'        => 'title',
					'description' => '',
					'id'          => 'section_general_settings',
				],
				'logo_upload'        => [
					'name'  => esc_html__( 'Change logo', 'inpost-italy' ),
					'title' => esc_html__( 'Upload custom logo', 'inpost-italy' ),
					'type'  => 'logo_upload',
					'id'    => 'logo_upload',
				],
				'title'              => [
					'title'    => esc_html__( 'Method title', 'inpost-italy' ),
					'type'     => 'text',
					'default'  => esc_html__( 'InPost Locker 24/7', 'inpost-italy' ),
					'desc_tip' => false,
				],
				'free_shipping_cost' => [
					'title'             => esc_html__( 'Free shipping', 'inpost-italy' ),
					'type'              => 'number',
					'custom_attributes' => [
						'step' => 'any',
						'min'  => '0',
					],
					'default'           => '',
					'desc_tip'          => esc_html__( 'Enter the amount of the order from which the shipping will be free. ',
                        'inpost-italy' ),
					'placeholder'       => '0.00',
				],
                'apply_minimum_order_rule_before_coupon' => [
                    'title'       => esc_html__( 'Coupons discounts', 'inpost-italy' ),
                    'label'       => esc_html__( 'Apply minimum order rule before coupon discount', 'inpost-italy' ),
                    'type'        => 'checkbox',
                    'description' => esc_html__( 'If checked, free shipping would be available based on pre-discount order amount.', 'inpost-italy' ),
                    'default'     => 'no',
                    'desc_tip'    => true,
                ],
				'flat_rate'          => [
					'title'   => esc_html__( 'Flat rate', 'inpost-italy' ),
					'type'    => 'checkbox',
					'label'   => esc_html__( 'Set a flat-rate shipping fee for the entire order.', 'inpost-italy' ),
					'class'   => 'easypack_flat_rate',
					'default' => 'yes',
				],
				'cost_per_order'     => [
					'title'             => esc_html__( 'Cost per order', 'inpost-italy' ),
					'type'              => 'number',
					'custom_attributes' => [
						'step' => 'any',
						'min'  => '0',
					],
					'class'             => 'easypack_cost_per_order',
					'default'           => '',
					'desc_tip'          => esc_html__( 'Set a flat-rate shipping for all orders'
						, 'inpost-italy' ),
					'placeholder'       => '0.00',
				],
				'tax_status'         => [
					'title'   => esc_html__( 'Tax status', 'inpost-italy' ),
					'type'    => 'select',
					'class'   => 'wc-enhanced-select',
					'default' => 'none',
					'options' => [
						'none'    => esc_html( _x( 'None', 'Tax status', 'inpost-italy' ) ),
						'taxable' => esc_html__( 'Taxable', 'inpost-italy' ),
					],
				],

				[
					'title'       => esc_html__( 'Rates table', 'inpost-italy' ),
					'type'        => 'title',
					'description' => '',
					'id'          => 'section_general_settings',
				],
				'based_on'           => [
					'title'    => esc_html__( 'Based on', 'inpost-italy' ),
					'type'     => 'select',
					'desc_tip' => esc_html__( 'Select the method of calculating shipping cost. If the cost of shipping is to be calculated based on the weight of the cart and the products do not have a defined weight, the cost will be calculated incorrectly.',
                        'inpost-italy' ),
                    'description' => sprintf( '<b id="easypack_dimensions_warning" style="color:red;display:none">%1s</b> %1s',
                        esc_html__('Attention!', 'inpost-italy'),
                        esc_html__('Set the dimension in the settings of each product. The default value is size \'S\'', 'inpost-italy' )

                                    ),
					'class'    => 'wc-enhanced-select easypack_based_on',
					'options'  => [
						'price'  => esc_html__( 'Price', 'inpost-italy' ),
						'weight' => esc_html__( 'Weight', 'inpost-italy' ),
                        'size'   => esc_html__( 'Size (S, M, L)', 'inpost-italy' ),
					],
				],
				'rates'              => [
					'title'    => '',
					'type'     => 'rates',
					'class'    => 'easypack_rates',
					'default'  => '',
					'desc_tip' => '',
				],

                'gabaryt_a'     => [
                    'title'             => esc_html__( 'Size S', 'inpost-italy' ),
                    'type'              => 'number',
                    'custom_attributes' => [ 'step' => 'any', 'min' => '0' ],
                    'class'             => 'easypack_gabaryt_a',
                    'default'           => '',
                    'desc_tip'          => esc_html__( 'Set a flat-rate shipping for size S', 'inpost-italy' ),
                    'placeholder'       => '0.00',
                ],

                'gabaryt_b'     => [
                    'title'             => esc_html__( 'Size M', 'inpost-italy' ),
                    'type'              => 'number',
                    'custom_attributes' => [ 'step' => 'any', 'min' => '0' ],
                    'class'             => 'easypack_gabaryt_b',
                    'default'           => '',
                    'desc_tip'          => esc_html__( 'Set a flat-rate shipping for size M', 'inpost-italy' ),
                    'placeholder'       => '0.00',
                ],

                'gabaryt_c'     => [
                    'title'             => esc_html__( 'Size L', 'inpost-italy' ),
                    'type'              => 'number',
                    'custom_attributes' => [ 'step' => 'any', 'min' => '0' ],
                    'class'             => 'easypack_gabaryt_c',
                    'default'           => '',
                    'desc_tip'          => esc_html__( 'Set a flat-rate shipping for size L', 'inpost-italy' ),
                    'placeholder'       => '0.00',
                ],
			];
			$this->instance_form_fields = $settings;
			$this->form_fields          = $settings;


		}


		public function generate_logo_upload_html( $key, $data ) {
			$field_key = $this->get_field_key( $key );

			$defaults = [
				'title'             => 'Upload custom logo',
				'disabled'          => false,
				'class'             => '',
				'css'               => '',
				'placeholder'       => '',
				'type'              => 'text',
				'desc_tip'          => false,
				'description'       => '',
				'custom_attributes' => [],
			];

			$data = wp_parse_args( $data, $defaults );

			ob_start();
			?>
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?><?php echo esc_html( $this->get_tooltip_html( $data ) ); // WPCS: XSS ok.
						?></label>
                </th>
                <td class="forminp">
                    <fieldset>
                        <legend class="screen-reader-text">
                            <span><?php echo wp_kses_post( $data['title'] ); ?></span>
                        </legend>
                        <img src='<?php echo esc_attr( $this->get_instance_option( $key ) ); ?>'
                             style='width: 60px; height: auto; background-size: cover; display: <?php echo !empty( $this->get_instance_option( $key ) ) ? 'block' : 'none'; ?>; margin-bottom: 10px;'
                             id='woo-inpost-logo-preview'>
                        <ul id="woo-inpost-logo-action" style='display: <?php echo !empty( $this->get_instance_option( $key ) ) ? 'block' : 'none'; ?>;'>
                            <li>
                                <a id="woo-inpost-logo-delete" href="#" title="Delete image">
                                    <?php echo esc_html__( 'Delete', 'inpost-italy' ); ?>
                                </a>
                            </li>
                        </ul>
                        <button class='woo-inpost-logo-upload-btn'>
                            <?php echo esc_html__( 'Upload', 'inpost-italy' ); ?>
                        </button>
                        <input class="input-text regular-input" type="hidden"
                               name="<?php echo esc_attr( $field_key ); ?>"
                               id="woocommerce_easypack_logo_upload"
                               style="<?php echo esc_attr( $data['css'] ); ?>"
                               value="<?php echo esc_attr( $this->get_instance_option( $key ) ); ?>"
                               placeholder="<?php echo esc_attr( $data['placeholder'] ); ?>"/>
						<?php echo wp_kses_post( $this->get_description_html( $data ) ); // WPCS: XSS ok.
						?>
                    </fieldset>
                </td>
            </tr>
			<?php

			return ob_get_clean();
		}


		public function add_rate( $args = [] ) {

			$args['meta_data'] = [ 'logo' => $this->get_instance_option( 'logo_upload' ) ];

			parent::add_rate( $args );
		}

		public function process_admin_options() {
			parent::process_admin_options();

			if ( isset( $_POST['rates'] ) && is_array( $_POST['rates'] ) ) {

                $save_rates = array();

                foreach( $_POST['rates'] as $key => $rate ) {
                    $key = (int) sanitize_text_field( $key );
                    $data = array_map( 'sanitize_text_field', $rate );
                    $save_rates[ $key ] = $data;
                }

				update_option( 'inpost_italy_' . $this->id . '_' . $this->instance_id . '_rates', $save_rates );
			}
		}

        public function calculate_shipping_free_shipping( $package ) {

            $total = WC()->cart->get_displayed_subtotal();

            if (WC()->cart->display_prices_including_tax()) {
                $total = $total - WC()->cart->get_discount_tax();
            }

            if ('no' === $this->ignore_discounts) {
                $total = $total - WC()->cart->get_discount_total();
            }


            if( (float) $this->free_shipping_cost > 0 ) {

                if ( (float) $this->free_shipping_cost <= $total
                ) {

                    $add_rate = [
                        'id'    => $this->get_rate_id(),
                        'label' => $this->title . ' ' .  esc_html__( '(free)', 'inpost-italy' ),
                        'cost'  => 0,
                    ];
                    $this->add_rate( $add_rate );

                    return true;
                }

            } else {

                $add_rate = [
                    'id'    => $this->get_rate_id(),
                    'label' => $this->title . ' ' .  esc_html__( '(free)', 'inpost-italy' ),
                    'cost'  => 0,
                ];
                $this->add_rate( $add_rate );

                return true;

            }

            return false;
        }

		public function calculate_shipping_flat( $package ) {

			if ( $this->flat_rate == 'yes' ) {

                if( (float) $this->cost_per_order > 0 ) {
                    $add_rate = [
                        'id' => $this->get_rate_id(),
                        'label' => $this->title,
                        'cost' => $this->cost_per_order,
                    ];
                    $this->add_rate($add_rate);

                } else {
                    $add_rate = [
                        'id'    => $this->get_rate_id(),
                        'label' => $this->title . ' ' .  esc_html__( '(free)', 'inpost-italy' ),
                        'cost'  => 0,
                    ];
                    $this->add_rate( $add_rate );
                }

				return true;
			}

			return false;
		}

        public function package_weight( $items ) {
            $weight = 0;
            foreach ( $items as $item ) {
                if( ! empty( $item['data']->get_weight() ) ) {
                    $weight += floatval( $item['data']->get_weight() ) * $item['quantity'];
                }
            }

            return $weight;
        }

		public function package_subtotal( $items ) {
			$subtotal = 0;
			foreach ( $items as $item ) {
				$subtotal += $item['line_subtotal']
				             + $item['line_subtotal_tax'];
			}

			return $subtotal;
		}

		/**
		 * @param unknown $package
		 *
		 */
		public function calculate_shipping_table_rate( $package ) {

            // based on gabaryt
            if ( $this->based_on == 'size' ) {

                $max_gabaryt = $this->get_max_gabaryt();
                $max_gabaryt = $this->get_max_gabaryt( $package );
                $cost = $this->instance_settings[ 'gabaryt_' . $max_gabaryt ];

                $add_rate = [
                    'id'    => $this->get_rate_id(),
                    'label' => $this->title,
                    'cost'  => $cost,
                    'package' => $package,
                ];
                $this->add_rate( $add_rate );

                return;
            }

			$rates = get_option( 'inpost_italy_' . $this->id . '_' . $this->instance_id . '_rates', [] );

            if( is_array( $rates ) ) {
                foreach ( $rates as $key => $rate ) {
                    if ( empty( $rates[$key]['min'] ) || trim( $rates[$key]['min'] ) == '' ) {
                        $rates[$key]['min'] = 0;
                    }
                    if ( empty( $rates[$key]['max'] ) || trim( $rates[$key]['max'] ) == '' ) {
                        $rates[$key]['max'] = PHP_INT_MAX;
                    }
                }
            }
			$value = 0;
			if ( $this->based_on == 'price' ) {
				$value = $this->package_subtotal( $package['contents'] );
			}
			if ( $this->based_on == 'weight' ) {
				$value = $this->package_weight( $package['contents'] );
			}
			foreach ( $rates as $rate ) {
				if ( floatval( $rate['min'] ) <= $value && floatval( $rate['max'] ) >= $value ) {

				    $add_rate = [
                        'id'    => $this->get_rate_id(),
						'label' => $this->title,
						'cost'  => $rate['cost'],
					];
					$this->add_rate( $add_rate );

					return;
				}
			}
		}

		/**
		 * @param array $package
		 */
		public function calculate_shipping( $package = [] ) {
			if ( inpost_italy_api()->normalize_country_code_for_inpost( $package['destination']['country'] )
			     == 'IT'
			) {

				if ( ! $this->calculate_shipping_free_shipping( $package ) ) {
					if ( ! $this->calculate_shipping_flat( $package ) ) {
						$this->calculate_shipping_table_rate( $package );
					}
				}
			}
		}

        public function woocommerce_checkout_process() {

            $chosen_shipping_methods = [];
            $at_least_one_physical_product = false;
            static $alert_shown;

            if ( is_object( WC()->session ) ) {
                $chosen_shipping_methods = WC()->session->get('chosen_shipping_methods' );
                $cart_contents = WC()->session->get('cart');

                $at_least_one_physical_product = inpost_italy_helper()->physical_goods_in_cart( $cart_contents );
            }

            if( ! empty( $chosen_shipping_methods ) && is_array ( $chosen_shipping_methods ) ) {
                // remove digit postfix (for example "easypack_parcel_machines:18") in method name
                foreach ( $chosen_shipping_methods as $key => $method ) {
                    $chosen_shipping_methods[$key] = inpost_italy_helper()->validate_method_name( $method );
                }

                $method_name = inpost_italy_helper()->validate_method_name( $this->id );

                if ( in_array( $method_name, $chosen_shipping_methods ) ) {

                    if ( false === $this->is_method_courier() && $at_least_one_physical_product ) {

                        if ( empty( $_POST['parcel_machine_id'] ) ) {
                            if ( ! $alert_shown ) {
                                $alert_shown = true;

                                if( 'it-IT' === get_bloginfo("language") ) {
                                    wc_add_notice(__('Il punto del pacco deve essere scelto', 'inpost-italy'), 'error');
                                    throw new Exception( "InPost Italia" );

                                } else {
                                    wc_add_notice(__('Parcel point must be choosen.', 'inpost-italy'), 'error');
                                    throw new Exception( "InPost Italy" );
                                }
                            }
                        } else {
                            WC()->session->set( 'parcel_machine_id', sanitize_text_field($_POST['parcel_machine_id']) );
                        }
                    }
                }
            }

        }

		public function woocommerce_checkout_update_order_meta( $order_id ) {
            if ( isset($_POST['parcel_machine_id']) && !empty($_POST['parcel_machine_id']) ) {
                update_post_meta( $order_id, '_parcel_machine_id', sanitize_text_field( $_POST['parcel_machine_id'] ) );

                if( 'yes' === get_option('woocommerce_custom_orders_table_enabled') ) {
                    $order = wc_get_order( $order_id );
                    if ( $order && !is_wp_error($order) ) {
                        $order->update_meta_data('_parcel_machine_id', sanitize_text_field($_POST['parcel_machine_id']));
                        $order->save();
                    }
                }
            }

            if ( isset($_POST['parcel_machine_desc']) && !empty($_POST['parcel_machine_desc']) ) {
                update_post_meta( $order_id, '_parcel_machine_desc', sanitize_text_field( $_POST['parcel_machine_desc'] ) );

                if( 'yes' === get_option('woocommerce_custom_orders_table_enabled') ) {
                    $order = wc_get_order( $order_id );
                    if ( $order && !is_wp_error($order) ) {
                        $order->update_meta_data('_parcel_machine_desc', sanitize_text_field($_POST['parcel_machine_desc']));
                        $order->save();
                    }
                }
            }

		}

		public function save_post( $post_id ) {

			// Check if our nonce is set.
			if ( ! isset( $_POST['wp_nonce'] ) ) {
				return;
			}
			// Verify that the nonce is valid.
            if ( ! wp_verify_nonce( sanitize_text_field(wp_unslash($_POST['wp_nonce'])), self::NONCE_ACTION ) ) {
                return;
            }
			// If this is an autosave, our form has not been submitted, so we don't want to do anything.
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}

			$status = get_post_meta( $post_id, '_easypack_status', true );
			if ( $status == '' ) {
				$status = 'new';
			}

            if ( $status == 'new' ) {

                if( isset( $_POST['parcel_machine_id'] ) ) {
                    $parcel_machine_id   = sanitize_text_field( $_POST['parcel_machine_id'] );
                    update_post_meta( $post_id, '_parcel_machine_id', $parcel_machine_id );
                }

                if( isset( $_POST['parcel_machine_desc'] ) ) {
                    $parcel_machine_desc = sanitize_text_field( $_POST['parcel_machine_desc'] );
                    update_post_meta( $post_id, '_parcel_machine_desc', $parcel_machine_desc );
                }

                $parcels = isset( $_POST['parcel'] )
                    ? (array) sanitize_text_field($_POST['parcel'])
                    : (array) get_option( 'easypack_italy_default_package_size', 'small' );
                $parcels = array_map( 'sanitize_text_field', $parcels );

                $easypack_pacels = [];
                if( ! empty ( $parcels ) ) {
                    foreach( $parcels as $parcel ) {
                        $easypack_pacels[] = ['package_size' => $parcel];
                    }
                }

                update_post_meta( $post_id, '_easypack_parcels', $easypack_pacels );

                $reference_number = isset( $_POST['reference_number'] ) ? sanitize_text_field( $_POST['reference_number'] ) : $post_id;
                update_post_meta( $post_id, '_easypack_reference_number', $reference_number );

            }

		}

		public function get_logo() {

			$custom_logo = null;

			if ( empty( $custom_logo ) ) {
				return '<img style="height:22px; float:right;" src="'
				       . untrailingslashit( inpost_italy()->getPluginImages()
				                            . 'logo/small/white.png"/>' );
			} else {
				return '<img style="height:22px; float:right;" src="'
				       . untrailingslashit( $custom_logo );
			}

		}

		public function add_meta_boxes( $post_type, $post ) {

            $order_id = null;

            if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
                // HPOS usage is enabled.
                if ( is_a( $post, 'WC_Order' ) ) {
                    $order_id = $post->get_id();
                }

            } else {
                // Traditional orders are in use.
                if ( is_object( $post ) && $post->post_type == 'shop_order' ) {
                    $order_id = $post->ID;
                }

            }

            if( $order_id ) {
                $order = wc_get_order( $order_id );
                if ( $order->has_shipping_method( $this->id ) ) {
                    add_meta_box( 'easypack_italy_parcel_machines',
                        esc_html__( 'InPost Italy', 'inpost-italy' ) . $this->get_logo(),
                        [ $this, 'order_metabox' ],
                        null,
                        'side',
                        'default'
                    );
                }
            }


		}

		public function order_metabox( $post ) {
			self::order_metabox_content( $post );
		}

		/**
		 * @throws ReflectionException
		 */
		public static function ajax_create_package() {
			$ret = [ 'status' => 'ok' ];

			$shipment_model   = self::ajax_create_shipment_model();
			$order_id         = $shipment_model->getInternalData()->getOrderId();
			$shipment_service = EasyPack_Italy::EasyPack_Italy()->get_shipment_service();
			$status_service   = EasyPack_Italy::EasyPack_Italy()->get_shipment_status_service();
			$shipment_array   = $shipment_service->shipment_to_array( $shipment_model );

			$shipment_array = EasyPack_Italy_Shipping_Parcel_Machines::validate_request_italy( $shipment_array );

            \wc_get_logger()->debug( 'Debug, data to API: ' . $order_id, array( 'source' => 'inpost-it-log' ) );
            \wc_get_logger()->debug( print_r( $shipment_array, true), array( 'source' => 'inpost-it-log' ) );
			//die();

			$label_url = '';

			try {
				update_post_meta( $shipment_model->getInternalData()->getOrderId(),
					'_easypack_parcel_create_args',
					$shipment_array );

				$response = inpost_italy_api()->customer_parcel_create( $shipment_array );
				
				\wc_get_logger()->debug( 'Debug, response API: ', array( 'source' => 'inpost-it-log' ) );
				\wc_get_logger()->debug( print_r( $response, true), array( 'source' => 'inpost-it-log' ) );


				$internal_data = $shipment_model->getInternalData();
				$internal_data->setInpostId( $response['id'] );
				$internal_data->setStatus( $response['status'] );
				$internal_data->setStatusTitle( $status_service->getStatusTitle( $response['status'] ) );
				$internal_data->setStatusDescription( $status_service->getStatusDescription( $response['status'] ) );

				$internal_data->setStatusChangedTimestamp( time() );

				$internal_data->setCreatedAt( time() );
				$internal_data->setUrl( $response['href'] );
				$shipment_model->setInternalData( $internal_data );


				$label_url = null;
				$tracking_for_email = '';

				for ( $i = 0; $i < 3; $i ++ ) {
					sleep( 1 );
					$search_in_api = inpost_italy_api()->customer_parcel_get_by_id( $shipment_model->getInternalData()->getInpostId() );
					if ( isset( $search_in_api['items'][0]['tracking_number'] ) ) {
						$shipment_model->getInternalData()->setTrackingNumber( $search_in_api['items'][0]['tracking_number'] );
						break;
					}

                    // ?? API changed ?? key: items => parcels
                    if ( isset( $search_in_api['parcels'][0]['tracking_number'] ) ) {
                        $tracking_for_email = $search_in_api['parcels'][0]['tracking_number'];
                        $shipment_model->getInternalData()->setTrackingNumber( $search_in_api['parcels'][0]['tracking_number'] );
                        break;
                    }
				}

				$internal_data = $shipment_model->getInternalData();
				$internal_data->setLabelUrl( $label_url );
				$shipment_model->setInternalData( $internal_data );
				//$shipment_service->update_shipment_to_db( $shipment_model );

				update_post_meta( $order_id, '_easypack_status', 'created' );
				if( isset( $response['id'] ) && ! empty( $response['id'] ) ) {
                    update_post_meta( $order_id, '_easypack_inpost_id', sanitize_text_field( $response['id'] ) );
                }

				if( ! empty( $tracking_for_email ) ) {
                    update_post_meta( $order_id, '_easypack_parcel_tracking', $tracking_for_email );
                }

				//zapisz koszt przesyłki do przesyłki
				$price_calculator = inpost_italy()->get_shipment_price_calculator_service();

				$shipment_service->update_shipment_to_db( $shipment_model );
			} catch ( Exception $e ) {
				
				\wc_get_logger()->debug( 'Debug, Exception: ', array( 'source' => 'inpost-it-log' ) );
				\wc_get_logger()->debug( print_r( $e->getMessage(), true), array( 'source' => 'inpost-it-log' ) );
				
				$ret['status'] = 'error';
				$ret['message']	= esc_html__( 'There are some errors. Please fix it: <br>', 'inpost-italy' ) . esc_html( $e->getMessage() );
			}

			if ( $ret['status'] == 'ok' ) {
                $order = wc_get_order( $order_id );
                $tracking_url = inpost_italy_helper()->get_tracking_url();

                $order->add_order_note(
					__( 'Shipment created', 'inpost-italy' ), false
				);

                if( isset( $_POST['action']) && $_POST['action'] === 'easypack_italy_bulk_create_shipments' ) {
                    if( $tracking_for_email ) {
                        $ret['tracking_number'] = $tracking_for_email;
                    } else {
                        $ret['api_status'] = $response['status'];
                    }
                } else {
                    $ret['content'] = self::order_metabox_content( get_post( $order_id ), false, $shipment_model );
                }

				// send email to buyer with tracking details
				( new TrackingInfoEmail() )->send_tracking_info_email( $order, $tracking_url,  $tracking_for_email );
			}
			echo wp_json_encode( $ret );
			wp_die();
		}

		/**
		 * @param $post
		 * @param bool $output
		 * @param ShipX_Shipment_Model|null $shipment
		 *
		 * @return string
		 * @throws Exception
		 */
		public static function order_metabox_content(
			$post,
			$output = true,
			$shipment = null
		) {
			$organization_service = EasyPack_Italy::EasyPack_Italy()->get_organization_service();

			$organization = $organization_service->query_organisation();

			if ( ! $organization ) {
				$services_for_organization = [];
			} else {
				$services_for_organization = $organization->getServices();
			}


			if ( ! $output ) {
				ob_start();
			}
			$shipment_service = EasyPack_Italy::EasyPack_Italy()->get_shipment_service();

			if ( is_a( $post, 'WC_Order' ) ) {
                $order_id = $post->get_id();
            } else {
                $order_id = $post->ID;
            }

			if ( false === $shipment instanceof ShipX_Shipment_Model ) {
				$shipment = $shipment_service->get_shipment_by_order_id( $order_id );
			}

			if ( $shipment instanceof ShipX_Shipment_Model
			     && false === $shipment_service->is_shipment_match_to_current_api( $shipment )
			) {
				wp_nonce_field( self::NONCE_ACTION, 'wp_nonce' );
				$wrong_api_env = true;
				include( 'views/html-order-metabox-parcel-machines.php' );
				if ( ! $output ) {
					$out = ob_get_clean();

					return $out;
				}

				return '';
			}
			$wrong_api_env = false;

			$order = wc_get_order( $order_id );

			/**
			 * id, template, dimensions, weight, tracking_number, is_not_standard
			 */

			if ( null !== $shipment ) {
				$parcels      = $shipment->getParcels();
				$tracking_url = $shipment->getInternalData()->getTrackingNumber();
				//$stickers_url = $shipment->getInternalData()->getLabelUrl();

				if ( true === $output ) {
					$status_srv = inpost_italy()->get_shipment_status_service();
					$status_srv->refreshStatus( $shipment );
				}

				$status            = $shipment->getInternalData()->getStatus();
				$parcel_machine_id = $shipment->getCustomAttributes()->getTargetPoint();
				$send_method       = $shipment->getCustomAttributes()->getSendingMethod();
				$disabled          = true;
			} else {
				$package_sizes_display = inpost_italy()->get_package_sizes_display();
				$parcels = [];
				$parcel  = new ShipX_Shipment_Parcel_Model();
				$parcel->setTemplate( get_option( 'easypack_italy_default_package_size', 'small' ) );
				$parcels[] = $parcel;

				$parcel_machine_from_order = get_post_meta( $order_id, '_parcel_machine_id', true );
				$parcel_machine_id = ! empty( $parcel_machine_from_order )
					? $parcel_machine_from_order
					: get_option( 'easypack_default_machine_id' );

				$tracking_url = false;
				$status       = 'new';
				$send_method  = get_option( 'easypack_italy_default_send_method', 'parcel_machine' );
				$disabled     = false;
			}
			$package_sizes = inpost_italy()->get_package_sizes();

			$send_method_disabled = false;

            $send_methods = [
                'parcel_machine' => esc_html__( 'Parcel point', 'inpost-italy' ),
                'any_point'        => esc_html__( 'Any point', 'inpost-italy' )
            ];

			$selected_service = $shipment_service->get_customer_service_name_by_id( self::SERVICE_ID );
			include( 'views/html-order-metabox-parcel-machines.php' );

			wp_nonce_field( self::NONCE_ACTION, 'wp_nonce' );
			if ( ! $output ) {
				$out = ob_get_clean();

				return $out;
			}
		}


		/**
		 * @return ShipX_Shipment_Model
		 */
		public static function ajax_create_shipment_model() {
			$shipmentService = EasyPack_Italy::EasyPack_Italy()->get_shipment_service();

            $order_id = sanitize_text_field( $_POST['order_id'] );

            // if Bulk create shipments
            if( isset( $_POST['action']) && $_POST['action'] === 'easypack_italy_bulk_create_shipments' ) {

                $parcel_machine_id = get_post_meta( $order_id, '_parcel_machine_id', true );

                $parcels = get_post_meta( $order_id, '_easypack_parcels', true )
                    ? get_post_meta( $order_id, '_easypack_parcels', true )
                    : array( get_option( 'easypack_italy_default_package_size' ) );

                $reference_number = get_post_meta( $order_id, '_easypack_reference_number', true )
                    ? get_post_meta( $order_id, '_easypack_reference_number', true )
                    : $order_id;

            } else {
                $parcel_machine_id = isset( $_POST['parcel_machine_id'] ) ? sanitize_text_field( $_POST['parcel_machine_id'] ) : '';
                $parcels = isset( $_POST['parcels'] ) ? array_map( 'sanitize_text_field', $_POST['parcels'] ) : array();
                $reference_number = isset( $_POST['reference_number'] ) ? sanitize_text_field( $_POST['reference_number'] ) : $order_id;
            }

			$send_method = 'parcel_machine'; // will be converted later in function to "parcel_locker"

            $shipment = $shipmentService->create_shipment_object_by_shiping_data(
                $parcels,
                (int) $order_id,
                $send_method,
				self::SERVICE_ID,
				[],
                $parcel_machine_id,
				null,
				null,
                $reference_number
			);
			$shipment->getInternalData()->setOrderId( (int) $order_id );

			return $shipment;
		}


		public function woocommerce_cart_shipping_method_full_label( $label, $method ) {

			if ( in_array( $this->id, self::$prevent_duplicate ) ) {
				return $label;
			}

			if ( $method->id === $this->id ) {

				if ( ! ( $method->cost > 0 ) ) {
					$label .= ': ' . wc_price( 0 );
				}
				self::$prevent_duplicate[] = $this->id;

				return $label;
			}


			return $label;
		}

		public function woocommerce_order_shipping_to_display_shipped_via( $via, $order ) {

			if ( self::$logo_printed === 1 ) {
				return $via;
			}

			if ( $order->has_shipping_method( $this->id ) ) {
				$img = ' <span class="easypack-shipping-method-logo" 
                               style="display: inline;">
                               <img style="max-width: 100px; max-height: 40px;	display: inline; border:none;" src="'
					                  . inpost_italy()->getPluginImages()
					                  . 'logo/small/white.png" />
                         <span>';
				$via .= $img;
				self::$logo_printed = 1;
			}

			return $via;
		}

		/**
		 * @param $shipping
		 * @param $order
		 * @param $tax_display
		 *
		 * @return mixed|string
		 */
		public function woocommerce_order_shipping_to_display( $shipping, $order, $tax_display ) {
			if ( $order->has_shipping_method( $this->id ) ) {
				if ( ! ( 0 < abs( (float) $order->get_shipping_total() ) ) && $order->get_shipping_method() ) {
                    if( ! stripos( $shipping, ':' ) ) {
                        $shipping .= ': ' . wc_price( 0 );
                    }
				}

				return $shipping;
			}

			return $shipping;
		}

		public function woocommerce_my_account_my_orders_actions( $actions, $order ) {
			if ( $order->has_shipping_method( $this->id ) ) {
				$status = get_post_meta( $order->get_id(), '_easypack_status', true );

				$tracking_url = false;
				$fast_returns = get_option('easypack_fast_return');

                if ( $status != 'new' ) {
					$tracking_url = inpost_italy_helper()->get_tracking_url();
					$tracking_number = get_post_meta( $order->get_id(), '_easypack_parcel_tracking', true );
					$tracking_url = trim( $tracking_url, ',' );
				}

				if ( $tracking_number ) {
					$actions['easypack_tracking'] = [
						'url'  =>  esc_url( $tracking_url . $tracking_number ) ,
						'name' => esc_html__( 'Track shipment', 'inpost-italy' ),
					];
				}

                if ( !empty( $fast_returns ) ) {
                    $actions['fast_return'] = [
                        'url' => get_option('easypack_fast_return'),
                        'name' => esc_html__('Fast return', 'inpost-italy'),
                    ];
                }
			}

			return $actions;
		}

		/**
		 * @return ShipX_Shipment_Service
		 */
		public function getShipmentService() {
			return $this->shipment_service;
		}

		/**
		 * @param ShipX_Shipment_Service $shipment_service
		 */
		public function setShipmentService( $shipment_service ) {
			$this->shipment_service = $shipment_service;
		}

		/**
		 * @return bool
		 */
		protected function is_method_courier() {
			return $this->id === 'easypack_shipping_courier'
			       || $this->id === 'easypack_italy_shipping_courier_c2c';
		}

		/**
		 * @param int $wc_order_id
		 *
		 * @return ShipX_Shipment_Parcel_Dimensions_Model
		 */
		protected static function get_single_product_dimensions( int $wc_order_id ): ShipX_Shipment_Parcel_Dimensions_Model {
			$order = wc_get_order( $wc_order_id );

			$items = $order->get_items();

			if ( count( $items ) > 1 ) {
				return new ShipX_Shipment_Parcel_Dimensions_Model();
			}

			foreach ( $order->get_items() as $item_id => $item ) {
				$product_id = $item->get_product_id();
				$product    = wc_get_product( $product_id );

				if ( $item->get_quantity() > 1 ) {
					return new ShipX_Shipment_Parcel_Dimensions_Model();
				}

				$height = (float) $product->get_height();
				$width  = (float) $product->get_width();
				$length = (float) $product->get_length();

				if ( $height > 0 || $width > 0 || $length > 0 ) {
					$dims = new ShipX_Shipment_Parcel_Dimensions_Model();
					$dims->setHeight(
						$height
					);
					$dims->setWidth(
						$width
					);
					$dims->setLength(
						$length
					);
					$dims->setUnit( 'cm' );

					return $dims;
				}


			}

			return new ShipX_Shipment_Parcel_Dimensions_Model();
		}

        /**
         * Get max parcel size among the products in cart
         */
        public function get_max_gabaryt( $package ) {

            if ( isset( $package['contents'] ) && ! empty( $package['contents'] ) )  {

                $possible_gabaryts = array();

                foreach ( $package['contents'] as $cart_item_key => $cart_item ) {
                    $product_id = $cart_item['product_id'];
                    $possible_gabaryts[] = get_post_meta( $product_id, EasyPack_Italy::ATTRIBUTE_PREFIX . '_parcel_dimensions', true );

                }

                if ( ! empty( $possible_gabaryts ) ) {
                    if ( in_array('large', $possible_gabaryts ) ) {
                        return 'c';
                    }
                    if ( in_array('medium', $possible_gabaryts ) ) {
                        return 'b';
                    }

                }
            }
            // by default
            return 'a';

        }


        private static function validate_request_italy( $shipment_array ) {
			
			$flow_type = get_option( 'easypack_italy_flow_type' );

            // Receiver
            unset( $shipment_array['receiver']['name'] );
            unset( $shipment_array['receiver']['address']['country_code'] );
            unset( $shipment_array['receiver']['address']['id'] );

            // Sender
            $shipment_array['sender'] = [
                    'company_name' => get_option( 'easypack_italy_sender_company_name' ),
                    'email'        => get_option( 'easypack_italy_sender_email' ),
                    'phone'        => str_replace(' ', '', get_option( 'easypack_italy_sender_phone' ) ),
                    'address'      => [
                        'city'              => get_option( 'easypack_italy_sender_city' ),
                        'post_code'         => get_option( 'easypack_italy_sender_post_code' ),
                        'street'            => get_option( 'easypack_italy_sender_street' ),
                        'building_number'   => get_option( 'easypack_italy_sender_building_no' ),
                    ]
            ];
			
			if( $flow_type === 'A2L' ) {
				// Sender Pick UP in address
				$shipment_array['sender'] = [
						'company_name' => get_option( 'easypack_italy_sender_company_name' ),
						'email'        => get_option( 'easypack_italy_sender_email' ),
						'phone'        => str_replace(' ', '', get_option( 'easypack_italy_sender_phone' ) ),
						'address'      => [
							'city'              => get_option( 'easypack_italy_pickup_city' ),
							'post_code'         => get_option( 'easypack_italy_pickup_post_code' ),
							'street'            => get_option( 'easypack_italy_pickup_street' ),
							'building_number'   => get_option( 'easypack_italy_pickup_building_no' ),
						]
				];
			}

            // Parcels
            unset( $shipment_array['parcels'][0]['is_non_standard'] );
            // simple variant
            if( isset( $_POST['action']) && $_POST['action'] === 'easypack_italy_bulk_create_shipments' ) {

                $template = isset( $shipment_array['parcels'][0]['template'] )
                    ? $shipment_array['parcels'][0]['template']
                    : get_option( 'easypack_italy_default_package_size' );

                $shipment_array['parcels'] = [];
                $shipment_array['parcels']['template'] = $template;
            }

            // Service
            unset( $shipment_array['custom_attributes']['dropoff_point'] );
            unset( $shipment_array['custom_attributes']['allegro_transaction_id'] );
			if( $flow_type === 'A2L' ) {
				unset( $shipment_array['custom_attributes']['sending_method'] );
			}

            // External_customer_id
            $shipment_array['external_customer_id'] = get_option( 'easypack_organization_id_italy' );
            $shipment_array['comments'] = 'woocommerce';

            // Remove ombigous
            unset($shipment_array['cod']);
            unset($shipment_array['insurance']);
            unset($shipment_array['isReturn']);
            unset($shipment_array['only_choice_of_offer']);
            unset($shipment_array['internal_data']);

            return $shipment_array;

        }


        /**
         * Output template with Choose Parcel Locker button
         */
        public function woocommerce_review_order_after_shipping() {

            if( get_option( 'easypack_italy_map_debug' ) === 'yes') {

                $chosen_shipping_methods = [];
                $parcel_machine_id = '';

                if (is_object(WC()->session)) {
                    $chosen_shipping_methods = WC()->session->get('chosen_shipping_methods');

                    // remove digit postfix (for example "easypack_parcel_machines:18") in method name
                    foreach ($chosen_shipping_methods as $key => $method) {
                        $chosen_shipping_methods[$key] = inpost_italy_helper()->validate_method_name($method);
                    }

                    $parcel_machine_id = WC()->session->get('parcel_machine_id');
                }

                $method_name = inpost_italy_helper()->validate_method_name($this->id);

                if (!empty($chosen_shipping_methods) && is_array($chosen_shipping_methods)) {
                    if (in_array($method_name, $chosen_shipping_methods) ) {
                        if (!self::$review_order_after_shipping_once) {
                            $args = ['parcel_machines' => []];
                            $args['parcel_machine_id'] = $parcel_machine_id;
                            $args['shipping_method_id'] = $this->id;
                            wc_get_template(
                                'checkout/easypack-review-order-after-shipping.php',
                                $args,
                                '',
                                inpost_italy()->getTemplatesFullPath()
                            );

                            self::$review_order_after_shipping_once = true;
                        }
                    }
                }

            }
        }
	}


}
