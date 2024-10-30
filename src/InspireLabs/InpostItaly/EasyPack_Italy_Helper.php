<?php

namespace InspireLabs\InpostItaly;

use InspireLabs\InpostItaly\EasyPack_Italy;
use Exception;
use InspireLabs\InpostItaly\shipping\EasyPack_Italy_Shipping_Parcel_Machines;
use InspireLabs\InpostItaly\shipx\models\courier_pickup\ShipX_Dispatch_Order_Model;
use Requests_Utility_CaseInsensitiveDictionary;

/**
 * EasyPack Helper
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'EasyPack_Italy_Helper' ) ) :

	class EasyPack_Italy_Helper {

		protected static $instance;


		public function __construct() {
			add_filter( 'query_vars', [ $this, 'query_vars' ] );
			add_action( 'woocommerce_before_my_account', [ $this, 'woocommerce_before_my_account' ] );
			add_filter( 'woocommerce_screen_ids', [ $this, 'woocommerce_screen_ids' ] );


		}

		public static function EasyPack_Italy_Helper() {
			if ( self::$instance === null ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
		public function print_stickers(
			$return_stickers = false,
			$orders = null
		) {
			$ret = [ 'status' => 'ok' ];

			if ( null === $orders ) {
                $orders = isset( $_POST['easypack_parcel'] )
                    ? (array) sanitize_text_field($_POST['easypack_parcel'])
                    : array();
			}

			$selected_shipments_ids = [];
			$shipment_service       = inpost_italy()->get_shipment_service();

            if( ! empty ( $orders ) ) {

                if( is_array( $orders ) ) {
                    foreach ($orders as $order) {
                        $inpost_internal_data = $shipment_service->get_shipment_by_order_id( (int)$order );

                        if( $inpost_internal_data && is_object( $inpost_internal_data ) ) {
                            $selected_shipments_ids[] = $inpost_internal_data->getInternalData()->getInpostId();
                        }
                    }
                } else {

                    $inpost_internal_data = $shipment_service->get_shipment_by_order_id( (int)$orders );

                    if( $inpost_internal_data && is_object( $inpost_internal_data ) ) {
                        $selected_shipments_ids[] = $inpost_internal_data->getInternalData()->getInpostId();
                    }
                }
            }

			try {
				if ( true === $return_stickers ) {
					$results
						= inpost_italy_api()->customer_shipments_return_labels( $selected_shipments_ids );
				} else {
					$results
						= inpost_italy_api()->customer_shipments_labels( $selected_shipments_ids );
				}


                if ( ! isset( $results['headers'] ) ) {
                    if ( isset( $_POST['easypack_action'] ) && $_POST['easypack_action'] === 'easypack_italy_create_bulk_labels' ) {
                        echo wp_json_encode(array(
                                'status' => isset($results['status']) ? esc_html( $results['status'] ) : 'Errore',
                                'details' => isset($results['details']) ? esc_html( $results['details'] ) : 'Si è verificato un errore',
                                'message' => isset($results['message']) ? esc_html( $results['message'] ) : 'Helper:140'
                            )
                        );
                    }
                    return;

                } else {
                    $headers = $results['headers'];
                }

				/**
				 * @var Requests_Utility_CaseInsensitiveDictionary $headers
				 */

				header( sprintf( "Content-type:%s", $headers->getAll()['content-type'] ) );
                if( ! empty ( $orders ) && is_array( $orders ) ) {
                    header('Content-Disposition: attachment; filename="inpost_italy_labels"');
                }
                // PDF string from API
				echo $results['body'];// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				wp_die();

			} catch ( Exception $e ) {
				$ret['status']  = 'error';
				$ret['message'] = esc_html( $e->getMessage() );
				wp_die( esc_html__( 'Error while creating manifest: ', 'inpost-italy' ) . esc_html( $e->getMessage() ) );

			}

		}

		/**
		 * Allow for custom query variables
		 */
		public function query_vars( $query_vars ) {
			$query_vars[] = 'easypack_download';

			return $query_vars;
		}


		/**
		 * @param string|null $country
		 *
		 * @return string
		 */
		public function get_tracking_url( $country = null ) {
            return 'https://inpost.it/trova-il-tuo-pacco?number=';
		}

		public function get_weight_option( $weight, $options ) {
			$ret     = - 1;
			$options = array_reverse( $options, true );
			foreach ( $options as $val => $option ) {
				if ( floatval( $weight ) <= floatval( $val ) ) {
					$ret = $val;
				}
			}

			return $ret;
		}


		public function woocommerce_before_my_account() {
			if ( get_option( 'easypack_returns_page' )
			     && trim( get_option( 'easypack_returns_page' ) ) != ''
			) {
				$page = get_page( get_option( 'easypack_returns_page' ) );
				if ( $page ) {
					$img_src = inpost_italy()->getPluginImages()
					           . 'logo/small/white.png';
					$args = [
						'returns_page'       => get_page_link( get_option( 'easypack_returns_page' ) ),
						'returns_page_title' => $page->post_title,
						'img_src'            => $img_src,
					];
					wc_get_template( 'myaccount/before-my-account.php', $args,
						'', plugin_dir_path( inpost_italy()->getPluginFilePath() )
						    . 'templates/' );
				}
			}
		}

		public function woocommerce_screen_ids( $screen_ids ) {
			$screen_ids[] = 'inpost_page_easypack_shipment_italy';

			return $screen_ids;
		}

        /**
         * Check if at least one physical product exists in cart
         *
         * @param array $cart_contents
         *
         * @return bool
         */
        public function physical_goods_in_cart( $cart_contents ) {
            $res = false;

            if( ! empty( $cart_contents ) ) {
                foreach ( $cart_contents as $cart_item_key => $cart_item ) {
                    // if variation in cart
                    if( isset($cart_item['variation_id']) && ! empty($cart_item['variation_id']) ) {
                        $variant = wc_get_product( $cart_item['variation_id'] );
                        if( ! $variant->is_virtual() && ! $variant->is_downloadable() ) {
                            $res = true;
                            break;
                        }
                    } else {
                        // simple product
                        $product = wc_get_product( $cart_item['product_id'] );
                        if ( ! $product->is_virtual() && ! $product->is_downloadable() ) {
                            $res = true;
                            break;
                        }
                    }
                }
            }

            return $res;
        }

		public function validate_method_name( $method_name ) {
		    if( stripos( $method_name, ':' ) ) {
		        return trim( explode(':', $method_name)[0] );
            }
		    return $method_name;
        }


        /**
         * Convert size from model data to letter symbol (A,B,C)
         *
         * @param string $size
         *
         * @return string
         */
        public function convert_size_to_symbol($size) {
            if($size === 'small') {
                return 'S';
            }
            if($size === 'medium') {
                return 'M';
            }
            if($size === 'large') {
                return 'L';
            }

        }


        /**
         * Inline CSS for button
         *
         * @return void
         */
        public function include_inline_css() {
            
        }


        public function get_class_name_by_shipping_id( $shipping_id ) {

            switch ( $shipping_id ) {
                case 'easypack_italy_parcel_machines':
                    return 'EasyPack_Italy_Shipping_Parcel_Machines';
            }
        }


	}


endif;