<?php
/**
 * Review Order After Shipping EasyPack
 *
 * @author
 * @package    EasyPack/Templates
 * @version
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

?>
<tr class="easypack-parcel-machine">
    <th class="easypack-parcel-machine-label">
        <?php esc_html__( 'Select InPost Point', 'inpost-taly' ); ?>
    </th>
    <td class="easypack-parcel-machine-select">
        <?php if ( defined( 'DOING_AJAX' ) && true === DOING_AJAX ): ?>

            <div class="easypack_italy_geowidget" id="easypack_italy_geowidget">
                <?php echo esc_html__( 'Select InPost Point', 'inpost-taly' ); ?>
            </div>

            <div id="selected-parcel-machine" class="hidden-punto-data">
                <div><span class="font-height-600">
                <?php echo esc_html__( 'Selected Parcel Locker:', 'inpost-taly' ); ?>
                </span></div>
                <span class="italic" id="selected-parcel-machine-id"></span>
                <span class="italic" id="selected-parcel-machine-desc"></span>
                <span class="italic" id="selected-parcel-machine-desc1"></span>

                <input type="hidden" id="parcel_machine_id"
                       name="parcel_machine_id" class="parcel_machine_id"/>
                <input type="hidden" id="parcel_machine_desc"
                       name="parcel_machine_desc" class="parcel_machine_desc"/>
            </div>

        <?php endif ?>
    </td>
</tr>
