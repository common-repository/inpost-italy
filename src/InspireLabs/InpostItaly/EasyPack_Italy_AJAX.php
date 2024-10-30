<?php

namespace InspireLabs\InpostItaly;

use Exception;

use InspireLabs\InpostItaly\shipping\EasyPack_Italy_Shipping_Method_Courier_C2C;
use InspireLabs\InpostItaly\shipping\EasyPack_Italy_Shipping_Parcel_Machines;


/**
 * EasyPack AJAX
 *
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'EasyPack_Italy_AJAX' ) ) :

	class EasyPack_Italy_AJAX {

		/**
		 * Ajax handler
		 */
		public static function init() {
			add_action( 'wp_ajax_easypack', array( __CLASS__, 'ajax_easypack' ) );
		}

		public static function ajax_easypack() {

			check_ajax_referer( 'easypack_nonce', 'security' );

			if ( isset( $_POST['easypack_action'] ) ) {
				$action = sanitize_text_field( $_POST['easypack_action'] );

				/*if ( $action == 'dispatch_point' ) {
					self::dispatch_point();
				}*/
				if ( $action == 'parcel_machines_create_package' ) {
					self::parcel_machines_create_package();
				}
				if ( $action == 'parcel_machines_cancel_package' ) {
					self::parcel_machines_cancel_package();
				}

				if ( $action == 'parcel_machines_cod_create_package' ) {
					self::parcel_machines_cod_create_package();
				}
				if ( $action == 'courier_create_package' ) {
					self::courier_create_package();
				}
				if ( $action == 'courier_c2c_create_package' ) {
					self::courier_c2c_create_package();
				}
				if ( $action == 'courier_lse_create_package' ) {
					self::courier_lse_create_package();
				}
				if ( $action == 'courier_lse_create_package_cod' ) {
					self::courier_lse_create_package_cod();
				}
				if ( $action == 'courier_local_standard_create_package' ) {
					self::courier_local_standard_create_package();
				}
				if ( $action == 'courier_local_standard_cod_create_package' ) {
					self::courier_local_standard_cod_create_package();
				}
				if ( $action == 'courier_local_express_create_package' ) {
					self::courier_local_express_create_package();
				}
				if ( $action == 'courier_local_express_cod_create_package' ) {
					self::courier_local_express_cod_create_package();
				}
				if ( $action == 'courier_palette_create_package' ) {
					self::courier_palette_create_package();
				}
				if ( $action == 'courier_palette_cod_create_package' ) {
					self::courier_palette_cod_create_package();
				}
				if ( $action == 'courier_cod_create_package' ) {
					self::courier_cod_create_package();
				}
				if ( $action == 'parcel_machines_cod_cancel_package' ) {
					self::parcel_machines_cod_cancel_package();
				}

                if ( $action == 'easypack_italy_create_bulk_labels' ) {
                    if( isset( $_POST['order_ids'] ) ) {
                        $data_string = sanitize_text_field( $_POST['order_ids'] );
                        $order_ids_arr = json_decode( stripslashes( $data_string ), true );

                        // we need validate choosed orders if they already has status which is allowing to get labels
                        $validated_ids = [];
                        foreach( $order_ids_arr as $order_id ) {
                            $easypack_status = get_post_meta( $order_id, '_easypack_parcel_tracking', true );
                            if( ! empty( $easypack_status ) ) {
                                $validated_ids[] = $order_id;
                            }
                        }

                        if( ! empty( $validated_ids ) ) {
                            // this function echo pdf or zip string
                            EasyPack_Italy_Helper::EasyPack_Italy_Helper()->print_stickers( false, $validated_ids );
                            die;
                        } else {
                            echo wp_json_encode( array( 'details' => esc_html__( 'Check your selection.', 'inpost-italy' ) ) );
                            die;
                        }
                    }

                    echo wp_json_encode( array( 'details' => esc_html__( 'There are some validation errors.', 'inpost-italy' ) ) );
                    die;
                }
			}
		}



		public static function parcel_machines_create_package() {
			EasyPack_Italy_Shipping_Parcel_Machines::ajax_create_package();
		}

		public static function parcel_machines_cancel_package() {

		}

		public static function parcel_machines_cod_create_package() {
			EasyPack_Italy_Shipping_Parcel_Machines_COD::ajax_create_package();
		}

		/**
		 * @throws \ReflectionException
		 */
		public static function courier_create_package() {
			EasyPack_Shipping_Method_Courier::ajax_create_package();
		}

		/**
		 * @throws \ReflectionException
		 */
		public static function courier_c2c_create_package() {
			EasyPack_Italy_Shipping_Method_Courier_C2C::ajax_create_package();
		}

		/**
		 * @throws \ReflectionException
		 */
		public static function courier_lse_create_package() {
			EasyPack_Shipping_Method_Courier_LSE::ajax_create_package();
		}

		/**
		 * @throws \ReflectionException
		 */
		public static function courier_lse_create_package_cod() {
			EasyPack_Shipping_Method_Courier_LSE_COD::ajax_create_package();
		}

		/**
		 * @throws \ReflectionException
		 */
		public static function courier_local_standard_create_package() {
			EasyPack_Shipping_Method_Courier_Local_Standard::ajax_create_package();
		}

		/**
		 * @throws \ReflectionException
		 */
		public static function courier_local_standard_cod_create_package() {
			EasyPack_Shipping_Method_Courier_Local_Standard_COD::ajax_create_package();
		}

		/**
		 * @throws \ReflectionException
		 */
		public static function courier_local_express_create_package() {
			EasyPack_Shipping_Method_Courier_Local_Express::ajax_create_package();
		}

		public static function courier_local_express_cod_create_package() {
			EasyPack_Shipping_Method_Courier_Local_Express_COD::ajax_create_package();
		}

		/**
		 * @throws \ReflectionException
		 */
		public static function courier_palette_create_package() {
			EasyPack_Shipping_Method_Courier_Palette::ajax_create_package();
		}

		/**
		 * @throws \ReflectionException
		 */
		public static function courier_palette_cod_create_package() {
			EasyPack_Shipping_Method_Courier_Palette_COD::ajax_create_package();
		}

		/**
		 * @throws \ReflectionException
		 */
		public static function courier_cod_create_package() {
			EasyPack_Shipping_Method_Courier_COD::ajax_create_package();
		}

		public static function parcel_machines_cod_cancel_package() {

		}

	}

endif;

