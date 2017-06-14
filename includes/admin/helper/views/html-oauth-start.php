<?php defined( 'ABSPATH' ) or exit(); ?>

<div class="wrap woocommerce wc_addons_wrap wc-helper">
	<?php include( WC_Helper::get_view_filename( 'html-section-nav.php' ) ); ?>
	<h1 class="screen-reader-text"><?php _e( 'WooCommerce Extensions', 'woocommerce' ); ?></h1>
	<?php include( WC_Helper::get_view_filename( 'html-section-notices.php' ) ); ?>

		<div class="start-container">
			<div class="text">
				<img src="https://woocommerce.com/wp-content/themes/woomattic/images/logo-woocommerce@2x.png" alt="WooCommerce" style="width:180px;">

				<?php if ( ! empty( $_GET['wc-helper-status'] ) && 'helper-disconnected' === $_GET['wc-helper-status'] ) : ?>
					<p><?php _e( '<strong>Sorry to see you go</strong>. Feel free to reconnect again using the button below.', 'woocommerce' ); ?></p>
				<?php endif; ?>

				<h2><?php _e( 'Connect a WooCommerce.com account to manage subscriptions and updates from the convenience of your WooCommerce dashboard', 'woocommerce' ); ?></h2>
				<p><?php _e( 'Once connected, WooCommerce.com purchases will be listed automatically within your WooCommerce dashboard. Manage subscriptions and get important notifications, updates, and support more easily – and disconnect at any time.', 'woocommerce' ); ?></p>
				<p><a class="button-primary" href="<?php echo esc_url( $connect_url ); ?>"><?php _e( 'Connect your store', 'woocommerce' ); ?></a></p>
			</div>
		</div>
</div>
