<?php


namespace InspireLabs\InpostItaly\EmailFilters;

use InspireLabs\InpostItaly\EasyPack_Italy;

class TrackingInfoEmail
{
    public function send_tracking_info_email( $order, $tracking_url, $tracking_number) {
        $order_email = $order->get_billing_email();
        // load the mailer class
        $mailer = WC()->mailer();
        $recipient = $order_email;
        $subject = esc_html__( 'Your order has been given a tracking number', 'inpost-italy' );
        $content = $this->get_tracking_info_email_html( $tracking_url, $tracking_number, $order, $mailer, $subject );
        $headers = "Content-Type: text/html\r\n";
        $mailer->send( $recipient, $subject, $content, $headers );
    }

    private function get_tracking_info_email_html( $tracking_link, $tracking_number, $order, $mailer, $heading = false ) {

        return wc_get_template_html( 'emails/send-tracking-to-buyer.php', array(
            'tracking_link'   => $tracking_link,
            'tracking_number' => $tracking_number,
            'order'           => $order,
            'email_heading'   => $heading,
            'sent_to_admin'   => false,
            'plain_text'      => false,
            'email'           => $mailer
        ) );
    }


}