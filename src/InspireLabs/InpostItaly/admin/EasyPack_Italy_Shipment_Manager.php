<?php

namespace InspireLabs\InpostItaly\admin;

use InspireLabs\InpostItaly\EasyPack_Italy;
use Exception;
use InspireLabs\InpostItaly\EasyPack_Italy_API;
use InspireLabs\InpostItaly\EasyPack_Italy_Helper;

/**
 * EasyPack Shipment Manager
 *
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'EasyPack_Italy_Shipment_Manager' ) ) :

	/**
	 * EasyPack_Italy_Shipment_Manager
	 */
	class EasyPack_Italy_Shipment_Manager {

		/**
		 *
		 */
		public static function init() {


			add_action( 'admin_menu', [ __CLASS__, 'admin_menu' ] );
			add_action( 'init', [ __CLASS__, 'print_stickers' ] );
		}

		public static function print_stickers() {
			if ( true === self::is_stickers_request() ) {
                check_ajax_referer('easypack_shipment_table', 'shipment_table_nonce');
				EasyPack_Italy_Helper::EasyPack_Italy_Helper()->print_stickers();
			}

			if ( true === self::is_stickers_return_request() ) {
                check_ajax_referer('easypack_shipment_table', 'shipment_table_nonce');
				EasyPack_Italy_Helper::EasyPack_Italy_Helper()->print_stickers( true );
			}

			if ( true === self::is_sticker_single_request() ) {
                check_ajax_referer('easypack_shipment_table', 'shipment_table_nonce');
				EasyPack_Italy_Helper::EasyPack_Italy_Helper()->print_stickers( false, sanitize_text_field( $_POST['get_sticker_order_id'] ) );
			}

			if ( true === self::is_sticker_single_ret_request() ) {
                check_ajax_referer('easypack_shipment_table', 'shipment_table_nonce');
				EasyPack_Italy_Helper::EasyPack_Italy_Helper()->print_stickers( true, sanitize_text_field( $_POST['get_sticker_order_id'] ) );
			}
		}

		/**
		 *
		 */
		public static function admin_menu() {
			global $menu;
			$menu_pos = 56;
			while ( isset( $menu[ $menu_pos ] ) ) {
				$menu_pos ++;
			}
			if ( inpost_italy_api()->api_country() != '--' ) {
				$icon_svg = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIj8+Cjxzdmcgd2lkdGg9IjI0Ni45OTk5OTk5OTk5OTk5NyIgaGVpZ2h0PSIyMjEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6c3ZnPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CiA8Zz4KICA8dGl0bGU+TGF5ZXIgMTwvdGl0bGU+CiAgPGcgaWQ9InN2Z18xIiBzdHJva2U9Im51bGwiPgogICA8cGF0aCBpZD0ic3ZnXzciIGQ9Im0xMDEuNTYxMDQsMTEwLjY3NDkyYzAsMCAtMTEuNjQ2MzcsNC41MDMxIC0yNi4wMTU5LDQuNTAzMWMtMTQuMzY4MTQsMCAtMjYuMDE1OSwtNC41MDMxIC0yNi4wMTU5LC00LjUwMzFzMTEuNjQ3NzUsLTQuNTAwMzMgMjYuMDE1OSwtNC41MDAzM2MxNC4zNjk1MywwIDI2LjAxNTksNC41MDAzMyAyNi4wMTU5LDQuNTAwMzMiIGZpbGw9IiNGRkNDMDAiIHN0cm9rZT0ibnVsbCIvPgogICA8cGF0aCBpZD0ic3ZnXzgiIGQ9Im0xMzcuNTM0NjUsNDQuNDYwM2MwLDAgLTEwLjMyMDA2LC02Ljk0OTY1IC0xOC4zNTM5OSwtMTguNjI3ODNjLTguMDMzOTQsLTExLjY3NjggLTEwLjc0MDUsLTIzLjY1OTI0IC0xMC43NDA1LC0yMy42NTkyNHMxMC4zMTg2OCw2Ljk0ODI3IDE4LjM1Mzk5LDE4LjYyNTA3YzguMDMzOTQsMTEuNjc5NTYgMTAuNzQwNSwyMy42NjIwMSAxMC43NDA1LDIzLjY2MjAxIiBmaWxsPSIjRkZDQzAwIiBzdHJva2U9Im51bGwiLz4KICAgPHBhdGggaWQ9InN2Z185IiBkPSJtMTExLjE4NjgzLDczLjAzMjAxYzAsMCAtMTIuNDM4ODQsLTEuMzg1NzggLTI1LjEyNTI0LC03Ljk5OTM2Yy0xMi42ODY0LC02LjYxMjIgLTIwLjgxNDM4LC0xNS45NDc1NSAtMjAuODE0MzgsLTE1Ljk0NzU1czEyLjQzODg0LDEuMzg1NzggMjUuMTI1MjQsNy45OTkzNmMxMi42ODY0LDYuNjEyMiAyMC44MTQzOCwxNS45NDc1NSAyMC44MTQzOCwxNS45NDc1NSIgZmlsbD0iI0ZGQ0MwMCIgc3Ryb2tlPSJudWxsIi8+CiAgIDxwYXRoIGlkPSJzdmdfMTAiIGQ9Im0xMzUuNzU4ODYsMTMwLjc2ODc1YzcuNTI5MTMsLTIuMjg0NzQgMTMuOTA0ODMsLTUuNjE5MTkgMTMuOTA0ODMsLTUuNjE5MTlzLTE3LjczNTc5LC00LjkzMDQ1IC0xNi40MzU3NSwtMjMuNDU0NTVjNC4wNzI5OCwtMzAuMzk3MjkgMjguNjgzNzQsLTU0LjI2MTIyIDU5LjQ5NDU1LC01OC4xMDMyM2MtMy4yNjgwNiwtMC40NTA4NiAtNi42MDUyOCwtMC42ODczNiAtMTAuMDAxOTcsLTAuNjcyMTVjLTM4LjEwODk4LDAuMTY4NzMgLTY4Ljg2MzA5LDMwLjU5NTA2IC02OC42OTE2LDY3Ljk1NzIyYzAuMTcwMTEsMzcuMzYwNzcgMzEuMjA0OTcsNjcuNTEwNSA2OS4zMTI1Nyw2Ny4zNDMxNmMzLjE3ODE3LC0wLjAxMzgzIDYuMzAxMDIsLTAuMjU3MjQgOS4zNjMwMSwtMC42Nzc2OGMtMjcuMDQwNzEsLTMuMzc4NzEgLTQ5LjA1MDAyLC0yMi4wNzE1NCAtNTYuOTQ1NjUsLTQ2Ljc3MzU3bDAuMDAwMDEsLTAuMDAwMDF6IiBmaWxsPSIjRkZDQzAwIi8+CiAgIDxwYXRoIGlkPSJzdmdfMTEiIGQ9Im0xMzcuNTM0NjUsMTc2LjYzMjNjMCwwIC0xMC4zMjAwNiw2Ljk0OTY1IC0xOC4zNTM5OSwxOC42MjkyMWMtOC4wMzM5NCwxMS42NzU0MSAtMTAuNzQwNSwyMy42NjA2MiAtMTAuNzQwNSwyMy42NjA2MnMxMC4zMTg2OCwtNi45NDk2NSAxOC4zNTM5OSwtMTguNjI3ODNjOC4wMzM5NCwtMTEuNjc2OCAxMC43NDA1LC0yMy42NjIwMSAxMC43NDA1LC0yMy42NjIwMSIgZmlsbD0iI0ZGQ0MwMCIgc3Ryb2tlPSJudWxsIi8+CiAgIDxwYXRoIGlkPSJzdmdfMTIiIGQ9Im0xMTEuMTg2ODMsMTQ4LjA2MTk3YzAsMCAtMTIuNDM4ODQsMS4zODU3OCAtMjUuMTI1MjQsNy45OTkzNmMtMTIuNjg2NCw2LjYxMDgxIC0yMC44MTQzOCwxNS45NDYxNyAtMjAuODE0MzgsMTUuOTQ2MTdzMTIuNDM4ODQsLTEuMzg1NzggMjUuMTI1MjQsLTcuOTk3OThzMjAuODE0MzgsLTE1Ljk0NzU1IDIwLjgxNDM4LC0xNS45NDc1NSIgZmlsbD0iI0ZGQ0MwMCIgc3Ryb2tlPSJudWxsIi8+CiAgPC9nPgogPC9nPgo8L3N2Zz4=';
				add_menu_page( esc_html__( 'InPost Italy', 'inpost-italy' ),
                    esc_html__( 'InPost Italy', 'inpost-italy' ),
					'view_woocommerce_reports', 'inpost-it', null, $icon_svg, $menu_pos );
                add_submenu_page( 'inpost-it',
                    esc_html__( 'Settings', 'inpost-italy' ),
                    esc_html__( 'Settings', 'inpost-italy' ),
                    'view_woocommerce_reports',
                    'admin.php?page=wc-settings&tab=easypack_italy' );
				add_submenu_page( 'inpost-it',
                    esc_html__( 'Shipments', 'inpost-italy' ),
                    esc_html__( 'Shipments', 'inpost-italy' ), 'view_woocommerce_reports',
					'easypack_shipment_italy',
					[ __CLASS__, 'easypack_shipment' ] );

				remove_submenu_page( 'inpost-it', 'inpost-it' );
			}
		}

		/**
		 * @throws Exception
		 */
		public static function easypack_shipment() {
			$courier_pickup_service = EasyPack_Italy::EasyPack_Italy()->get_courier_pickup_service();
			$status_service         = EasyPack_Italy::EasyPack_Italy()->get_shipment_status_service();
			$shipment_service       = EasyPack_Italy::EasyPack_Italy()->get_shipment_service();

			$view_var_send_methods = self::get_send_methods_for_country( inpost_italy_api()->api_country() );
			$view_var_statuses = $status_service->get_statuses_key_value();
			$view_var_services = $shipment_service->get_services_key_value();

			if ( true === self::is_pickup() ) {
				self::pickup();
			}

			$send_method = 'all';

			if ( isset( $_GET['send_method'] ) ) {
				$send_method = sanitize_text_field( $_GET['send_method'] );
			}

			$view_var_shipment_manager_list_table = new EasyPack_Italy_Shipment_Manager_List_Table( $send_method );

			include( 'views/html-shipment-manager.php' );
		}

		private static function pickup() {
			$shipment_service       = inpost_italy()->get_shipment_service();
			$courier_pickup_service = inpost_italy()->get_courier_pickup_service();

            $selected_data = null;

            if( isset( $_POST['easypack_parcel'] ) ) {
                if ( is_array($_POST['easypack_parcel'] ) ) {
                    $selected_data = array_map('sanitize_text_field', $_POST['easypack_parcel']);
                }
            }

			$selected_shipments     = $selected_data;
			$dispatch_point         = sanitize_text_field( $_POST['easypack_dispatch_point'] );


			$shipments_to_pick_up = [];
            if( ! empty ( $selected_shipments ) ) {
                foreach ($selected_shipments as $order_id) {
                    $shipments_to_pick_up[] = $shipment_service->get_shipment_by_order_id($order_id);
                }
            }

			$dispatch_point_arr = $courier_pickup_service->getDispatchPoint( (int) $dispatch_point );

			try {
				$courier_pickup_service->createDispatchOrder( $dispatch_point_arr, $shipments_to_pick_up );
				$message = esc_html__( 'Shipments dispathed ', 'inpost-italy' );
				printf( '<div class="updated"><p>%s</p></div>', esc_html($message) );


			} catch ( Exception $e ) {
				$class   = "error";
				$message = esc_html__( 'Error while creating manifest: ', 'inpost-italy' ) . esc_html( $e->getMessage() );
				printf( '<div class="%s"><p>%s</p></div>', esc_html($class), esc_html($message) );
			}
		}


		/**
		 * @param string $api_country
		 *
		 * @return array
		 */
		private static function get_send_methods_for_country( $api_country ) {
            return [
                'any'            => esc_html__( 'All', 'inpost-italy' ),
                'parcel_locker'  => esc_html__( 'Parcel Point', 'inpost-italy' )
            ];
		}

		/**
		 * @return bool
		 */
		public static function is_courier_context() {
			return isset( $_GET['send_method'] ) && 'dispatch_order' === $_GET['send_method'];
		}

		/**
		 * @return bool
		 */
		private static function is_stickers_request() {
			return isset( $_POST['easypack_get_stickers_request'] )
			       && $_POST['easypack_get_stickers_request'] === '1';
		}

		/**
		 * @return bool
		 */
		private static function is_sticker_single_request() {
			return isset( $_POST['easypack_get_sticker_single_request'] )
			       && $_POST['easypack_get_sticker_single_request'] === '1';
		}

		private static function is_sticker_single_ret_request() {
			return isset( $_POST['easypack_get_sticker_single_request_ret'] )
			       && $_POST['easypack_get_sticker_single_request_ret'] === '1';
		}

		/**
		 * @return bool
		 */
		private static function is_stickers_return_request() {
			return isset( $_POST['easypack_get_stickers_ret_request'] )
			       && $_POST['easypack_get_stickers_ret_request'] === '1';
		}

		/**
		 * @return bool
		 */
		private static function is_pickup() {
			return isset( $_POST['easypack_create_manifest_input'] )
			       && $_POST['easypack_create_manifest_input'] == 1;
		}

		public static function getSendingMethodFilterFromRequest() {
			return ! empty( $_GET['send_method'] )
				? sanitize_key( $_GET['send_method'] )
				: null;
		}

		public static function getStatusFilterFromRequest() {
			return ! empty( $_GET['status'] )
				? sanitize_key( $_GET['status'] )
				: null;
		}

		public static function getServiceFilterFromRequest() {
			return ! empty( $_GET['service'] )
				? sanitize_key( $_GET['service'] )
				: null;
		}

		public static function getReferenceNumberFilterFromRequest() {
			return ! empty( $_GET['reference_number'] )
				? (int) sanitize_key( $_GET['reference_number'] )
				: null;
		}

		public static function getTrackingNumberFilterFromRequest() {
			return ! empty( $_GET['tracking_number'] )
				? sanitize_key( $_GET['tracking_number'] )
				: null;
		}

		public static function getOrderIdFilterFromRequest() {
			return ! empty( $_GET['order_id'] )
				? (int) sanitize_key( $_GET['order_id'] )
				: null;
		}

		public static function getReceiverEmailFilterFromRequest() {
			return ! empty( $_GET['receiver_email'] )
				? filter_var( $_GET['receiver_email'], FILTER_SANITIZE_EMAIL )
				: null;
		}

		public static function getReceiverPhoneFilterFromRequest() {
			return ! empty( $_GET['receiver_phone'] )
				? esc_sql( strip_shortcodes( wp_strip_all_tags( sanitize_text_field($_GET['receiver_phone']) ) ) )
				: null;
		}
	}

endif;

