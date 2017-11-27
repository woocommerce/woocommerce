<?php defined( 'ABSPATH' ) or exit(); ?>

<nav class="nav-tab-wrapper woo-nav-tab-wrapper">
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-addons' ) ); ?>" class="nav-tab"><?php _e( 'Browse Extensions', 'woocommerce' ); ?></a>

	<?php
		$count_html = WC_Helper_Updater::get_updates_count_html();
		$menu_title = sprintf( __( 'WooCommerce.com Subscriptions %s', 'woocommerce' ), $count_html );
	?>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-addons&section=helper' ) ); ?>" class="nav-tab nav-tab-active"><?php echo $menu_title; ?></a>
</nav>
