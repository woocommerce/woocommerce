<?php
/**
 * Admin View: Page - Extensions
 *
 * @var string $view
 * @var object $extensions
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="wrap woocommerce wc_extensions_wrap">
	<nav class="nav-tab-wrapper woo-nav-tab-wrapper">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-extensions' ) ); ?>" class="nav-tab nav-tab-active"><?php _e( 'Browse Extensions', 'woocommerce' ); ?></a>

		<?php
			$count_html = WC_Helper_Updater::get_updates_count_html();
			$menu_title = sprintf( __( 'WooCommerce.com Subscriptions %s', 'woocommerce' ), $count_html );
		?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-extensions&section=helper' ) ); ?>" class="nav-tab"><?php echo $menu_title; ?></a>
	</nav>

	<h1 class="screen-reader-text"><?php _e( 'WooCommerce Extensions', 'woocommerce' ); ?></h1>

	<?php if ( $sections ) : ?>
		<ul class="subsubsub">
			<?php foreach ( $sections as $section_id => $section ) : ?>
				<li><a class="<?php echo $current_section === $section_id ? 'current' : ''; ?>" href="<?php echo admin_url( 'admin.php?page=wc-extensions&section=' . esc_attr( $section_id ) ); ?>"><?php echo esc_html( $section->title ); ?></a><?php echo ( end( $section_keys ) !== $section_id ) ? ' |' : ''; ?></li>
			<?php endforeach; ?>
		</ul>
		<br class="clear" />
		<?php if ( 'featured' === $current_section ) : ?>
			<div class="extensions-featured">
				<?php
					$featured = WC_Admin_Extensions::get_featured();
				?>
			</div>
		<?php endif; ?>
		<?php if ( 'featured' !== $current_section && $extensions = WC_Admin_Extensions::get_section_data( $current_section ) ) : ?>
			<?php if ( 'shipping_methods' === $current_section ) : ?>
				<div class="extensions-shipping-methods">
					<?php WC_Admin_Extensions::output_wcs_banner_block(); ?>
				</div>
			<?php endif; ?>
			<ul class="products">
			<?php foreach ( $extensions as $extension ) : ?>
				<?php if ( 'shipping_methods' === $current_section ) {
					// Do not show USPS or Canada Post extensions for US and CA stores, respectively.
					$country = WC()->countries->get_base_country();
					if ( 'US' === $country
						&& false !== strpos(
								$extension->link, 'woocommerce.com/products/usps-shipping-method'
							)
					) {
						continue;
					}
					if ( 'CA' === $country
						&& false !== strpos(
								$extension->link, 'woocommerce.com/products/canada-post-shipping-method'
							)
					) {
						continue;
					}
				}
				?>
				<li class="product">
					<a href="<?php echo esc_attr( $extension->link ); ?>">
						<?php if ( ! empty( $extension->image ) ) : ?>
							<img src="<?php echo esc_attr( $extension->image ); ?>"/>
						<?php else : ?>
							<h2><?php echo esc_html( $extension->title ); ?></h2>
						<?php endif; ?>
						<span class="price"><?php echo wp_kses_post( $extension->price ); ?></span>
						<p><?php echo wp_kses_post( $extension->excerpt ); ?></p>
					</a>
				</li>
			<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	<?php else : ?>
		<p><?php printf( __( 'Our catalog of WooCommerce Extensions can be found on WooCommerce.com here: <a href="%s">WooCommerce Extensions Catalog</a>', 'woocommerce' ), 'https://woocommerce.com/product-category/woocommerce-extensions/' ); ?></p>
	<?php endif; ?>

	<?php if ( 'Storefront' !== $theme['Name'] && 'featured' !== $current_section ) : ?>
		<div class="storefront">
			<a href="<?php echo esc_url( 'https://woocommerce.com/storefront/' ); ?>" target="_blank"><img src="<?php echo WC()->plugin_url(); ?>/assets/images/storefront.png" alt="Storefront" /></a>
			<h2><?php _e( 'Looking for a WooCommerce theme?', 'woocommerce' ); ?></h2>
			<p><?php _e( 'We recommend Storefront, the <em>official</em> WooCommerce theme.', 'woocommerce' ); ?></p>
			<p><?php _e( 'Storefront is an intuitive, flexible and <strong>free</strong> WordPress theme offering deep integration with WooCommerce and many of the most popular customer-facing extensions.', 'woocommerce' ); ?></p>
			<p>
				<a href="https://woocommerce.com/storefront/" target="_blank" class="button"><?php _e( 'Read all about it', 'woocommerce' ) ?></a>
				<a href="<?php echo esc_url( wp_nonce_url( self_admin_url( 'update.php?action=install-theme&theme=storefront' ), 'install-theme_storefront' ) ); ?>" class="button button-primary"><?php _e( 'Download &amp; install', 'woocommerce' ); ?></a>
			</p>
		</div>
	<?php endif; ?>
</div>
