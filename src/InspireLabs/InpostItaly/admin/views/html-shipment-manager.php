<?php
/**
 * @var ShipX_Dispatch_Order_Point_Model[]   $view_var_points
 * @var array                                $view_var_send_methods
 * @var array                                $view_var_statuses
 * @var array                                $view_var_services
 * @var EasyPack_Italy_Shipment_Manager_List_Table $view_var_shipment_manager_list_table
 * @var int $dispatch_point
 *
 *
 */


if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use InspireLabs\InpostItaly\admin\EasyPack_Italy_Shipment_Manager;
use InspireLabs\InpostItaly\admin\EasyPack_Italy_Shipment_Manager_List_Table;
use InspireLabs\InpostItaly\EasyPack_Italy;
use InspireLabs\InpostItaly\shipx\models\courier_pickup\ShipX_Dispatch_Order_Point_Model;

?>

<?php $is_courier_context = EasyPack_Italy_Shipment_Manager::is_courier_context() ?>
<div class="wrap">
    <div id="icon-users" class="icon32"></div>
    <h2><?php esc_html_e( 'InPost Shipments', 'inpost-italy' ); ?></h2>
	<?php $view_var_shipment_manager_list_table->prepare_items(); ?>
    <form method="get">
        <input type="hidden" name="page" value="easypack_shipment_italy">
		<?php if ( true === $is_courier_context ): ?>
            <div style="float:left;">
				<?php

				$point_select_items = [];
				foreach ( $view_var_points as $k => $point ) {
					$point_select_items[ $k ] = $point;
				}

				$params = [
					'type'    => 'select',
					'selected'    => $dispatch_point,
					'options' => $point_select_items,
					'class'   => [ 'wc-enhanced-select' ],
					'label'   => esc_html__( 'Dispatch point ', 'inpost-italy' ),
				];
				woocommerce_form_field( 'dispatch_point', $params );
				?>

            </div>

            <div style="float:left;">
                <p>&nbsp;
                    <span class="tips"
                          data-tip="<?php esc_attr_e( 'From the list, select the packages that you want to be sent by courier.', 'inpost-italy' ); ?>">
						<button id="easypack_get_courier" class="button-primary">
                            <?php esc_html_e( 'Get courier', 'inpost-italy' ); ?>
                        </button>&nbsp;
					</span>
                </p>

            </div>

            <div style="float:left;">
                <p><span id="easypack_spinner_get_courier" class="spinner"></span></p>
            </div>
            <div style="clear:both;"></div>
        <?php endif; ?>

        <div style="float:none;">
            <h3><?php esc_html_e( 'Filters', 'inpost-italy' ) ?></h3>
			<?php
            $params = [
                'type' => 'select',
                'options' => $view_var_services,
                'class' => ['wc-enhanced-select'],
                'label' => esc_html__('Service', 'inpost-italy'),
                'label_class' => 'admin-label',
                'input_class' => ['admin-input'],
            ];
            woocommerce_form_field('service', $params, EasyPack_Italy_Shipment_Manager::getServiceFilterFromRequest());
            ?>
        </div>
        <div style="float:none;">
			<?php
			$params = [
				'type'        => 'select',
				'options'     => $view_var_statuses,
				'class'       => [ 'wc-enhanced-select' ],
				'label'       => esc_html__( 'Shipment status', 'inpost-italy' ),
				'label_class' => 'admin-label',
				'input_class' => [ 'admin-input', 'max-width-select' ],
			];
			woocommerce_form_field( 'status', $params, EasyPack_Italy_Shipment_Manager::getStatusFilterFromRequest() );
			?>
        </div>
        <div style="float:none;">
        </div>
        <div style="float:none;">
			<?php
			$params = [
				'type'        => 'text',
				'class'       => [ '' ],
				'label'       => esc_html__( 'Tracking number', 'inpost-italy' ),
				'label_class' => 'admin-label',
				'input_class' => [ 'admin-input' ],
			];
			woocommerce_form_field( 'tracking_number', $params, EasyPack_Italy_Shipment_Manager::getTrackingNumberFilterFromRequest() );
			?>
        </div>
        <div style="float:none;">
			<?php
			$params = [
				'type'        => 'number',
				'class'       => [ '' ],
				'label'       => esc_html__( 'Order ID', 'inpost-italy' ),
				'label_class' => 'admin-label',
				'input_class' => [ 'admin-input' ],
			];
			woocommerce_form_field( 'order_id', $params, EasyPack_Italy_Shipment_Manager::getOrderIdFilterFromRequest() );
			?>
        </div>
        <div style="float:none;">
			<?php
			$params = [
				'type'        => 'text',
				'class'       => [ '' ],
				'label'       => esc_html__( 'Reference number', 'inpost-italy' ),
				'label_class' => 'admin-label',
				'input_class' => [ 'admin-input' ],
			];
			woocommerce_form_field( 'reference_number', $params, EasyPack_Italy_Shipment_Manager::getReferenceNumberFilterFromRequest() );
			?>
        </div>
        <div style="float:none;">
			<?php
			$params = [
				'type'        => 'text',
				'class'       => [ '' ],
				'label'       => esc_html__( 'Receiver email', 'inpost-italy' ),
				'label_class' => 'admin-label',
				'input_class' => [ 'admin-input' ],
			];
			woocommerce_form_field( 'receiver_email', $params, EasyPack_Italy_Shipment_Manager::getReceiverEmailFilterFromRequest() );
			?>
        </div>
        <div style="float:none;">
			<?php
			$params = [
				'type'        => 'text',
				'class'       => [ '' ],
				'label'       => esc_html__( 'Receiver phone', 'inpost-italy' ),
				'label_class' => 'admin-label',
				'input_class' => [ 'admin-input' ],
			];
			woocommerce_form_field( 'receiver_phone', $params, EasyPack_Italy_Shipment_Manager::getReceiverPhoneFilterFromRequest() );
			?>
        </div>
        <div style="float:left;">
            <p>
                <input class="button button-primary" type="submit" value="<?php esc_html_e( 'Filter parcels', 'inpost-italy' ); ?>"/>
            </p>
        </div>
        <div style="clear:both;"></div>
        <div style="width: 10px; display: block"></div>
        <div style="float:left; padding-left: 10px;">
            <p>
				<?php if ( true === $is_courier_context ) : ?>
                    <span>
				<?php else : ?>
                    <span class="tips" data-tip="">
				<?php endif; ?>

				</span>
            </p>
        </div>

        <div style="float:left;">
            <p><span id="easypack_spinner_get_stickers" class="spinner"></span>
            </p>
        </div>
        <div style="clear:both;"></div>


        <div style="float:left;">
            <p>
                <span>
				&nbsp;
				</span>
            </p>
        </div>
        <div style="float:left;">
            <p><span id="easypack_spinner_get_stickers" class="spinner"></span>
            </p>
        </div>
        <div style="clear:both;"></div>
    </form>
    <form id="easypack_shipment_form" method="post">
        <input type="hidden" id="easypack_create_manifest_input"
               name="easypack_create_manifest_input" value="0"/>
        <input type="hidden" id="easypack_dispatch_point"
               name="easypack_dispatch_point" value="0"/>
        <input type="hidden"
               name="page" value="easypack_shipment">
        <input type="hidden" name="easypack_get_stickers_request"
               id="easypack_get_stickers_request" value="0"/>
        <input type="hidden" name="easypack_get_stickers_ret_request"
               id="easypack_get_stickers_ret_request" value="0"/>
        <input type="hidden" name="easypack_get_sticker_single_request"
               id="easypack_get_sticker_single_request" value="0"/>
        <input type="hidden" name="get_sticker_order_id"
               id="get_sticker_order_id" value=""/>
        <input type="hidden" name="easypack_get_sticker_single_request_ret"
               id="easypack_get_sticker_single_request_ret" value="0"/>
        <input type="hidden" name="shipment_table_nonce"
               value="<?php echo esc_attr( wp_create_nonce('easypack_shipment_table') ); ?>"/>

		<?php $view_var_shipment_manager_list_table->display(); ?>

    </form>

</div>
