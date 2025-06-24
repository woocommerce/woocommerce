<?php
/**
 * Back in Stock Form
 *
 * Shows the additional form fields on the product page.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/back-in-stock-form.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 0.0.0
 */

use Automattic\WooCommerce\Internal\StockNotifications\Config;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( isset( $available_variations ) && ! empty( $available_variations ) ) {
	foreach ( $available_variations as $variation ) {
		echo '<pre>';
		print_r( $variation );
		echo '</pre>';
	}
}

?><form method="post" id="wc_bis_product_form" data-bis-product-id="<?php echo $product->get_parent_id() ? absint( $product->get_parent_id() ) : absint( $product->get_id() ); ?>" role="form" aria-labelledby="wc_bis_form_title">

	<h3 id="wc_bis_form_title" class="wc_bis_form_title">
		<?php echo wp_kses_post( __( 'Want to be notified when this product is back in stock?', 'woocommerce' ) ); ?>
	</h3>

	<?php
	/**
	 * Fires before the form fields in the back in stock notification form.
	 *
	 * @since 0.0.0
	 *
	 * @param WC_Product $product The product object.
	 */
	do_action( 'woocommerce_customer_stock_notifications_before_form_fields', $product );
	?>

	<div class="wc_bis_inline_form">
		<?php if ( ! is_user_logged_in() && ! Config::requires_account() ) : ?>
			<label for="wc_bis_email" class="screen-reader-text"><?php echo esc_html_x( 'Email address', 'back in stock form', 'woocommerce' ); ?></label>
			<input type="email"
				id="wc_bis_email"
				name="wc_bis_email"
				class="input-text"
				placeholder="<?php echo esc_attr_x( 'Enter your e-mail', 'back in stock form', 'woocommerce' ); ?>"
				required
				aria-required="true"
			/>
		<?php endif; ?>

		<button class="<?php echo esc_attr( $button_class ); ?>"
			type="submit"
			id="wc_bis_send_form"
		>
			<?php echo esc_html( $button_text ); ?>
		</button>
	</div>

	<?php if ( ! is_user_logged_in() && Config::creates_account_on_signup() && ! Config::requires_account() ) : ?>
		<label for="wc_bis_opt_in" class="wc_bis_opt_in">
			<input type="checkbox"
				name="wc_bis_opt_in"
				id="wc_bis_opt_in"
			/>
			<?php echo wp_kses_post( wc_replace_policy_page_link_placeholders( wc_get_privacy_policy_text( 'registration' ) ) ); ?>
		</label>
	<?php endif; ?>

	<?php
	/**
	 * Fires after the form fields in the back in stock notification form.
	 *
	 * @since 0.0.0
	 *
	 * @param WC_Product $product The product object.
	 */
	do_action( 'woocommerce_customer_stock_notifications_after_form_fields', $product );
	?>

	<input type="hidden" name="action" value="wc_bis_register" />
	<input type="hidden" name="wc_bis_product_id" value="<?php echo $product->get_parent_id() ? absint( $product->get_parent_id() ) : absint( $product->get_id() ); ?>" />
</form>
