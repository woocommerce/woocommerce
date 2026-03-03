<?php
/**
 * General block content for fulfillment emails
 *
 * Note: This template is only used in the fulfillment emails.
 *
 * Used to render information for the email editor WooCommerce content block (BlockEmailRenderer::WOO_EMAIL_CONTENT_PLACEHOLDER).
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\Block
 * @version 10.5.0
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable Squiz.PHP.EmbeddedPhp.ContentBeforeOpen -- removed to prevent empty new lines.
// phpcs:disable Squiz.PHP.EmbeddedPhp.ContentAfterEnd -- removed to prevent empty new lines.

if ( ! isset( $order, $fulfillment ) ) {
	return;
}

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
