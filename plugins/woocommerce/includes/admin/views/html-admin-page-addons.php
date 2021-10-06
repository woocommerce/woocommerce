<?php
/**
 * Admin View: Page - Addons
 *
 * @package WooCommerce\Admin
 * @var string $view
 * @var object $addons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_section_name = __( 'Browse Categories', 'woocommerce' );

?>
<div class="woocommerce wc-addons-wrap">
	<h1 class="screen-reader-text"><?php esc_html_e( 'Marketplace', 'woocommerce' ); ?></h1>

	<?php if ( $sections ) : ?>
	<div class="marketplace-header">
		<h1 class="marketplace-header__title"><?php esc_html_e( 'WooCommerce Marketplace', 'woocommerce' ); ?></h1>
		<p class="marketplace-header__description"><?php esc_html_e( 'Grow your business with hundreds of free and paid WooCommerce extensions.', 'woocommerce' ); ?></p>
		<form class="marketplace-header__search-form" method="GET">
			<input
				type="text"
				name="search"
				value="<?php echo esc_attr( ! empty( $search ) ? sanitize_text_field( wp_unslash( $search ) ) : '' ); ?>"
				placeholder="<?php esc_attr_e( 'Search for extensions', 'woocommerce' ); ?>"
			/>
			<button type="submit">
				<span class="dashicons dashicons-search"></span>
			</button>
			<input type="hidden" name="page" value="wc-addons">
			<input type="hidden" name="section" value="_all">
		</form>
	</div>

	<div class="top-bar">
		<div id="marketplace-current-section-dropdown" class="current-section-dropdown">
			<ul>
				<?php foreach ( $sections as $section ) : ?>
					<?php
					if ( $current_section === $section->slug && '_featured' !== $section->slug ) {
						$current_section_name = $section->label;
					}
					?>
					<li>
						<a
							class="<?php echo $current_section === $section->slug ? 'current' : ''; ?>"
							href="<?php echo esc_url( admin_url( 'admin.php?page=wc-addons&section=' . esc_attr( $section->slug ) ) ); ?>">
							<?php echo esc_html( $section->label ); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
			<div id="marketplace-current-section-name" class="current-section-name"><?php echo esc_html( $current_section_name ); ?></div>
		</div>
		</div>

	<div class="wp-header-end"></div>

	<div class="wrap">
		<div class="marketplace-content-wrapper">
			<?php if ( ! empty( $search ) ) : ?>
				<h1 class="search-form-title">
					<?php // translators: search keyword. ?>
					<?php printf( esc_html__( 'Search results for "%s"', 'woocommerce' ), esc_html( sanitize_text_field( wp_unslash( $search ) ) ) ); ?>
				</h1>
			<?php endif; ?>

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
				<?php if ( 'payment-gateways' === $current_section ) : ?>
					<div class="addons-shipping-methods">
						<?php WC_Admin_Addons::output_wcpay_banner_block(); ?>
					</div>
				<?php endif; ?>
				<ul class="products">
					<?php foreach ( $addons as $addon ) : ?>
						<?php
						if ( 'shipping_methods' === $current_section ) {
							// Do not show USPS or Canada Post extensions for US and CA stores, respectively.
							$country = WC()->countries->get_base_country();
							if ( 'US' === $country
								&& false !== strpos(
									$addon->link,
									'woocommerce.com/products/usps-shipping-method'
								)
							) {
								continue;
							}
							if ( 'CA' === $country
								&& false !== strpos(
									$addon->link,
									'woocommerce.com/products/canada-post-shipping-method'
								)
							) {
								continue;
							}
						}
						?>
						<li class="product">
							<div class="product-details">
								<?php if ( ! empty( $addon->image ) ) : ?>
									<span class="product-img-wrap"><img src="<?php echo esc_url( $addon->image ); ?>" /></span>
								<?php endif; ?>
								<a href="<?php echo esc_url( WC_Admin_Addons::add_in_app_purchase_url_params( $addon->link ) ); ?>">
									<h2><?php echo esc_html( $addon->title ); ?></h2>
								</a>
								<p><?php echo wp_kses_post( $addon->excerpt ); ?></p>
							</div>
							<div class="product-footer">
								<?php if ( '&#36;0.00' === $addon->price ) : ?>
									<span class="price"><?php esc_html_e( 'Free', 'woocommerce' ); ?></span>
								<?php else : ?>
									<span class="price"><?php echo wp_kses_post( $addon->price ); ?></span>
									<span class="price_suffix"><?php esc_html_e( 'per year', 'woocommerce' ); ?></span>
								<?php endif; ?>
								<a class="button" href="<?php echo esc_url( WC_Admin_Addons::add_in_app_purchase_url_params( $addon->link ) ); ?>">
									<?php esc_html_e( 'View details', 'woocommerce' ); ?>
								</a>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php else : ?>
			<?php /* translators: a url */ ?>
			<p><?php printf( wp_kses_post( __( 'Our catalog of WooCommerce Extensions can be found on WooCommerce.com here: <a href="%s">WooCommerce Extensions Catalog</a>', 'woocommerce' ) ), 'https://woocommerce.com/product-category/woocommerce-extensions/' ); ?></p>
		<?php endif; ?>

		<?php if ( 'Storefront' !== $theme['Name'] && '_featured' !== $current_section ) : ?>
			<div class="storefront">
				<a href="<?php echo esc_url( 'https://woocommerce.com/storefront/' ); ?>" target="_blank"><img src="<?php echo esc_url( WC()->plugin_url() ); ?>/assets/images/storefront.png" alt="<?php esc_attr_e( 'Storefront', 'woocommerce' ); ?>" /></a>
				<h2><?php esc_html_e( 'Looking for a WooCommerce theme?', 'woocommerce' ); ?></h2>
				<p><?php echo wp_kses_post( __( 'We recommend Storefront, the <em>official</em> WooCommerce theme.', 'woocommerce' ) ); ?></p>
				<p><?php echo wp_kses_post( __( 'Storefront is an intuitive, flexible and <strong>free</strong> WordPress theme offering deep integration with WooCommerce and many of the most popular customer-facing extensions.', 'woocommerce' ) ); ?></p>
				<p>
					<a href="https://woocommerce.com/storefront/" target="_blank" class="button"><?php esc_html_e( 'Read all about it', 'woocommerce' ); ?></a>
					<a href="<?php echo esc_url( wp_nonce_url( self_admin_url( 'update.php?action=install-theme&theme=storefront' ), 'install-theme_storefront' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Download &amp; install', 'woocommerce' ); ?></a>
				</p>
			</div>
		<?php endif; ?>
	</div>
</div>
