jQuery(document).ready(function () {

    jQuery('#easypack_send_parcels').click(function (e) {
        jQuery('#easypack_error').html('');
        jQuery(this).attr('disabled', true);
        jQuery("#easypack_spinner").addClass("is-active");
        var parcels = [];
        jQuery('select.easypack_parcel').each(function (i) {
            parcels[i] = jQuery(this).val();
        });

        if (!parcels.length) {
            let alternate_parcels_find = jQuery('#easypack_parcels').find('select').val();
            parcels.push(alternate_parcels_find);
        }

        let order_number = '';
        let nonce = '';
        if (typeof easypack_it != 'undefined' && easypack_it !== null) {
            if (easypack_it.hasOwnProperty('order_id')) {
                order_number = easypack_it.order_id;
            }
            if (easypack_it.hasOwnProperty('easypack_nonce')) {
                nonce = easypack_it.easypack_nonce;
            }
        }

        var data = {
            action: 'easypack',
            easypack_action: 'parcel_machines_create_package',
            security: nonce,
            order_id: order_number,
            parcel_machine_id: jQuery('#parcel_machine_id').val(),
            parcels: parcels,
            send_method: jQuery('#easypack_send_method').val(),
            insurance_amounts: [],
            reference_number: jQuery('#reference_number').val()
        };
        jQuery.post(ajaxurl, data, function (response) {
            console.log(response);
            if (response != 0) {
                response = JSON.parse(response);
                console.log(response);
                console.log(response.status);
                if (response.status == 'ok') {
                    jQuery("#easypack_italy_parcel_machines .inside").html(response.content);

                    return false;
                } else {
                    console.log(response.message);
                    jQuery('#easypack_error').html(response.message);
                }
            } else {
                jQuery('#easypack_error').html('Invalid response.');
            }
            jQuery("#easypack_spinner").removeClass("is-active");
            jQuery('#easypack_send_parcels').attr('disabled', false);
        });
        return false;

    });
});

document.addEventListener('click', function (e) {
    e = e || window.event;
    var target = e.target || e.srcElement;
    if (target.hasAttribute('id') && target.getAttribute('id') === 'inpost_it_get_stickers') {
        e.preventDefault();
        e.stopPropagation();
        jQuery('#easypack_error').html('');

        var beforeSend = function () {
            jQuery("#easypack_spinner").addClass("is-active");
            jQuery('#easypack_send_parcels').attr('disabled', true);
        };

        let order_number = '';
        let nonce = '';
        if (typeof easypack_it != 'undefined' && easypack_it !== null) {
            if (easypack_it.hasOwnProperty('order_id')) {
                order_number = easypack_it.order_id;
            }
            if (easypack_it.hasOwnProperty('easypack_nonce')) {
                nonce = easypack_it.easypack_nonce;
            }
        }

        var action = 'easypack';
        var easypack_action = 'easypack_italy_create_bulk_labels';
        var order_ids = order_number;
        beforeSend();
        var request = new XMLHttpRequest();
        request.open('POST', ajaxurl, true);
        request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
        request.responseType = 'blob';
        request.onload = function () {
            // Only handle status code 200
            if (request.status === 200 && request.response.size > 0) {
                var content_type = request.getResponseHeader("content-type");
                if (content_type === 'application/pdf') {
                    var filename = 'inpost_ordine_' + order_ids + '.pdf';
                    // download file
                    var blob = new Blob([request.response], {type: 'application/pdf'});
                    var link = document.createElement('a');
                    link.href = window.URL.createObjectURL(blob);
                    link.download = filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                } else {
                    // some error occured
                    let text_from_blob = new Blob([request.response], {type: 'text/html'});
                    console.log(text_from_blob);
                    var reader = new FileReader();
                    reader.onload = function () {
                        let textResponse = JSON.parse(reader.result);
                        console.log(textResponse);
                        if (textResponse.details.key == 'ParcelLabelExpired') {
                            jQuery('#easypack_error').html('L\'etichetta è scaduta');
                        } else {
                            jQuery('#easypack_error').html('Si è verificato un errore');
                        }
                    };
                    reader.readAsText(text_from_blob);
                    jQuery("#easypack_spinner").removeClass("is-active");
                    jQuery('#easypack_send_parcels').attr('disabled', false);
                    return;
                }

                jQuery("#easypack_spinner").removeClass("is-active");
                jQuery('#easypack_send_parcels').attr('disabled', false);
            } else {
                jQuery('#easypack_error').html('Si è verificato un errore');
            }

            jQuery("#easypack_spinner").removeClass("is-active");
            jQuery('#easypack_send_parcels').attr('disabled', false);
        };

        request.send('action=' + action + '&easypack_action=' + easypack_action + '&security=' + nonce + '&order_ids=' + JSON.stringify([order_ids]));
    }
});