jQuery(document).ready(function () {

    var easypack_token = jQuery('#easypack_token_italy').val();
    var easypack_org_id = jQuery('#easypack_organization_id_italy').val();

    if( typeof easypack_token != 'undefined' && easypack_token !== null ) {
        if (!easypack_token.length) {
            jQuery('#easypack_token_italy').parent('td.forminp-text').find('.easypack_access_notice').addClass('warning');
        }
    }

    if( typeof easypack_org_id != 'undefined' && easypack_org_id !== null ) {
        if (!easypack_org_id.length) {
            jQuery('#easypack_organization_id_italy').parent('td.forminp-text').find('.easypack_access_notice').addClass('warning');
        }
    }

    jQuery('#easypack_organization_id_italy').change(function () {
        jQuery('#easypack_api_change').val('1');
    });

    jQuery('#easypack_italy_api_environment').change(function () {
        jQuery('#easypack_api_change').val('1');
    });

    jQuery('#easypack_token_italy').change(function () {
        jQuery('#easypack_api_change').val('1');
        let token = jQuery('#easypack_token_italy').val();
    });

    jQuery('#easypack_token_italy').keyup(function () {
        if (easypack_token !== jQuery('#easypack_token_italy').val()) {
            jQuery('#easypack_api_change').val('1');
        }
    });

    let sender_phone = jQuery('input[name="easypack_italy_sender_phone"]');
    if( typeof sender_phone != 'undefined' ) {
        //jQuery('input[name="easypack_italy_sender_phone"]').mask("399 999 999?9",{placeholder:" "});
    }
	
	let flow_type = jQuery('#easypack_italy_flow_type').val();
	if (flow_type === 'A2L') {
		show_additional_settings();
	} else {
		hide_additional_settings();
	}
	
	jQuery('#easypack_italy_flow_type').on('change', function () {
        if (jQuery(this).val() === 'A2L') {
            show_additional_settings();
        } else {
            hide_additional_settings();
        }
    });
	
	function show_additional_settings() {
		jQuery('.easypack_italy_hidden_setting').each(function (i, elem) {
			jQuery(elem).attr('required', 'required');
			let parent = jQuery(elem).closest('tr[valign="top"]');
			let section_title = jQuery(parent).closest('table').prev('h2');
			jQuery(parent).fadeIn(300);
			jQuery(section_title).fadeIn(300);
		});
	}
	
	function hide_additional_settings() {
		jQuery('.easypack_italy_hidden_setting').each(function (i, elem) {
				jQuery(elem).removeAttr('required');
                let parent = jQuery(elem).closest('tr[valign="top"]');
				let section_title = jQuery(parent).closest('table').prev('h2');
                jQuery(parent).fadeOut(100);
                jQuery(section_title).fadeOut(100);
            });
	}

});