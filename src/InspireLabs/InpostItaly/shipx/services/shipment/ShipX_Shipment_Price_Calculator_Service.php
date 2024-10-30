<?php
namespace InspireLabs\InpostItaly\shipx\services\shipment;

use Automattic\WooCommerce\Utilities\OrderUtil;
use InspireLabs\InpostItaly\EasyPack_Italy;
use Exception;
use InspireLabs\InpostItaly\EasyPack_Italy_API;
use InspireLabs\InpostItaly\shipping\EasyPack_Shipping_Method_Courier;
use InspireLabs\InpostItaly\shipping\EasyPack_Italy_Shipping_Parcel_Machines;
use InspireLabs\InpostItaly\shipx\models\shipment\ShipX_Shipment_Model;
use InspireLabs\InpostItaly\shipx\models\shipment_cost\ShipX_Shipment_Cost_Model;
use ReflectionClass;
use ReflectionException;

class ShipX_Shipment_Price_Calculator_Service
{

    /**
     * @var EasyPack_Italy_API
     */
    private $api;

    public function __construct()
    {
        $this->api = inpost_italy_api();
    }


    /**
     * @param $order_id
     *
     * @return ShipX_Shipment_Model|null
     */
    public function get_shipment_by_order_id($order_id)
    {
        $order = wc_get_order($order_id);
        $from_order_meta = null;

        if( 'yes' === get_option('woocommerce_custom_orders_table_enabled') ) {
            // HPOS usage is enabled.
            $from_order_meta_raw = isset( get_post_meta( $order_id )['_shipx_shipment_object'][0] )
                ? get_post_meta( $order_id )['_shipx_shipment_object'][0]
                : '';

            if( !empty( $from_order_meta_raw ) ) {
                $from_order_meta = unserialize( $from_order_meta_raw );
            }

        } else {
            // Traditional orders are in use.
            $from_order_meta = $order->get_meta('_shipx_shipment_object');

        }

        return $from_order_meta instanceof ShipX_Shipment_Model
            ? $from_order_meta
            : null;
    }

}
