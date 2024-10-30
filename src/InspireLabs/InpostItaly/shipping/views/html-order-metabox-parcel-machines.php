<?php /** @var ShipX_Shipment_Model $shipment */

use InspireLabs\InpostItaly\EasyPack_Italy;
use InspireLabs\InpostItaly\EasyPack_Italy_Helper;
use InspireLabs\InpostItaly\shipx\models\shipment\ShipX_Shipment_Model;
use InspireLabs\InpostItaly\shipx\models\shipment\ShipX_Shipment_Parcel_Model; ?>
<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} ?>

<?php
$status_service = inpost_italy()->get_shipment_status_service();
?>

<?php if ( true === $wrong_api_env ): ?>
	<?php
	$internal_data = $shipment->getInternalData();
	$origin_api    = $shipment->getInternalData()->getApiVersion();
	if ( $internal_data->getApiVersion()
	     === $internal_data::API_VERSION_PRODUCTION
	):?>

	<?php endif; ?>

	<?php if ( $internal_data->getApiVersion()
	           === $internal_data::API_VERSION_PRODUCTION
	): ?>
        <span style="font-weight: bold; color: #a00">
            <?php esc_html_e( 'This shipment was created in production API. Change API environment to production to process this shipment',
                'inpost-italy' ) ?>
        </span>

	<?php endif; ?>

	<?php if ( $internal_data->getApiVersion()
	           === $internal_data::API_VERSION_SANDBOX
	): ?>
        <span style="font-weight: bold; color: #a00">
            <?php esc_html_e( 'This shipment was created in sandbox API. Change API environment to sandbox to process this shipment',
                'inpost-italy' ) ?>
        </span>
	<?php endif; ?>
	<?php return; ?>
<?php endif; ?>

<?php $first_parcel = true;
$shipment_service   = inpost_italy()->get_shipment_service();
?>

<?php
$class             = [ 'wc-enhanced-select' ];
$custom_attributes = [ 'style' => 'width:100%;' ];
if ( $disabled ) {
	$custom_attributes['disabled'] = 'disabled';
	$class[]                       = 'easypack-disabled';
}
?>
<p>
    <label for="parcel_machine_id"><?php esc_html_e( 'Selected InPost point', 'inpost-italy' ) ?></label>
    <input value="<?php echo esc_attr( $parcel_machine_id ); ?>" type="text"
           class="settings-geowidget" id="parcel_machine_id"
           name="parcel_machine_id"
           >
</p>
<p>
    <span style="font-weight: bold"><?php esc_html_e( 'Service:', 'inpost-italy' ) ?>
    </span>
    <span>
        <?php echo esc_html( $selected_service );
        ?>
    </span>
</p>

<p><span style="font-weight: bold"><?php esc_html_e( 'API Status:', 'inpost-italy' ) ?> </span>
<?php if ( $shipment instanceof ShipX_Shipment_Model ): ?>
    <?php if( ! empty(get_option('easypack_organization_id_italy')) && ! empty(get_option('easypack_token_italy')) ) { ?>
        <?php $status = $shipment->getInternalData()->getStatus() ?>
        <?php //$status_title = $shipment->getInternalData()->getStatusTitle() ?>
        <?php $status_desc = $shipment->getInternalData()->getStatusDescription() ?>
        <span title="<?php echo esc_attr( $status_desc ); ?>">
            <?php echo esc_html( $status ); ?>
        </span>
    <?php } ?>
</p>
<?php if ( $shipment->isCourier() ) {
	$send_method = 'courier';
}

if ( $shipment->isParcelMachine() ) {
	$send_method = 'parcel_machine';
}
?>
<?php else: ?>
    <?php if( ! empty(get_option('easypack_organization_id_italy')) && ! empty(get_option('easypack_token_italy')) ) { ?>
	    <?php esc_html_e( 'Not created yet (new)', 'inpost-italy' ) ?>
    <?php } ?>
<?php endif ?>

<?php if ( ! empty( $shipment instanceof ShipX_Shipment_Model
                    && $shipment->getInternalData()->getTrackingNumber() )
): ?>
    <span style="font-weight: bold">
            <?php esc_html_e( 'Tracking number:', 'inpost-italy' ) ?>
    </span>

    <a target="_blank"
       href="<?php echo esc_url( $shipment_service->getTrackingUrl( $shipment ) ); ?>">
		<?php echo esc_html( $shipment->getInternalData()->getTrackingNumber() ); ?>
    </a>
    <div class="padding-bottom15"></div>
<?php endif ?>



<p><?php esc_html_e( 'Attributes:', 'inpost-italy' ); ?>
<ul id="easypack_parcels" style="list-style: none">
	<?php /** @var ShipX_Shipment_Parcel_Model $parcel */ ?>
	<?php /** @var ShipX_Shipment_Parcel_Model[] $parcels */ ?>
    <?php if( ! empty( get_option('easypack_organization_id_italy') )
        && ! empty( get_option('easypack_token_italy') ) ) { ?>
        <?php foreach ( $parcels as $parcel ) : ?>
            <li>
                <?php if ( $status == 'new' ) : ?>
                    <?php
                    $params = [
                        'type'        => 'select',
                        'options'     => $package_sizes,
                        'class'       => [ 'easypack_parcel' ],
                        'input_class' => [ 'easypack_parcel' ],
                        'label'       => '',
                    ];

                    $saved_meta_data = get_post_meta( $order_id, '_easypack_parcels', true );

                    $saved_package_size = isset( $saved_meta_data[0]['package_size'] )
                        ? $saved_meta_data[0]['package_size']
                        : $parcel->getTemplate();

                    woocommerce_form_field( 'parcel[]', $params, $saved_package_size );
                    ?>

                <?php else : ?>
                    <?php esc_html_e( 'Size', 'inpost-italy' ); ?>:
                    <?php echo '<span style="font-size: 16px">'; ?>
                    <?php echo esc_html( inpost_italy_helper()->convert_size_to_symbol( $parcel->getTemplate() ) ); ?>
                    <?php echo '</span>'; ?>
                <?php endif; ?>
            </li>
            <?php $first_parcel = false; ?>
        <?php endforeach; ?>
    <?php } ?>
</ul>

</p>

<?php if( ! empty( get_option('easypack_organization_id_italy') )
    && ! empty (get_option('easypack_token_italy') ) ) { ?>
    <?php //include( 'services/html-service-insurance.php' ); ?>
    <?php include( 'html-field-reference.php' ); ?>
<?php } ?>

<?php
$custom_attributes = [ 'style' => 'width:100%;' ];
if ( $disabled || $send_method_disabled ) {
	$custom_attributes['disabled'] = 'disabled';
}
$params = [
	'type'              => 'select',
	'options'           => $send_methods,
	'class'             => [ 'wc-enhanced-select' ],
	'custom_attributes' => $custom_attributes,
	'label'             => esc_html__( 'Send method', 'inpost-italy' ),
];

//woocommerce_form_field( 'easypack_send_method', $params, $send_method );
?>

<p>
    <?php if( ! empty(get_option('easypack_organization_id_italy')) && ! empty(get_option('easypack_token_italy')) ) { ?>
        <?php if ( $status == 'new' ) : ?>
            <button id="easypack_send_parcels"
                    class="button button-primary"><?php esc_html_e( 'Send parcel', 'inpost-italy' ); ?></button>
        <?php endif; ?>

        <?php if ( $status == 'offer_selected' ) : ?>
            <p id="easypack_error">
                <?php esc_html_e( 'The package has not been created! Probably you do not have funds or a contract for InPost services', 'inpost-italy' ); ?>
            </p>
        <?php endif; ?>

        <?php if ( $shipment instanceof ShipX_Shipment_Model
                   && ! empty( $shipment->getInternalData()->getTrackingNumber() ) ) : ?>
            <input id="inpost_it_get_stickers" type="submit" class="button button-primary"
                   value="<?php esc_html_e( 'Get label', 'inpost-italy' ); ?>">
            <input type="hidden" name="easypack_get_stickers_request"
                   id="easypack_get_stickers_request">
            <input type="hidden" name="easypack_parcel"
                   value="<?php echo esc_attr( $shipment->getInternalData()->getOrderId() ); ?>">
        <?php endif; ?>

    <?php } ?>
    <span id="easypack_spinner" class="spinner"></span>
</p>

<p id="easypack_error"></p>
