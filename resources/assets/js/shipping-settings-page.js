jQuery(document).ready(function () {
	
    
    let easypack_flat_rate = jQuery('.easypack_flat_rate');
    if( typeof easypack_flat_rate != 'undefined' && easypack_flat_rate !== null ) {
        jQuery(easypack_flat_rate).change(function () {
            display_rates();
        });
        display_rates();
    }
});

function display_rates() {
	if (jQuery('.easypack_flat_rate').prop('checked')) {
		jQuery('.easypack_cost_per_order').closest('tr').css('display', 'table-row');
		jQuery('.easypack_based_on').closest('tr').css('display', 'none');
		jQuery('.easypack_rates').closest('tr').css('display', 'none');

		jQuery('.easypack_gabaryt_a').closest('tr').css('display', 'none');
		jQuery('.easypack_gabaryt_b').closest('tr').css('display', 'none');
		jQuery('.easypack_gabaryt_c').closest('tr').css('display', 'none');

	} else {
		jQuery('.easypack_cost_per_order').closest('tr').css('display', 'none');
		jQuery('.easypack_based_on').closest('tr').css('display', 'table-row');
		jQuery('.easypack_rates').closest('tr').css('display', 'table-row');

		let select_position = jQuery('#woocommerce_easypack_italy_parcel_machines_based_on').val();

		if(select_position === 'size') {
			jQuery('#woocommerce_easypack_italy_parcel_machines_rates').closest('tr').hide();
			jQuery('#woocommerce_easypack_italy_parcel_machines_rates').hide(); // on parcel lockers settings page
			jQuery('#woocommerce_easypack_italy_shipping_courier_c2c_rates').closest('tr').hide();
			jQuery('#woocommerce_easypack_italy_shipping_courier_c2c_rates').hide(); // on c2c courier settings page
			jQuery('.easypack_gabaryt_a').closest('tr').show();
			jQuery('.easypack_gabaryt_b').closest('tr').show();
			jQuery('.easypack_gabaryt_c').closest('tr').show();
		}
	}
}

function append_row( id ) {
    const { __, _x, _n, sprintf } = wp.i18n;
    const text = 'Elimina riga';
    var code = '<tr class="new">\
                    <td class="sort"></td>\
                    <td>\
                        <input id="rates_'+id+'_min" class="input-text regular-input" type="number" style="" value="" placeholder="0.00" step="any" min="0" name=rates[' + id + '][min]>\
                    </td>\
                    <td>\
                        <input class="input-text regular-input" type="number" style="" value="" placeholder="0.00" step="any" min="0" name=rates[' + id + '][max]>\
                    </td>\
                    <td>\
                        <input class="input-text regular-input" type="number" style="" value="" placeholder="0.00" step="any" min="0" name=rates[' + id + '][cost]>\
                    </td>\
                    <td>\
                        <a id="delete_rate_'+id+'" href="#" class="button delete_rate" data-id="'+id+'">'+text+'</a>\
                    </td>\
                </tr>';
    var $tbody = jQuery('.easypack_rates').find('tbody');
    $tbody.append( code );
}


jQuery(document).ready(function() {
    const { __, _x, _n, sprintf } = wp.i18n;
    const warning = __('Are you sure?', 'inpost-italy');

    var $tbody = jQuery('.easypack_rates').find('tbody');
    var append_id = $tbody.find('tr').size();
    var size = $tbody.find('tr').size();
    if ( size == 0 ) {
        append_id = append_id+1;
        append_row(append_id);
    }
    jQuery('#insert_rate').click(function() {
        append_id = append_id+1;
        append_row(append_id);
        jQuery('#rates_'+append_id+'_min').focus();
        return false;
    });
    jQuery(document).on('click', '.delete_rate',  function() {
        if (confirm(warning)) {
            jQuery(this).closest('tr').remove();
        }
        return false;
    });

    // show-hide size fields
    let select_position = jQuery('#woocommerce_easypack_italy_parcel_machines_based_on').val();

    show_hide_gabaryt_rows(select_position);

    function show_hide_gabaryt_rows(select_position) {
        if(select_position === 'size') {
            jQuery('#woocommerce_easypack_italy_parcel_machines_rates').closest('tr').hide();
            jQuery('#woocommerce_easypack_italy_parcel_machines_rates').hide();
            jQuery('.easypack_gabaryt_a').closest('tr').show();
            jQuery('.easypack_gabaryt_b').closest('tr').show();
            jQuery('.easypack_gabaryt_c').closest('tr').show();
            jQuery('#easypack_dimensions_warning').parent('p').show();
        } else {
            jQuery('#easypack_dimensions_warning').parent('p').hide();
            jQuery('.easypack_gabaryt_a').closest('tr').hide();
            jQuery('.easypack_gabaryt_b').closest('tr').hide();
            jQuery('.easypack_gabaryt_c').closest('tr').hide();
            jQuery('#woocommerce_easypack_italy_parcel_machines_rates').show();
            jQuery('#woocommerce_easypack_italy_parcel_machines_rates').closest('tr').show();
        }
    }

    jQuery('#woocommerce_easypack_italy_parcel_machines_based_on').on('change', function () {
        show_hide_gabaryt_rows(jQuery(this).val());
    });
});