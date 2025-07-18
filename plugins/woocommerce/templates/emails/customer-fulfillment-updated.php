<?php
/**
 * Customer fulfillment updated email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-fulfillment-updated.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\HTML
 * @version 10.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Hook for the woocommerce_email_header.
 *
 * @param string $email_heading The email heading.
 * @param WC_Email $email The email object.
 * @since 2.5.0
 *
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<div class="email-introduction">
	<p><?php echo esc_html__( 'Some details of your shipment have recently been updated. This may include tracking information, item contents, or delivery status.', 'woocommerce' ); ?></p>
	<p><?php echo esc_html__( 'Here’s the latest info we have:', 'woocommerce' ); ?></p>
</div>

<?php

/**
 * Hook for the woocommerce_email_fulfillment_details.
 *
 * @since 10.1.0
 * @param WC_Order $order The order object.
 * @param Fulfillment $fulfillment The fulfillment object.
 * @param bool $sent_to_admin Whether the email is sent to admin.
 * @param bool $plain_text Whether the email is plain text.
 * @param WC_Email $email The email object.
 *
 * @hooked WC_Emails::fulfillment_details() Shows the fulfillment details.
 */
do_action( 'woocommerce_email_fulfillment_details', $order, $fulfillment, $sent_to_admin, $plain_text, $email );

/**
 * Hook for the woocommerce_email_fulfillment_meta.
 *
 * @param WC_Order $order The order object.
 * @param Fulfillment $fulfillment The fulfillment object.
 * @param bool $sent_to_admin Whether the email is sent to admin.
 * @param bool $plain_text Whether the email is plain text.
 * @param WC_Email $email The email object.
 * @since 10.1.0
 *
 * @hooked WC_Emails::order_meta() Shows fulfillment meta data.
 */
do_action( 'woocommerce_email_fulfillment_meta', $order, $fulfillment, $sent_to_admin, $plain_text, $email );

/**
 * Hook for woocommerce_email_customer_details.
 *
 * @param WC_Order $order The order object.
 * @param bool $sent_to_admin Whether the email is sent to admin.
 * @param bool $plain_text Whether the email is plain text.
 * @param WC_Email $email The email object.
 * @since 2.5.0
 *
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo '<table border="0" cellpadding="0" cellspacing="0" width="100%"><tr><td class="email-additional-content">';
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
	echo '</td></tr></table>';
}

/**
 * Hook for the woocommerce_email_footer.
 *
 * @param WC_Email $email The email object.
 * @since 2.5.0
 *
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
