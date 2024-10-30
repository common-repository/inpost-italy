jQuery(document).ready(function(){

    const { __, _x, _n, sprintf } = wp.i18n;

    var mediaUploader;

    jQuery('.woo-inpost-logo-upload-btn').on('click',function(e) {
        e.preventDefault();

        if( mediaUploader ){
            mediaUploader.open();
            return;
        }

        mediaUploader = wp.media.frames.file_frame =wp.media({
            title: __('Choose a shipping method logo', 'inpost-italy'),
            button: {
                text: __('Choose Image', 'inpost-italy')
            },
            multiple:false
        });

        mediaUploader.on('select', function(){
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            jQuery('#woocommerce_easypack_logo_upload').val(attachment.url);
            jQuery('#woo-inpost-logo-preview').attr('src',attachment.url);
            jQuery('#woo-inpost-logo-preview').css('display', 'block');
            jQuery('#woo-inpost-logo-action').css('display', 'block');
        });
        mediaUploader.open();
    });

    jQuery('#woo-inpost-logo-delete').on('click',function(e) {
        e.preventDefault();
        jQuery('#woo-inpost-logo-preview').css('display', 'none');
        jQuery('#woocommerce_easypack_logo_upload').val('');
        jQuery('#woo-inpost-logo-action').css('display', 'none');
    });

    jQuery('.easypack_parcel').click(function () {

        var allowReturnStickers = jQuery(this).data('allow_return_stickers') === 1;
        if (jQuery(this).is(':checked')) {
            if (false === allowReturnStickers) {
                jQuery('#get_return_stickers').prop('disabled', true);
            }
        } else {
            if (false === allowReturnStickers) {
                jQuery('#get_return_stickers').removeAttr('disabled');
            }
        }
    });
});