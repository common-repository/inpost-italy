<?php
/**
 * Seller create the parcel and tracking number can be provided now
 *
 * This template was overridden by copying it to yourtheme/woocommerce/emails/customer-completed-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 3.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use InspireLabs\InpostItaly\EasyPack_Italy;

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php /* translators: %s: Customer first name */ ?>
    <p><?php printf( esc_html__( 'Hi %s,', 'inpost-italy' ), esc_html( $order->get_billing_first_name() ) ); ?></p>
    <p><?php esc_html_e( 'A tracking number has been given for your order. It will soon move on its journey', 'inpost-italy' ); ?></p>
    <?php if( ! empty( $tracking_number ) ) {
        $tracking_number = sanitize_text_field( $tracking_number );
        $tracking_link = esc_url( $tracking_link );
        $parcel_tracking = $tracking_link . $tracking_number;

        ?>
        <p><?php esc_html_e( 'Tracking link:', 'inpost-italy' ); ?></p>
        <p><?php printf(
                /* translators: %s: Tracking link */
                '<a href="%1$s">%2$s</a>',
                esc_html( $parcel_tracking ),
                esc_html( $parcel_tracking )
            ); ?>
        </p>
        <br>
    <?php } ?>
<?php

/*
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( isset( $additional_content ) ) {
    echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
