<?php
/**
 * Admin View: Page - Addons
 *
 * @var string $view
 * @var object $addons
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="wrap woocommerce wc_addons_wrap">
	<nav class="nav-tab-wrapper woo-nav-tab-wrapper">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-addons' ) ); ?>" class="nav-tab nav-tab-active"><?php _e( 'Browse Extensions', 'woocommerce' ); ?></a>

		<?php
			$count_html = WC_Helper_Updater::get_updates_count_html();
			$menu_title = sprintf( __( 'WooCommerce.com Subscriptions %s', 'woocommerce' ), $count_html );
		?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-addons&section=helper' ) ); ?>" class="nav-tab"><?php echo $menu_title; ?></a>
	</nav>

	<h1 class="screen-reader-text"><?php _e( 'WooCommerce Extensions', 'woocommerce' ); ?></h1>

	<?php if ( $sections ) : ?>
		<ul class="subsubsub">
			<?php foreach ( $sections as $section ) : ?>
				<li>
					<a
						class="<?php echo $current_section === $section->slug ? 'current' : ''; ?>"
						href="<?php echo admin_url( 'admin.php?page=wc-addons&section=' . esc_attr( $section->slug ) ); ?>">
						<?php echo esc_html( $section->label ); ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>

		<?php if ( isset( $_GET['search'] ) ) : ?>
			<h1 class="search-form-title" >
				<?php printf( __( 'Showing search results for: %s', 'woocommerce' ), '<strong>' . esc_html( $_GET['search'] ) . '</strong>' ); ?>
			</h1>
		<?php endif; ?>

		<form class="search-form" method="GET">
			<button type="submit">
				<span class="dashicons dashicons-search"></span>
			</button>
			<input
				type="text"
				name="search"
				value="<?php echo esc_attr( isset( $_GET['search'] ) ? $_GET['search'] : '' ); ?>"
				placeholder="<?php _e( 'Enter a search term and press enter', 'woocommerce' ); ?>">
			<input type="hidden" name="page" value="wc-addons">
			<?php $page_section = ( isset( $_GET['section'] ) && '_featured' !== $_GET['section'] ) ? $_GET['section'] : '_all'; ?>
			<input type="hidden" name="section" value="<?php echo esc_attr( $page_section ); ?>">
		</form>
		<?php if ( '_featured' === $current_section ) : ?>
			<div class="addons-featured">
				<?php
					$featured = WC_Admin_Addons::get_featured();
				?>
			</div>
		<?php endif; ?>
		<?php if ( '_featured' !== $current_section && $addons ) : ?>
			<?php if ( 'shipping_methods' === $current_section ) : ?>
				<div class="addons-shipping-methods">
					<?php WC_Admin_Addons::output_wcs_banner_block(); ?>
				</div>
			<?php endif; ?>
			<ul class="products">
			<?php foreach ( $addons as $addon ) : ?>
				<?php if ( 'shipping_methods' === $current_section ) {
					// Do not show USPS or Canada Post extensions for US and CA stores, respectively.
					$country = WC()->countries->get_base_country();
					if ( 'US' === $country
						&& false !== strpos(
								$addon->link, 'woocommerce.com/products/usps-shipping-method'
							)
					) {
						continue;
					}
					if ( 'CA' === $country
						&& false !== strpos(
								$addon->link, 'woocommerce.com/products/canada-post-shipping-method'
							)
					) {
						continue;
					}
				}
				?>
				<li class="product">
					<a href="<?php echo esc_attr( $addon->link ); ?>">
						<?php if ( ! empty( $addon->image ) ) : ?>
							<span class="product-img-wrap"><img src="<?php echo esc_url( $addon->image ); ?>"/></span>
						<?php else : ?>
							<h2><?php echo esc_html( $addon->title ); ?></h2>
						<?php endif; ?>
						<span class="price"><?php echo wp_kses_post( $addon->price ); ?></span>
						<p><?php echo wp_kses_post( $addon->excerpt ); ?></p>
					</a>
				</li>
			<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	<?php else : ?>
		<p><?php printf( __( 'Our catalog of WooCommerce Extensions can be found on WooCommerce.com here: <a href="%s">WooCommerce Extensions Catalog</a>', 'woocommerce' ), 'https://woocommerce.com/product-category/woocommerce-extensions/' ); ?></p>
	<?php endif; ?>

	<?php if ( 'Storefront' !== $theme['Name'] && '_featured' !== $current_section ) : ?>
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
