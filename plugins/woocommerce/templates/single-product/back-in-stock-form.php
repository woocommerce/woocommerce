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

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="wc_bis_form">

	<h3>
		<?php echo wp_kses_post( __( 'Want to be notified when this product is back in stock?', 'woocommerce' ) ); ?>
	</h3>

	<form method="post" novalidate>
		<div class="wc_bis_form__form-row">
			<?php if ( $show_email_field ) : ?>

				<label for="wc_bis_email" class="screen-reader-text"><?php echo esc_html_x( 'Email address to be notified when this product is back in stock', 'back in stock form', 'woocommerce' ); ?></label>
				<input
					type="email"
					name="wc_bis_email"
					class="wc_bis_form__input"
					placeholder="<?php echo esc_attr_x( 'Enter your e-mail', 'back in stock form', 'woocommerce' ); ?>"
					id="wc_bis_email"
					required
					aria-required="true"
				/>

			<?php endif; ?>

			<button
				type="submit"
				name="wc_bis_register"
				class="<?php echo esc_attr( $button_class ); ?>"
			>
				<?php echo esc_html( $button_text ); ?>
			</button>
		</div>

		<?php if ( $show_checkbox ) : ?>

			<label for="wc_bis_opt_in" class="wc_bis_form__checkbox">
				<input
					type="checkbox"
					name="wc_bis_opt_in"
					id="wc_bis_opt_in"
				/>
				<?php echo wp_kses_post( wc_replace_policy_page_link_placeholders( wc_get_privacy_policy_text( 'registration' ) ) ); ?>
			</label>

		<?php endif; ?>

		<input type="hidden" name="wc_bis_product_id" value="<?php echo $product->get_parent_id() ? absint( $product->get_parent_id() ) : absint( $product->get_id() ); ?>" />
	</form>

</div>
