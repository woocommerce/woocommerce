<?php defined( 'ABSPATH' ) or exit(); ?>

<div class="wrap woocommerce wc_addons_wrap wc-helper">
	<?php include( WC_Helper::get_view_filename( 'html-section-nav.php' ) ); ?>
	<h1 class="screen-reader-text"><?php _e( 'WooCommerce Extensions', 'woocommerce' ); ?></h1>
	<?php include( WC_Helper::get_view_filename( 'html-section-notices.php' ) ); ?>

		<div class="start-container">
			<div class="text">
				<img src="https://woocommerce.com/wp-content/themes/woo/images/logo-woocommerce@2x.png" alt="WooCommerce" style="width:180px;">

				<?php if ( ! empty( $_GET['wc-helper-status'] ) && 'helper-disconnected' === $_GET['wc-helper-status'] ) : ?>
					<p><?php _e( '<strong>Sorry to see you go</strong>. Feel free to reconnect again using the button below.', 'woocommerce' ); ?></p>
				<?php endif; ?>

				<h2><?php _e( 'Manage your subscriptions, get important product notifications, and updates, all from the convenience of your WooCommerce dashboard', 'woocommerce' ); ?></h2>
				<p><?php _e( 'Once connected, your WooCommerce.com purchases will be listed here.', 'woocommerce' ); ?></p>
				<p><a class="button button-primary" href="<?php echo esc_url( $connect_url ); ?>"><?php _e( 'Connect', 'woocommerce' ); ?></a></p>
			</div>
		</div>
</div>
