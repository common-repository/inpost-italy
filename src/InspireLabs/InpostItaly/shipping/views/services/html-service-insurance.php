<?php /** @var ShipX_Shipment_Model $shipment */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use InspireLabs\WooInpost\EasyPack_Italy;
use InspireLabs\InpostItaly\shipx\models\shipment\ShipX_Shipment_Model;
use InspireLabs\InpostItaly\shipx\models\shipment\ShipX_Shipment_Parcel_Model; ?>

<?php if ($shipment instanceof ShipX_Shipment_Model): ?>
<?php $inputDisabled = ' disabled '?>
    <label disabled style="display: block" for="reference_number" class="graytext">
        <?php esc_html_e('Insurance amount: ', 'inpost-italy'); ?>
    </label>
<?php else: ?>
    <?php $inputDisabled = ''?>
    <label style="display: block" for="reference_number">
        <?php esc_html_e('Insurance amount: ', 'inpost-italy'); ?>
    </label>
<?php endif?>

<?php if ($shipment instanceof ShipX_Shipment_Model && null !== $shipment->getInsurance()): ?>
    <input <?php echo esc_attr( $inputDisabled ); ?>
           class="insurance_amount"
           type="number"
           style=""
           value="<?php echo esc_attr( $shipment->getInsurance()->getAmount() ); ?>"
           placeholder="0.00"
           step="any"
           min="0"
           id="insurance_amounts"
           name="insurance_amounts[]">
<?php else: ?>
    <input <?php echo esc_attr( $inputDisabled ); ?>class="insurance_amount"
           type="number" style=""
           value="<?php echo esc_attr( floatval(get_option('easypack_insurance_amount_default')) ); ?>"
           placeholder="0.00"
           step="any"
           min="0"
           id="insurance_amounts"
           name="insurance_amounts[]<?php echo esc_attr( $inputDisabled ); ?>">
<?php endif; ?>
