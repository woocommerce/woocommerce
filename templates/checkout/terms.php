<?php
/**
 * Checkout terms and conditions area.
 *
 * @package WooCommerce/Templates
 * @version 3.4.0
 */

defined( 'ABSPATH' ) || exit;

$terms_checkbox_enabled = wc_string_to_bool( get_option( 'woocommerce_checkout_terms_and_conditions_checkbox' ) );

if ( apply_filters( 'woocommerce_checkout_show_terms', true ) ) {
	do_action( 'woocommerce_checkout_before_terms_and_conditions' );

	?>
	<div class="woocommerce-terms-and-conditions-wrapper">
		<div class="woocommerce-terms-and-conditions-text">
			<?php woocommerce_output_terms_and_conditions_text(); ?>
		</div>

		<?php if ( 0 < wc_get_page_id( 'terms' ) ) : ?>
		<div class="woocommerce-terms-and-conditions" style="display: none; max-height: 200px; overflow: auto;">
			<?php woocommerce_output_terms_and_conditions_page_content(); ?>
		</div>
		<?php endif; ?>

		<?php if ( $terms_checkbox_enabled ) : ?>
		<p class="form-row validate-required">
			<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
			<input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" name="terms" <?php checked( apply_filters( 'woocommerce_terms_is_checked_default', isset( $_POST['terms'] ) ), true ); // WPCS: input var ok, csrf ok. ?> id="terms" />
				<span class="woocommerce-terms-and-conditions-checkbox-text"><?php woocommerce_output_terms_and_conditions_checkbox_text(); ?></span>&nbsp;<span class="required">*</span>
			</label>
			<input type="hidden" name="terms-field" value="1" />
		</p>
		<?php endif; ?>
	</div>
	<?php

	do_action( 'woocommerce_checkout_after_terms_and_conditions' );
}
