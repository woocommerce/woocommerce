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
	<div class="icon32 icon32-posts-product" id="icon-woocommerce"><br /></div>
	<h1>
		<?php _e( 'WooCommerce Add-ons/Extensions', 'woocommerce' ); ?>
		<a href="https://woocommerce.com/product-category/woocommerce-extensions/" class="add-new-h2"><?php _e( 'Browse all extensions', 'woocommerce' ); ?></a>
		<?php WC_Admin_Addons::output_storefront_button(); ?>
	</h1>
	<?php if ( $sections ) : ?>
		<ul class="subsubsub">
			<?php foreach ( $sections as $section_id => $section ) : ?>
				<li><a class="<?php echo $current_section === $section_id ? 'current' : ''; ?>" href="<?php echo admin_url( 'admin.php?page=wc-addons&section=' . esc_attr( $section_id ) ); ?>"><?php echo esc_html( $section->title ); ?></a><?php if ( $section_id !== end( $section_keys ) ) echo ' |'; ?></li>
			<?php endforeach; ?>
		</ul>
		<br class="clear" />
		<?php if ( $addons = WC_Admin_Addons::get_section_data( $current_section ) ) : ?>
			<ul class="products">
			<?php foreach ( $addons as $addon ) : ?>
				<li class="product">
					<a href="<?php echo esc_attr( $addon->link ); ?>">
						<?php if ( ! empty( $addon->image ) ) : ?>
							<img src="<?php echo esc_attr( $addon->image ); ?>"/>
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

	<?php if ( 'Storefront' !== $theme['Name'] ) : ?>
		<div class="storefront">
			<a href="<?php echo esc_url( 'https://woocommerce.com/storefront/' ); ?>" target="_blank"><img src="<?php echo WC()->plugin_url(); ?>/assets/images/storefront.png" alt="Storefront" /></a>
			<h2><?php _e( 'Looking for a WooCommerce theme?', 'woocommerce' ); ?></h2>
			<p><?php printf( __( 'We recommend Storefront, the %sofficial%s WooCommerce theme.', 'woocommerce' ), '<em>', '</em>' ); ?></p>
			<p><?php printf( __( 'Storefront is an intuitive, flexible and %sfree%s WordPress theme offering deep integration with WooCommerce and many of the most popular customer-facing extensions.', 'woocommerce' ), '<strong>', '</strong>' ); ?></p>
			<p>
				<a href="<?php echo esc_url( 'https://woocommerce.com/storefront/' ); ?>" target="_blank" class="button"><?php _e( 'Read all about it', 'woocommerce' ) ?></a>
				<a href="<?php echo esc_url( wp_nonce_url( self_admin_url( 'update.php?action=install-theme&theme=storefront' ), 'install-theme_storefront' ) ); ?>" class="button button-primary"><?php _e( 'Download &amp; install', 'woocommerce' ); ?></a>
			</p>
		</div>
	<?php endif; ?>
</div>
