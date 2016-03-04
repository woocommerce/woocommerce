<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * eCheck Payment Gateway
 *
 * @since       2.6.0
 * @package		WooCommerce/Classes
 * @author 		WooThemes
 */
abstract class WC_Payment_Gateway_eCheck extends WC_Payment_Gateway {

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
	 * Outputs fields for entering eCheck information.
	 * @since 2.6.0
	 */
	public function form() {
		$html           = '';
		$fields         = array();

		$default_fields = array(
			'routing-number' => '<p class="form-row form-row-first">
				<label for="' . esc_attr( $this->id ) . '-routing-number">' . __( 'Routing Number', 'woocommerce' ) . ' <span class="required">*</span></label>
				<input id="' . esc_attr( $this->id ) . '-routing-number" class="input-text wc-echeck-form-routing-number" type="text" maxlength="9" autocomplete="off" placeholder="•••••••••" name="' . esc_attr( $this->id ) . '-routing-number" />
			</p>',
			'account-number' => '<p class="form-row form-row-wide">
				<label for="' . esc_attr( $this->id ) . '-account-number">' . __( 'Account Number', 'woocommerce' ) . ' <span class="required">*</span></label>
				<input id="' . esc_attr( $this->id ) . '-account-number" class="input-text wc-echeck-form-account-number" type="text" autocomplete="off" name="' . esc_attr( $this->id ) . '-account-number" maxlength="17" />
			</p>',
		);

		$fields = wp_parse_args( $fields, apply_filters( 'woocommerce_echeck_form_fields', $default_fields, $this->id ) );
		?>

		<fieldset id="<?php echo esc_attr( $this->id ); ?>-cc-form" class='wc-echeck-form'>
			<?php do_action( 'woocommerce_echeck_form_start', $this->id ); ?>
			<?php
				foreach ( $fields as $field ) {
					echo $field;
				}
			?>
			<?php do_action( 'woocommerce_echeck_form_end', $this->id ); ?>
			<div class="clear"></div>
		</fieldset><?php
	}

}
