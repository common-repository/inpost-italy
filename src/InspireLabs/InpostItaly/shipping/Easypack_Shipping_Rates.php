<?php

namespace InspireLabs\InpostItaly\shipping;

use WC_Shipping_Method;
use WC_Shipping_Rate;
use InspireLabs\InpostItaly\EasyPack_Italy_Helper;

class Easypack_Shipping_Rates
{

    public function init()
    {


        add_action('woocommerce_after_shipping_rate', function ($method, $index) {
            /**
             * @var WC_Shipping_Rate $method
             */

            if (strpos($method->get_method_id(), 'easypack_') === false) {
                return; //none inpost method
            }

            $meta = $method->get_meta_data();
            if (is_array($meta) && isset($meta['logo'])) {
                $custom_logo = $meta['logo'];
            }

            $defined_courier_description = '';
            $method_name = inpost_italy_helper()->validate_method_name( $method->get_method_id() );

            if( $method_name === 'easypack_italy_parcel_machines' ) {
                $defined_courier_description = '<span id="defined_inpost_italy_courier_description">
                                                    <i>' . esc_html__( 'Consegna sostenibile stimata in 48-72 ore', 'inpost-italy' ) . '</i>
                                                </span>';

            }


            if (empty($custom_logo)) {
                $img = ' <span class="easypack-shipping-method-logo"><img style="" src="'
                    . esc_url( inpost_italy()->getPluginImages() )
                    . 'logo/small/white.png" /><span>';
            } else {
                $img = ' <span class="easypack-custom-shipping-method-logo"><img style="" src="'
                    . esc_url( $custom_logo ) . '" /><span>';
            }
            $data = $img . $defined_courier_description;
            echo wp_kses_post( $data );

        }, 10, 2);
    }
}
