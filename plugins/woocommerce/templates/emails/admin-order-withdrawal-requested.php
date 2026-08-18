<?php
/**
 * Admin order withdrawal request email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/admin-order-withdrawal-requested.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 11.1.0
 *
 * @var string               $email_heading             Email heading.
 * @var string               $additional_content        Additional content below the body.
 * @var array<string,string> $withdrawal_data           Withdrawal request data.
 * @var array<string,string> $detail_rows               Withdrawal request detail rows.
 * @var \WC_Order|null       $matched_order             Matched order, if found.
 * @var bool                 $outside_withdrawal_window Whether the matched order is outside the withdrawal window.
 * @var string               $withdrawal_window_warning Withdrawal window warning message.
 * @var bool                 $sent_to_admin             Whether sent to admin.
 * @var bool                 $plain_text                Whether plain-text variant.
 * @var \WC_Email            $email                     Email object.
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

/**
 * Fires to output the email header.
 *
 * @hooked WC_Emails::email_header()
 * @since 3.7.0
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php echo $email_improvements_enabled ? '<div class="email-introduction">' : ''; ?>
<p><?php esc_html_e( 'A customer submitted an order withdrawal request.', 'woocommerce' ); ?></p>
<?php if ( $matched_order instanceof WC_Order ) : ?>
	<p><?php esc_html_e( 'WooCommerce matched this request to an order and added an order note.', 'woocommerce' ); ?></p>
<?php else : ?>
	<p><?php esc_html_e( 'WooCommerce could not match this request to an order automatically, so no order note was added.', 'woocommerce' ); ?></p>
<?php endif; ?>
<?php echo $email_improvements_enabled ? '</div>' : ''; ?>

<ul>
	<?php foreach ( $detail_rows as $label => $value ) : ?>
		<li><strong><?php echo esc_html( $label ); ?>:</strong> <?php echo nl2br( esc_html( $value ) ); ?></li>
	<?php endforeach; ?>
</ul>

<?php if ( $matched_order instanceof WC_Order ) : ?>
	<?php if ( $outside_withdrawal_window ) : ?>
		<p><?php echo esc_html( $withdrawal_window_warning ); ?></p>
	<?php endif; ?>
	<p>
		<?php
		printf(
			/* translators: %d: order ID. */
			esc_html__( 'Matched order ID: %d', 'woocommerce' ),
			absint( $matched_order->get_id() )
		);
		?>
	</p>
	<?php if ( '' !== $matched_order->get_edit_order_url() ) : ?>
		<p><a href="<?php echo esc_url( $matched_order->get_edit_order_url() ); ?>"><?php esc_html_e( 'View matched order', 'woocommerce' ); ?></a></p>
	<?php endif; ?>
<?php endif; ?>

<?php
if ( $additional_content ) {
	echo $email_improvements_enabled ? '<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation"><tr><td class="email-additional-content">' : '';
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
	echo $email_improvements_enabled ? '</td></tr></table>' : '';
}

/**
 * Fires to output the email footer.
 *
 * @hooked WC_Emails::email_footer()
 * @since 3.7.0
 */
do_action( 'woocommerce_email_footer', $email );
