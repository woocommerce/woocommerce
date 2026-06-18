<?php
/**
 * Customer new account email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-new-account.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 11.0.0
 *
 * @var string    $email_heading      Email heading.
 * @var string    $additional_content Additional content below the body.
 * @var string    $user_display_name  Customer's display name.
 * @var string    $user_login         Customer's username.
 * @var string    $user_email         Customer's email address.
 * @var string    $blogname           Site name.
 * @var bool      $sent_to_admin      Whether sent to admin.
 * @var bool      $plain_text         Whether plain-text variant.
 * @var \WC_Email $email              Email object.
 * @var bool      $password_generated Whether password was generated.
 * @var string    $set_password_url   Set password URL.
 * @var string    $verify_url         Email verify URL.
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

/**
 * Fires to output the email header.
 *
 * @hooked WC_Emails::email_header()
 *
 * @since 3.7.0
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php echo $email_improvements_enabled ? '<div class="email-introduction">' : ''; ?>

<?php /* translators: %s: Customer first name, or username if name is not available */ ?>
<p><?php printf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $user_display_name ) ); ?></p>

<?php if ( $email_improvements_enabled ) : ?>
	<?php /* translators: %s: Site title */ ?>
	<p><?php printf( esc_html__( 'Thanks for creating an account on %s. Here’s a copy of your user details.', 'woocommerce' ), esc_html( $blogname ) ); ?></p>
	<div class="hr hr-top"></div>

	<?php /* translators: %s: Username */ ?>
	<p><?php echo wp_kses( sprintf( __( 'Username: <b>%s</b>', 'woocommerce' ), esc_html( $user_login ) ), array( 'b' => array() ) ); ?></p>

	<?php
	if ( $password_generated && $set_password_url ) {
		$button_url    = $set_password_url;
		$button_label  = __( 'Set your new password', 'woocommerce' );
		$link_message  = esc_html__( "Once you've clicked the link and set your new password, we'll link any past orders to your account.", 'woocommerce' );
	} else {
		$button_url   = $verify_url ?? wc_get_page_permalink( 'myaccount' );
		$button_label = __( 'Confirm email address', 'woocommerce' );
		$link_message = sprintf(
			/* translators: %s: the customer's email address. */
			esc_html__( "Once you've confirmed that %s is your email address, we'll link any past orders to your account.", 'woocommerce' ),
			'<strong>' . esc_html( $user_email ) . '</strong>'
		);
	}
	wc_get_template(
		'emails/email-button.php',
		array(
			'url'   => $button_url,
			'label' => $button_label,
		)
	);
	?>

	<p><?php echo wp_kses_post( $link_message ); ?></p>

	<div class="hr hr-bottom"></div>

	<p>
	<?php
	echo wp_kses_post(
		sprintf(
			/* translators: %1$s: account link open, %2$s account link close */
			__( 'You can access your %1$saccount area%2$s to view orders, change your password, and more.', 'woocommerce' ),
			'<a href="' . esc_url( wc_get_page_permalink( 'myaccount' ) ) . '">',
			'</a>'
		)
	);
	?>
	</p>

<?php else : ?>
	<?php /* translators: %1$s: Site title, %2$s: Username, %3$s: My account link */ ?>
	<p><?php printf( esc_html__( 'Thanks for creating an account on %1$s. Your username is %2$s. You can access your account area to view orders, change your password, and more at: %3$s', 'woocommerce' ), esc_html( $blogname ), '<strong>' . esc_html( $user_login ) . '</strong>', make_clickable( esc_url( wc_get_page_permalink( 'myaccount' ) ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
	<?php if ( $password_generated && $set_password_url ) : ?>
		<?php
		// If the password has not been set by the user during the sign up process, send them a link to set a new password.
		?>
		<p><a href="<?php echo esc_attr( $set_password_url ); ?>"><?php printf( esc_html__( 'Click here to set your new password.', 'woocommerce' ) ); ?></a></p>
	<?php endif; ?>
<?php endif; ?>

<?php echo $email_improvements_enabled ? '</div>' : ''; ?>

<?php
/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo $email_improvements_enabled ? '<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation"><tr><td class="email-additional-content email-additional-content-aligned">' : '';
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
	echo $email_improvements_enabled ? '</td></tr></table>' : '';
}

/**
 * Fires to output the email footer.
 *
 * @hooked WC_Emails::email_footer()
 *
 * @since 3.7.0
 */
do_action( 'woocommerce_email_footer', $email );
