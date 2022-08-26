<?php
/**
 * Email mobile messaging
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/email-mobile-messaging.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 6.9.0
 */

echo wp_kses_post( WC_Mobile_Messaging_Handler::prepare_mobile_message( $order_id, $blog_id ) );
