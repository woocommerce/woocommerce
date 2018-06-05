<?php
/**
 * Missing WooCommerce notice.
 *
 * @package WC_Beta_Tester\Admin\Views
 */

defined( 'ABSPATH' ) || exit;

?>

<div class="notice notice-error">
	<p><strong><?php esc_html_e( 'WooCommerce Beta Tester', 'woocommerce-beta-tester' ); ?></strong> <?php esc_html_e( 'requires WooCommerce to be installed and enabled.', 'woocommerce-beta-tester' ); ?></p>

	<?php if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) && current_user_can( 'activate_plugin', 'woocommerce/woocommerce.php' ) ) : ?>
		<p>
			<a href="<?php echo esc_url( wp_nonce_url( self_admin_url( 'plugins.php?action=activate&plugin=woocommerce/woocommerce.php&plugin_status=active' ), 'activate-plugin_woocommerce/woocommerce.php' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Active WooCommerce', 'woocommerce-beta-tester' ); ?></a>
			<?php if ( current_user_can( 'deactivate_plugin', 'woocommerce-beta-tester/woocommerce-beta-tester.php' ) ) : ?>
				<a href="<?php echo esc_url( wp_nonce_url( 'plugins.php?action=deactivate&plugin=woocommerce-beta-tester/woocommerce-beta-tester.php&plugin_status=inactive', 'deactivate-plugin_woocommerce-beta-tester/woocommerce-beta-tester.php' ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Turn off Beta Tester plugin', 'woocommerce-beta-tester' ); ?></a>
			<?php endif; ?>
		</p>
	<?php else : ?>
		<?php
		if ( current_user_can( 'install_plugins' ) ) {
			$url = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=woocommerce' ), 'install-plugin_woocommerce' );
		} else {
			$url = 'http://wordpress.org/plugins/woocommerce/';
		}
		?>
		<p>
			<a href="<?php echo esc_url( $url ); ?>" class="button button-primary"><?php esc_html_e( 'Install WooCommerce', 'woocommerce-beta-tester' ); ?></a>
			<?php if ( current_user_can( 'deactivate_plugin', 'woocommerce-beta-tester/woocommerce-beta-tester.php' ) ) : ?>
				<a href="<?php echo esc_url( wp_nonce_url( 'plugins.php?action=deactivate&plugin=woocommerce-beta-tester/woocommerce-beta-tester.php&plugin_status=inactive', 'deactivate-plugin_woocommerce-beta-tester/woocommerce-beta-tester.php' ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Turn off Beta Tester plugin', 'woocommerce-beta-tester' ); ?></a>
			<?php endif; ?>
		</p>
	<?php endif; ?>
</div>
