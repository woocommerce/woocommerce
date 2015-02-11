<?php
/**
 * Admin View: Page - Addons
 *
 * @var string $view
 * @var object $addons
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$view 	= isset( $_GET['view'] ) ? sanitize_text_field( $_GET['view'] ) : '';
$theme 	= wp_get_theme();

?>

<div class="wrap woocommerce wc_addons_wrap">
	<div class="icon32 icon32-posts-product" id="icon-woocommerce"><br /></div>
	<h2>
		<?php _e( 'WooCommerce Add-ons/Extensions', 'woocommerce' ); ?>
		<a href="http://www.woothemes.com/product-category/woocommerce-extensions/" class="add-new-h2"><?php _e( 'Browse all extensions', 'woocommerce' ); ?></a>
	</h2>
	<?php if ( $addons ) : ?>
		<ul class="subsubsub">
			<?php
				$links = array(
					''                         => __( 'Popular', 'woocommerce' ),
					'payment-gateways'         => __( 'Gateways', 'woocommerce' ),
					'shipping-methods'         => __( 'Shipping', 'woocommerce' ),
					'import-export-extensions' => __( 'Import/export', 'woocommerce' ),
					'product-extensions'       => __( 'Products', 'woocommerce' ),
					'marketing-extensions'     => __( 'Marketing', 'woocommerce' ),
					'accounting-extensions'	   => __( 'Accounting', 'woocommerce' ),
					'free-extensions'          => __( 'Free', 'woocommerce' ),
					'third-party-extensions'   => __( 'Third-party', 'woocommerce' ),
				);

				$i = 0;

				foreach ( $links as $link => $name ) {
					$i ++;
					?><li><a class="<?php if ( $view == $link ) echo 'current'; ?>" href="<?php echo admin_url( 'admin.php?page=wc-addons&view=' . esc_attr( $link ) ); ?>"><?php echo $name; ?></a><?php if ( $i != sizeof( $links ) ) echo ' |'; ?></li><?php
				}
			?>
		</ul>
		<br class="clear" />
		<ul class="products">
		<?php
			switch ( $view ) {
				case '':
					$addons = $addons->popular;
				break;
				case 'payment-gateways':
					$addons = $addons->{'payment-gateways'};
				break;
				case 'shipping-methods':
					$addons = $addons->{'shipping-methods'};
				break;
				case 'import-export-extensions':
					$addons = $addons->{'import-export'};
				break;
				case 'product-extensions':
					$addons = $addons->product;
				break;
				case 'marketing-extensions':
					$addons = $addons->marketing;
				break;
				case 'accounting-extensions':
					$addons = $addons->accounting;
				break;
				case 'free-extensions':
					$addons = $addons->free;
				break;
				case 'third-party-extensions':
					$addons = $addons->{'third-party'};
				break;
			}

			foreach ( $addons as $addon ) {
				echo '<li class="product">';
				echo '<a href="' . $addon->link . '">';
				if ( ! empty( $addon->image ) ) {
					echo '<img src="' . $addon->image . '"/>';
				} else {
					echo '<h3>' . $addon->title . '</h3>';
				}
				echo '<span class="price">' . $addon->price . '</span>';
				echo '<p>' . $addon->excerpt . '</p>';
				echo '</a>';
				echo '</li>';
			}
		?>
		</ul>
	<?php else : ?>
		<p><?php printf( __( 'Our catalog of WooCommerce Extensions can be found on WooThemes.com here: <a href="%s">WooCommerce Extensions Catalog</a>', 'woocommerce' ), 'http://www.woothemes.com/product-category/woocommerce-extensions/' ); ?></p>
	<?php endif; ?>

	<?php if ( 'Storefront' != $theme['Name'] ) : ?>

	<div class="storefront">
		<img src="<?php echo WC()->plugin_url(); ?>/assets/images/storefront.jpg" alt="Storefront" />

		<h3><?php _e( 'Looking for a WooCommerce theme?', 'woocommerce' ); ?></h3>

		<p>
			<?php printf( __( 'We recommend Storefront, the %sofficial%s WooCommerce theme.', 'woocommerce' ), '<em>', '</em>' ); ?>
		</p>

		<p>
			<?php printf( __( 'Storefront is an intuitive &amp; flexible, %sfree%s WordPress theme offering deep integration with WooCommerce and many of the most popular customer-facing extensions.', 'woocommerce' ), '<strong>', '</strong>' ); ?>
		</p>

		<p>
			<a href="<?php echo esc_url( 'http://www.woothemes.com/storefront/' ); ?>" target="_blank" class="button"><?php _e( 'Read all about it', 'woocommerce' ) ?></a>
			<a href="<?php echo esc_url( wp_nonce_url( self_admin_url( 'update.php?action=install-theme&theme=storefront' ), 'install-theme_storefront' ) ); ?>" class="button button-primary"><?php _e( 'Download &amp; install', 'woocommerce' ); ?></a>
		</p>
	</div>

	<?php endif; ?>
</div>
