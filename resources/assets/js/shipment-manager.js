jQuery(document).ready(function () {
	jQuery('#easypack_get_courier').click(function () {

		var parcels = [];
		var count_parcels = 0;
		jQuery('input.easypack_parcel').each(function (i) {
			if (jQuery(this).is(':checked')) {
				parcels[i] = jQuery(this).val();
				count_parcels++;
			}
		});
		if (count_parcels == 0) {
			alert('No parcels selected.');
			jQuery("#easypack_spinner_get_stickers").removeClass("is-active");
			return false;
		}
		jQuery('#easypack_create_manifest_input').val(1);
		jQuery('#easypack_dispatch_point').val(jQuery('#dispatch_point').val());
		jQuery("#easypack_shipment_form").submit();
		return false;
	});


	jQuery('#refresh_statuses_btn').click(function () {
		var obj = jQuery('<input>', {
			'type': 'hidden',
			'name': 'refresh_statuses',
			'value': '1'
		});
		var obj2 = jQuery('<input>', {
			'type': 'hidden',
			'name': 'nonce',
			'value': easypack_shipment_manager.nonce
		});
		jQuery('#easypack_shipment_form').append(obj).submit();
		return false;
	});


	jQuery('#get_stickers').click(function () {
		var parcels = [];
		var count_parcels = 0;
		jQuery('input.easypack_parcel').each(function (i) {
			if (jQuery(this).is(':checked')) {
				parcels[i] = jQuery(this).val();
				count_parcels++;
			}
		});
		if (count_parcels === 0) {
			alert('No parcels selected.');
			jQuery('#easypack_spinner_get_stickers').removeClass("is-active");
			return false;
		}

		jQuery('#get_sticker_order_id').val(parcels);
		jQuery('#easypack_get_stickers_request').val('1');
		jQuery('#easypack_shipment_form').attr('target', '_blank');
		jQuery('#easypack_shipment_form').submit();
		jQuery('#easypack_shipment_form').attr('target', '_self');
		jQuery('#easypack_get_stickers_request').val('0');

		return false;
	});

	jQuery('.get_sticker_action').click(function () {
		var parcel = jQuery(this).data('id');

		jQuery('#get_sticker_order_id').val(parcel);
		jQuery('#easypack_get_sticker_single_request').val('1');
		jQuery('#easypack_shipment_form').attr('target', '_blank');
		jQuery('#easypack_shipment_form').submit();
		jQuery('#easypack_shipment_form').attr('target', '_self');
		jQuery('#easypack_get_sticker_single_request').val('0');
		jQuery('#order_id').val('');

		return false;
	});

	jQuery('.get_sticker_return_action').click(function () {
		var parcel = jQuery(this).data('id');

		jQuery('#get_sticker_order_id').val(parcel);
		jQuery('#easypack_get_sticker_single_request_ret').val('1');
		jQuery('#easypack_shipment_form').attr('target', '_blank');
		jQuery('#easypack_shipment_form').submit();
		jQuery('#easypack_shipment_form').attr('target', '_self');
		jQuery('#easypack_get_sticker_single_request_ret').val('0');
		jQuery('#order_id').val('');

		return false;
	});

	jQuery('#get_return_stickers').click(function () {
		var parcels = [];
		var count_parcels = 0;
		jQuery('input.easypack_parcel').each(function (i) {
			if (jQuery(this).is(':checked')) {
				parcels[i] = jQuery(this).val();
				count_parcels++;
			}
		});
		if (count_parcels == 0) {
			alert('No parcels selected.');
			jQuery('#easypack_spinner_get_stickers').removeClass("is-active");
			return false;
		}

		jQuery('#easypack_get_stickers_ret_request').val('1');
		jQuery('#easypack_shipment_form').attr('target', '_blank');
		jQuery('#easypack_shipment_form').submit();
		jQuery('#easypack_shipment_form').attr('target', '_self');
		jQuery('#easypack_get_stickers_ret_request').val('0');

		return false;
	});

	jQuery('.easypack_parcel').change(function () {
		var easypack_get_courier_disabled = false;
		var easypack_get_courier_count = 0;
		jQuery('.easypack_parcel').each(function () {
			if (jQuery(this).is(':checked')) {
				easypack_get_courier_count++;
				if (jQuery(this).data('status') !== 'created'
					&& jQuery(this).data('status') !== 'confirmed') {
					easypack_get_courier_disabled = true;
				}
			}
		});
		if (easypack_get_courier_count == 0) easypack_get_courier_disabled = true;
		jQuery('#easypack_get_courier').attr('disabled', easypack_get_courier_disabled);
	});

});
