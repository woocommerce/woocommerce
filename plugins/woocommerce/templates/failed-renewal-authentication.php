<?php
/**
 * Customer email about a renewal payment requiring authentication.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/failed-renewal-authentication.php.
 *
 * @package WooCommerce\Templates\Emails
 * @version 11.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Hook for the woocommerce_email_header.
 *
 * @hooked WC_Emails::email_header() Output the email header
 * @since 3.7.0
 */
do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<p>
	<?php
	echo wp_kses(
		sprintf(
			/* translators: %1$s: site title, %2$s: authorization link. */
			_x( 'The automatic payment to renew your subscription with %1$s has failed. To reactivate the subscription, please log in and authorize the renewal from your account page: %2$s', 'In failed renewal authentication email', 'woocommerce' ),
			esc_html( get_bloginfo( 'name' ) ),
			'<a href="' . esc_url( $authorization_url ) . '">' . esc_html__( 'Authorize the payment', 'woocommerce' ) . '</a>'
		),
		array(
			'a' => array(
				'href' => true,
			),
		)
	);
	?>
</p>

<?php
/**
 * Hook for the woocommerce_subscriptions_email_order_details.
 *
 * @since 11.0.0
 */
do_action( 'woocommerce_subscriptions_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * Hook for the woocommerce_email_footer.
 *
 * @hooked WC_Emails::email_footer() Output the email footer
 * @since 3.7.0
 */
do_action( 'woocommerce_email_footer', $email );
