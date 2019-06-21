<?php
/**
 * Admin View: Notice - PHP & WP minimum requirements.
 *
 * @package WooCommerce\Admin\Notices
 */

defined( 'ABSPATH' ) || exit;
if ( $old_php && $old_wp ) {
	$msg = __( 'Your store is running on outdated versions of WordPress and PHP. In future releases of WooCommerce we will not be supporting these outdated versions.', 'woocommerce' );
} elseif ( $php_update_required ) {
	$msg = __( 'Your store is running on an outdated version of PHP. In future releases of WooCommerce we will not be supporting this outdated version.', 'woocommerce' );
} else {
	$msg = __( 'Your store is running on an outdated version of WordPress. In future releases of WooCommerce we will not be supporting this outdated version.', 'woocommerce' );
}
?>
<div id="message" class="updated woocommerce-message">
	<a class="woocommerce-message-close notice-dismiss" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wc-hide-notice', 'wp_php_min_requirements' ), 'woocommerce_hide_notices_nonce', '_wc_notice_nonce' ) ); ?>"><?php esc_html_e( 'Dismiss', 'woocommerce' ); ?></a>

	<p>
		<?php
		echo wp_kses_post(
			sprintf(
				/* translators: %s: WP & PHP Upgrade URL */
				$msg . '<p><a href="%s" class="button button-primary">Learn More</a></p>',
				get_admin_url( null, '#woocommerce_php_wp_nag' )
			)
		);
		?>
	</p>
</div>
