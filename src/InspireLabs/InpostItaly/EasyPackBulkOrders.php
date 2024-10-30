<?php

namespace InspireLabs\InpostItaly;

use InspireLabs\InpostItaly\shipx\models\shipment\ShipX_Shipment_Model;
use WC_Order;
use Automattic\WooCommerce\Utilities\OrderUtil;


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


/**
 * Bulk actions on order list page
 *
 * EasyPackBulkOrders
 */
class EasyPackBulkOrders {

	/**
	 * Hooks
	 *
	 * @return void
	 */
	public function hooks() {
		add_filter( 'bulk_actions-edit-shop_order', array( $this, 'inpost_italy_register_bulk_action' ) );
		add_filter( 'bulk_actions-woocommerce_page_wc-orders', array( $this, 'inpost_italy_register_bulk_action' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ), 75 );
		add_filter( 'manage_woocommerce_page_wc-orders_columns', array( $this, 'manage_edit_shop_order_columns' ), 11 );
		add_filter( 'manage_edit-shop_order_columns', array( $this, 'manage_edit_shop_order_columns' ), 11 );

		add_action( 'wp_ajax_easypack_italy_bulk_create_shipments', array( $this, 'easypack_bulk_create_shipments_callback' ) );
		add_action( 'manage_shop_order_posts_custom_column', array( $this, 'manage_shop_order_posts_custom_column' ), 11, 2 );
		add_action( 'manage_woocommerce_page_wc-orders_custom_column', array( $this, 'manage_shop_order_posts_custom_column' ), 11, 2 );
	}


	/**
	 * Register custom bulk actions
	 *
	 * @param array $bulk_actions array.
	 * @return mixed
	 */
	public function inpost_italy_register_bulk_action( $bulk_actions ) {
		$bulk_actions['easypack_italy_bulk_create_shipments'] = esc_html__( 'InPost Italy create shipments', 'inpost-italy' );
		$bulk_actions['easypack_italy_bulk_create_labels']    = esc_html__( 'InPost Italy get labels', 'inpost-italy' );
		return $bulk_actions;
	}


	/**
	 * Custom column Inpost status
	 *
	 * @param array $columns .
	 *
	 * @return array
	 */
	public function manage_edit_shop_order_columns( $columns ) {
		if ( isset( $columns['easypack_shipping_statuses'] ) ) {
			return $columns;
		}

		$ret = array();

		$col_added = false;

		foreach ( $columns as $key => $column ) {
			if ( ! $col_added && in_array( $key, array( 'order_actions', 'wc_actions' ), true ) ) {
				$ret['easypack_shipping_statuses'] = esc_html__( 'Inpost status', 'inpost-italy' );
				$col_added                         = true;
			}
			$ret[ $key ] = $column;
		}

		if ( ! $col_added ) {
			$ret['easypack_shipping_statuses'] = esc_html__( 'Inpost status', 'inpost-italy' );
		}

		return $ret;
	}


	/**
	 * Manage Columns
	 *
	 * @param string $column .
	 * @param int    $post_id   order ID.
	 *
	 * @return void
	 */
	public function manage_shop_order_posts_custom_column( string $column, $post_id ): void {
		if ( 'easypack_shipping_statuses' !== $column ) {
			return;
		}

		$inpost_status = '';

		$order = wc_get_order( $post_id );

		if ( $order ) {

			foreach ( $order->get_shipping_methods() as $shipping_method ) {

				if ( 0 === strpos( $shipping_method->get_method_id(), 'easypack_' ) ) {

					$status = get_post_meta( $post_id, '_easypack_status', true );

					if ( OrderUtil::custom_orders_table_usage_is_enabled() && ! $status ) {
						// HPOS usage is enabled.
						if ( is_a( $post_id, 'WC_Order' ) ) {
							$post_id = $post_id->get_id();
							$status  = isset( get_post_meta( $post_id )['_easypack_status'][0] )
								? get_post_meta( $post_id )['_easypack_status'][0]
								: null;
						}
					}

					if ( ! empty( $status ) ) {
						$tracking_url    = inpost_italy_helper()->get_tracking_url();
						$tracking_number = get_post_meta( $post_id, '_easypack_parcel_tracking', true );
						if ( empty( $tracking_number ) ) {
							$shipment = $order->get_meta( '_shipx_shipment_object' );

							if ( ! $shipment && 'yes' === get_option( 'woocommerce_custom_orders_table_enabled' ) ) {
								$from_order_meta_raw = isset( get_post_meta( $post_id )['_shipx_shipment_object'][0] )
									? get_post_meta( $post_id )['_shipx_shipment_object'][0]
									: '';

								if ( ! empty( $from_order_meta_raw ) ) {
									$shipment = unserialize( $from_order_meta_raw );
								}
							}

							if ( is_object( $shipment ) && $shipment instanceof ShipX_Shipment_Model ) {
								$tracking_number = $shipment->getInternalData()->getTrackingNumber();
								if ( ! empty( $tracking_number ) ) {
									update_post_meta( $post_id, '_easypack_parcel_tracking', sanitize_text_field( $tracking_number ) );
								}
							}
						}
						if ( ! empty( $tracking_number ) ) {
							$print_label_icon = sprintf(
								'<a href="#" target="_blank" data-id="%s" class="get_sticker_action_orders">
                                                <span 
                                                title="%s" 
                                                data-id="%s"
                                                class="dashicons dashicons-media-spreadsheet%s"></span>
                                                </a>',
								$post_id,
								esc_html__( 'Print sticker', 'inpost-italy' ),
								$post_id,
								''
							);

							$link_to_tracking = sprintf(
								'<a target="_blank" href="%s">%s</a>',
								$tracking_url . $tracking_number,
								$tracking_number
							);

							$inpost_api_status = '';
							$shipment          = get_post_meta( $post_id, '_shipx_shipment_object', true );
							if ( $shipment && $shipment instanceof ShipX_Shipment_Model ) {
								$inpost_api_status = $shipment->getInternalData()->getStatus();
							}

							$inpost_status = '<div class="inpost-status-inside-td">'
								. $print_label_icon . ' ' . $inpost_api_status . ' ' . $link_to_tracking
								. '</div>';

						} else {
							$shipment_service = EasyPack_Italy::EasyPack_Italy()->get_shipment_service();
							if ( is_object( $shipment_service ) ) {
								$shipment = $shipment_service->get_shipment_by_order_id( $post_id );
								if ( is_object( $shipment ) && is_object( $shipment->getInternalData() ) ) {

									if ( 'offer_selected' === $shipment->getInternalData()->getStatus() ) {

										$inpost_status = '<div class="inpost-status-inside-td">'
											. esc_html_e( 'The package has not been created! Probably you do not have funds or a contract for InPost services', 'inpost-italy' )
											. ' ('
											. $shipping_method->get_method_title()
											. ')'
											. '</div>';

									} else {

										$status_desc = $shipment->getInternalData()->getStatusDescription();

										$inpost_status = '<div class="inpost-status-inside-td">'
											. $status_desc
											. '</div>';
									}
								}
							}
						}
					} else {

						$inpost_status = '<div class="inpost-status-inside-td">'
							. esc_html__( 'Not created yet', 'inpost-italy' )
							. '</div>';
					}
				}
			}
		}

		echo wp_kses_post( $inpost_status );
	}


	/**
	 * Callback for Ajax
	 *
	 * @return void
	 */
	public function easypack_bulk_create_shipments_callback() {

		if ( ! is_admin() ) {
			exit;
		}

		// Verify that the nonce is valid.
		if ( ! isset( $_POST['security'] )
			||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['security'] ) ), 'easypack_nonce' )
		) {

			$return_content = array(
				'status'  => 'bad',
				'message' => esc_html__( 'Bad nonce', 'inpost-italy' ),
			);
			echo wp_json_encode( $return_content );
			exit;
		}

		if ( ! isset( $_POST['order_id'] ) ) {
			$return_content = array(
				'status'  => 'bad',
				'message' => esc_html__( 'No orders selected', 'inpost-italy' ),
			);
			echo wp_json_encode( $return_content );
			exit;

		} else {

			$order_id = sanitize_text_field( $_POST['order_id'] );
			$status   = get_post_meta( $order_id, '_easypack_status', true );

			if ( ! empty( $status ) ) {
				$return_content = array(
					'status'  => 'already_created',
					'message' => esc_html__( 'Shipment already created', 'inpost-italy' ),
				);
				echo wp_json_encode( $return_content );
				exit;

			} else {

				// detect InPost shipping class we need for each order.
				$service = '';
				$order   = wc_get_order( $order_id );
				foreach ( $order->get_items( 'shipping' ) as $item_id => $item ) {
					$item_data = $item->get_data();
					$service   = $item_data['method_id'];
				}

				$is_any_inpost_method = ! empty( $service ) && 0 === strpos( $service, 'easypack_' );

				if ( $is_any_inpost_method ) {

					$shipping_method_class_name = inpost_italy_helper()->get_class_name_by_shipping_id( $service );

					$class_with_namespace = 'InspireLabs\InpostItaly\shipping\\' . $shipping_method_class_name;

					if ( class_exists( $class_with_namespace ) ) {
						$class_instance = new $class_with_namespace();
						$class_instance::ajax_create_package();

					} else {
						$return_content = array(
							'status'  => 'bad',
							'message' => esc_html__( 'Order was placed with not InPost shipping method', 'inpost-italy' ),
						);
						echo wp_json_encode( $return_content );
						exit;
					}
				} else {
					$return_content = array(
						'status'  => 'bad',
						'message' => esc_html__( 'Order was placed with not InPost shipping method', 'inpost-italy' ),
					);
					echo wp_json_encode( $return_content );
					exit;
				}
			}
		}
	}


	/**
	 * Enqueue admin scripts
	 *
	 * @return void
	 */
	public function enqueue_admin_scripts(): void {

		$current_screen = get_current_screen();
		// only on order's list page.
		if ( is_a( $current_screen, 'WP_Screen' ) && ( 'edit-shop_order' === $current_screen->id
			|| 'woocommerce_page_wc-orders' === $current_screen->id ) ) {
			$plugin_data = new EasyPack_Italy();

			wp_enqueue_style( 'easypack-italy-bulk-actions', $plugin_data->getPluginCss() . 'easypack-italy-bulk-actions.css', array(), INPOST_ITALY_PLUGIN_VERSION );
			wp_enqueue_script(
				'easypack-italy-bulk-actions',
				$plugin_data->getPluginJs() . 'easypack-italy-bulk-actions.js',
				array( 'jquery' ),
				INPOST_ITALY_PLUGIN_VERSION,
				true
			);
			wp_localize_script(
				'easypack-italy-bulk-actions',
				'easypack_it_bulk',
				array(
					'ajaxurl'        => admin_url( 'admin-ajax.php' ),
					'easypack_nonce' => wp_create_nonce( 'easypack_nonce' ),
				)
			);
		}
	}
}
