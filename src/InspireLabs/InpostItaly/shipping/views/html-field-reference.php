<?php /** @var ShipX_Shipment_Model $shipment */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use InspireLabs\InpostItaly\EasyPack_Italy;
use InspireLabs\InpostItaly\shipx\models\shipment\ShipX_Shipment_Model; ?>

<?php if ( $shipment instanceof ShipX_Shipment_Model ): ?>
    <label disabled style="display: block" for="reference_number"
           class="graytext">
		<?php esc_html_e( 'Reference number: ', 'inpost-italy' ); ?>
    </label>
<?php else: ?>
    <label disabled style="display: block" for="reference_number" class="">
		<?php esc_html_e( 'Reference number: ', 'inpost-italy' ); ?>
    </label>
<?php endif ?>

<?php if ( $shipment instanceof ShipX_Shipment_Model
           && null !== $shipment->getReference()
): ?>
    <input disabled class="reference_number"
           type="text"
           style=""
           value="<?php echo esc_attr( $shipment->getReference() ); ?>"
           id="reference_number"
           name="reference_number">
<?php else:
    $default_ref_number = isset($_GET['post'])
        ? sanitize_text_field($_GET['post'])
        : sanitize_text_field($_GET['id']);

    $ref_number = get_post_meta( $order_id, '_easypack_reference_number', true )
        ? get_post_meta( $order_id, '_easypack_reference_number', true )
        : $default_ref_number;
    ?>
    <input class="reference_number"
           type="text"
           style=""
           value="<?php echo esc_attr( $ref_number ) ?>"
           id="reference_number"
           name="reference_number">
<?php endif; ?>
