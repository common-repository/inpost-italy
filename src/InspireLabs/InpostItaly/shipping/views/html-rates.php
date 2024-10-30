<?php

use InspireLabs\WoocommerceInpost\EasyPack;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$field = $this->get_field_key( $key );

?>

<tr valign="top">
	<th scope="row" class="titledesc">
		<label for="woocommerce_easypack_italy_parcel_machines_rates"><?php echo esc_html( $data['title'] ); ?></label>
	</th>
	<td class="forminp">
		<table id="<?php echo esc_attr( $field ); ?>" class="easypack_rates wc_input_table sortable widefat">
			<thead>
				<tr>
					<th class="sort">&nbsp;</th>
					<th><?php esc_html_e( 'Min', 'inpost-italy' ); ?></th>
					<th><?php esc_html_e( 'Max', 'inpost-italy' ); ?></th>
					<th><?php esc_html_e( 'Cost', 'inpost-italy' ); ?></th>
					<th><?php esc_html_e( 'Action', 'inpost-italy' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php $count = 0; ?>
				<?php if( is_array( $rates ) ): ?>
                    <?php foreach ( $rates as $key => $rate) : $count++; ?>
                        <?php if( is_array( $rate ) ): ?>
                            <tr>
                                <td class="sort"></td>
                                <td>
                                    <input class="input-text regular-input" type="number" style="" value="<?php echo esc_attr ($rate['min'] ); ?>" placeholder="0.00" step="any" min="0" name=rates[<?php echo esc_attr( $count ); ?>][min]>
                                </td>
                                <td>
                                    <input class="input-text regular-input" type="number" style="" value="<?php echo esc_attr( $rate['max'] ); ?>" placeholder="0.00" step="any" min="0" name=rates[<?php echo esc_attr( $count ); ?>][max]>
                                </td>
                                <td>
                                    <input class="input-text regular-input" type="number" style="" value="<?php echo esc_attr( $rate['cost'] ); ?>" placeholder="0.00" step="any" min="0" name=rates[<?php echo esc_attr( $count ); ?>][cost]>
                                </td>
                                <td>
                                    <a id="delete_rate_<?php echo esc_attr( $count ); ?>" href="#" class="button delete_rate" data-id="<?php echo esc_attr( $count ); ?>"><?php esc_html_e( 'Delete row', 'inpost-italy' ); ?></a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
				<?php endif; ?>
			</tbody>
			<tfoot>
				<tr>
					<th colspan="5">
						<a id="insert_rate" href="#" class="button plus insert"><?php esc_html_e( 'Insert row', 'inpost-italy' ); ?></a>
					</th>
				</tr>
			</tfoot>
		</table>
	</td>
</tr>



