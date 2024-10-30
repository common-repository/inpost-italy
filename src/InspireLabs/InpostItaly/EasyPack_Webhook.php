<?php


namespace InspireLabs\InpostItaly;


use Automattic\WooCommerce\Utilities\OrderUtil;
use InspireLabs\InpostItaly\shipx\models\shipment\ShipX_Shipment_Model;

if ( ! class_exists( 'InspireLabs\InpostItaly\EasyPack_Webhook' ) ) :
    class EasyPack_Webhook
    {
        public function hooks() {
            add_action( 'rest_api_init', array( $this, 'register_webhook_status_route' ) );
            add_filter( "woocommerce_rest_prepare_shop_order_object", array( $this, "add_locker_point_data" ), PHP_INT_MAX, 3 );
        }

        /**
         * Register order status update route.
         */
        public function register_webhook_status_route() {
            register_rest_route(
                'inpost_webhook/v1',
                '/order/update',
                array(
                    'methods' => 'POST',
                    'callback'            => array( $this, 'update_shipment_status' ),
                    'args'                => array(),
                    'permission_callback' => function() {
                        return true;
                    }
                )
            );
        }


        public function update_shipment_status( \WP_REST_Request $request ) {

            if ( $request->is_json_content_type() ) {
                try {

                    $body = json_decode( $request->get_body(), true );

                    if( isset( $body['payload']['status'] ) && ! empty( $body['payload']['status'] ) ) {
                        if( isset( $body['payload']['shipment_id'] ) && ! empty( $body['payload']['shipment_id'] ) ) {

                            $orders = $this->get_order_id_by_inpost_id( sanitize_text_field( $body['payload']['shipment_id'] ) );

                            if ( !empty ( $orders ) && isset( $orders[0] ) ) {
                                $order_id = $orders[0];

                                $webhook_status = sanitize_text_field( $body['payload']['status'] );

                                $order = wc_get_order($order_id);
                                if( is_object( $order ) && ! is_wp_error( $order ) ) {

                                    $shipment = $order->get_meta('_shipx_shipment_object');

                                    if ( ! $shipment && 'yes' === get_option('woocommerce_custom_orders_table_enabled') ) {
                                        $from_order_meta_raw = isset( get_post_meta( $order_id )['_shipx_shipment_object'][0] )
                                            ? get_post_meta( $order_id )['_shipx_shipment_object'][0]
                                            : '';

                                        if( !empty( $from_order_meta_raw ) ) {
                                            $shipment = unserialize( $from_order_meta_raw );
                                        }
                                    }

                                    if ( $shipment && $shipment instanceof ShipX_Shipment_Model ) {
                                        $shipment->getInternalData()->setStatus( $webhook_status );
                                        $shipment->getInternalData()->setStatusChangedTimestamp( time() );

                                        inpost_italy()->get_shipment_service()->update_shipment_to_db( $shipment );
                                    }
                                }
                            }
                        }
                    }

                } catch ( \Exception $e ) {
                    $exception_data = array(
                        'status'        => $e->getCode(),
                        'response'      => $e->getMessage(),
                    );
                    \wc_get_logger()->debug( 'Exception in function update_shipment_status: ', array( 'source' => 'inpost-it-log' ) );
                    \wc_get_logger()->debug( print_r( $exception_data, true), array( 'source' => 'inpost-it-log' ) );
                }

            } else {
                $response = array(
                    'status'        => 406,
                    'response'      => 'Not Acceptable Request :)',
                );
            }


            $resp = array(
                'status'        => 200,
                'response'      => 'ok',
            );

            return new \WP_REST_Response( $resp, 200 );
        }


        public function add_locker_point_data( $response, $object, $request ) {

            if( empty( $response->data ) ) {
                return $response;
            }

            $order_id = $request->get_param('id');

            $point_locker_data = get_post_meta( $order_id, '_parcel_machine_id', true );

            $response->data['shipping_lines'][0]['parcel_locker'] = $point_locker_data;

            return $response;

        }


        private function get_order_id_by_inpost_id( $inpost_id ) {
            global $wpdb;

            $order_ids = [];

            $meta_key = '_easypack_inpost_id';
            $meta_value = $inpost_id;

            if ( 'yes' === get_option('woocommerce_custom_orders_table_enabled') ) {

                $order_ids = $wpdb->get_col( $wpdb->prepare( "
                    SELECT DISTINCT pm.post_id
                    FROM {$wpdb->prefix}postmeta AS pm
                    WHERE pm.meta_key = %s
                    AND pm.meta_value = %s
                ", array(
                    $meta_key,
                    $meta_value,
                ) ) );

            } else {

                $order_ids = $wpdb->get_col( $wpdb->prepare( "
                    SELECT ID
                    FROM {$wpdb->prefix}posts o
                    INNER JOIN {$wpdb->prefix}postmeta om
                        ON o.ID = om.post_id            
                    WHERE o.post_type = %s    
                    AND om.meta_key = %s
                    AND om.meta_value = %s
                ", array(
                    'shop_order',
                    $meta_key,
                    $meta_value,
                ) ) );
            }

            return $order_ids;
        }
    }

endif;