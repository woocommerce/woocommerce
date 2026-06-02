<?php
/**
 * Cart item data (when outputting non-flat)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/cart-item-data.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     11.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<?php // Non-semantic markup is used intentionally: this data is not a list of terms and definitions, so the previously used <dl>/<dt>/<dd> tags were announced incorrectly by screen readers. See https://github.com/woocommerce/woocommerce/issues/61076. ?>
<div class="variation">
	<?php foreach ( $item_data as $data ) : ?>
		<div class="<?php echo sanitize_html_class( 'variation-' . $data['key'] ); ?> variation-label"><?php echo wp_kses_post( $data['key'] ); ?>:</div>
		<div class="<?php echo sanitize_html_class( 'variation-' . $data['key'] ); ?> variation-value"><?php echo wp_kses_post( wpautop( $data['display'] ) ); ?></div>
	<?php endforeach; ?>
</div>
