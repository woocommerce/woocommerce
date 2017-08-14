<?php defined( 'ABSPATH' ) or exit(); ?>

<nav class="nav-tab-wrapper woo-nav-tab-wrapper">
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-addons' ) ); ?>" class="nav-tab"><?php _e( 'Browse Extensions', 'woocommerce' ); ?></a>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-addons&section=helper' ) ); ?>" class="nav-tab nav-tab-active"><?php _e( 'WooCommerce.com Subscriptions', 'woocommerce' ); ?></a>
</nav>
