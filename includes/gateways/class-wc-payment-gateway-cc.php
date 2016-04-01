<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Credit Card Payment Gateway
 *
 * @since       2.6.0
 * @package		WooCommerce/Classes
 * @author 		WooThemes
 */
abstract class WC_Payment_Gateway_CC extends WC_Payment_Gateway {

	/**
	 * Builds our payment fields area - including tokenization fields and the actualy payment fields.
	 * If tokenization is displayed, just the fields will be displayed.
	 * @since 2.6.0
	 */
	public function payment_fields() {
		$display_tokenization = $this->supports( 'tokenization' ) && is_checkout();

		if ( $display_tokenization ) {
			$this->tokenization_script();
			if ( is_user_logged_in() ) {
				$this->saved_payment_methods();
			}
			$this->use_new_payment_method_checkbox();
		}

		$this->form();

		if ( $display_tokenization ) {
			$this->save_payment_method_checkbox();
		}
	}

	/**
	 * Outputs fields for entering credit card information.
	 * @since 2.6.0
	 */
	public function form() {
		$html           = '';
		$fields         = array();

		$cvc_field = '<p class="form-row form-row-last">
			<label for="' . esc_attr( $this->id ) . '-card-cvc">' . __( 'Card Code', 'woocommerce' ) . ' <span class="required">*</span></label>
			<input id="' . esc_attr( $this->id ) . '-card-cvc" class="input-text wc-credit-card-form-card-cvc" type="text" autocomplete="off" placeholder="' . esc_attr__( 'CVC', 'woocommerce' ) . '" name="' . esc_attr( $this->id ) . '-card-cvc" style="width:100px" />
		</p>';

		$default_fields = array(
			'card-number-field' => '<p class="form-row form-row-wide">
				<label for="' . esc_attr( $this->id ) . '-card-number">' . __( 'Card Number', 'woocommerce' ) . ' <span class="required">*</span></label>
				<input id="' . esc_attr( $this->id ) . '-card-number" class="input-text wc-credit-card-form-card-number" type="text" maxlength="20" autocomplete="off" placeholder="•••• •••• •••• ••••" name="' . esc_attr( $this->id ) . '-card-number" />
			</p>',
			'card-expiry-field' => '<p class="form-row form-row-first">
				<label for="' . esc_attr( $this->id ) . '-card-expiry">' . __( 'Expiry (MM/YY)', 'woocommerce' ) . ' <span class="required">*</span></label>
				<input id="' . esc_attr( $this->id ) . '-card-expiry" class="input-text wc-credit-card-form-card-expiry" type="text" autocomplete="off" placeholder="' . esc_attr__( 'MM / YY', 'woocommerce' ) . '" name="' . esc_attr( $this->id ) . '-card-expiry" />
			</p>'
		);

		if ( ! $this->supports( 'credit_card_form_cvc_on_saved_method' ) ) {
			$default_fields['card-cvc-field'] = $cvc_field;
		}

		$fields = wp_parse_args( $fields, apply_filters( 'woocommerce_credit_card_form_fields', $default_fields, $this->id ) );
		?>

		<fieldset id="wc-<?php echo esc_attr( $this->id ); ?>-cc-form" class='wc-credit-card-form'>
			<?php do_action( 'woocommerce_credit_card_form_start', $this->id ); ?>
			<?php
				foreach ( $fields as $field ) {
					echo $field;
				}
			?>
			<?php do_action( 'woocommerce_credit_card_form_end', $this->id ); ?>
			<div class="clear"></div>
		</fieldset>
		<?php
		if ( $this->supports( 'credit_card_form_cvc_on_saved_method' ) ) {
			echo '<fieldset>' . $cvc_field . '</fieldset>';
		}
	}

}
