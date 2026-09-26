<?php
/**
 * Customer order withdrawal request email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-order-withdrawal-requested.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 11.2.0
 *
 * @var string              $email_heading      Email heading.
 * @var string              $additional_content Additional content below the body.
 * @var array<string,string> $withdrawal_data    Withdrawal request data.
 * @var array<string,string> $detail_rows        Withdrawal request detail rows.
 * @var bool                $sent_to_admin      Whether sent to admin.
 * @var bool                $plain_text         Whether plain-text variant.
 * @var \WC_Email           $email              Email object.
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
<p><?php esc_html_e( 'We have received your request to withdraw from the order below.', 'woocommerce' ); ?></p>
<?php echo $email_improvements_enabled ? '</div>' : ''; ?>

<ul>
	<?php foreach ( $detail_rows as $label => $value ) : ?>
		<li><strong><?php echo esc_html( $label ); ?>:</strong> <?php echo nl2br( esc_html( $value ) ); ?></li>
	<?php endforeach; ?>
</ul>

<?php
if ( $additional_content ) {
	echo $email_improvements_enabled ? '<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation"><tr><td class="email-additional-content email-additional-content-aligned">' : '';
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
