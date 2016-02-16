<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Default payment Form for gateways.
 *
 * @class 		WC_Payment_Gateway_Form
 * @since       2.6
 * @package		WooCommerce/Classes
 * @category	Class
 * @author 		WooThemes
 */
class WC_Payment_Gateway_Form {

	/** @protected WC_Payment_Gateway Gateway Class */
	protected $gateway;

	/**
	 * Setup and hook into WordPress & WooCommerce.
	 *
	 * @since 2.6
	 * @param WC_Payment_Gateway $gateway
	 */
	public function __construct( $gateway ) {
		$this->gateway = $gateway;
		$gateway_id    = $this->gateway->id;

		if ( is_user_logged_in() && $this->gateway->supports( 'tokenization' ) ) {
			$this->tokens = $this->filter_tokens_by_gateway(
				$gateway_id,
				WC_Payment_Tokens::get_customer_tokens( get_current_user_id() )
			);
			$this->saved_payment_methods();
		}

		if ( $this->gateway->supports( 'tokenization' ) ) {
			$this->use_new_payment_method_checkbox();
		}

		$this->load_scripts();
		$this->payment_fields();
	}

	/**
	 * Grab and display our saved payment methods.
	 *
	 * @since 2.6
	 */
	public function saved_payment_methods() {
		$html = '<p>';

		foreach ( $this->tokens as $token ) {
			$html .= $this->saved_payment_method( $token );
		}

		$html .= '</p><span id="wc-' . $this->gateway->id . '-method-count" data-count="' . esc_attr( count( $this->tokens ) ) . '"></span>';
		$html .= '<div class="clear"></div>';

		echo apply_filters( 'wc_payment_gateway_form_saved_payment_methods_html', $html, $this );
	}

	/**
	 * Outputs a saved payment method from a token.
	 *
	 * @since 2.6
	 * @param  WC_Payment_Token $token Payment Token
	 * @return string                  Generated payment method HTML
	 */
	public function saved_payment_method( $token ) {
		$html = sprintf(
			'<input type="radio" id="wc-%1$s-payment-token-%2$s" name="wc-%1$s-payment-token" style="width:auto;" class="wc-gateway-payment-token wc-%1$s-payment-token" value="%2$s" %3$s/>',
			esc_attr( $this->gateway->id ),
			esc_attr( $token->get_id() ),
			checked( $token->is_default(), true, false )
		);

		$html .= sprintf( '<label class="wc-gateway-payment-form-saved-payment-method wc-gateway-payment-token-label" for="wc-%s-payment-token-%s">',
			esc_attr( $this->gateway->id ),
			esc_attr( $token->get_id() )
		);

		$html .= $this->saved_payment_method_title( $token );
		$html .= '</label><br />';

		return apply_filters( 'wc_payment_gateway_form_saved_payment_method_html', $html, $token, $this );
	}

	/**
	 * Outputs a saved payment method's title based on the passed token.
	 *
	 * @since 2.6
	 * @param  WC_Payment_Token $token Payment Token
	 * @return string                  Generated payment method title HTML
	 */
	public function saved_payment_method_title( $token ) {
		if ( 'CC' == $token->get_type() && is_callable( array( $token, 'get_card_type' ) ) ) {
			$type = esc_html__( ucfirst( $token->get_card_type() ), 'woocommerce' );
		} else if ( 'eCheck' === $token->get_type() ) {
			$type = esc_html__( 'eCheck', 'woocommerce' );
		}

		$type  = apply_filters( 'wc_payment_gateway_form_saved_payment_method_title_type_html', $type, $token, $this );
		$title = $type;

		if ( is_callable( array( $token, 'get_last4' ) ) ) {
			$title .= '&nbsp;' . sprintf( esc_html__( 'ending in %s', 'woocommerce' ), $token->get_last4() );
		}

		if ( is_callable( array( $token, 'get_expiry_month' ) ) && is_callable( array( $token, 'get_expiry_year' ) ) ) {
			$title .= ' ' . sprintf( esc_html__( '(expires %s)', 'woocommerce' ), $token->get_expiry_month() . '/' . substr( $token->get_expiry_year(), 2 ) );
		}

		return apply_filters( 'wc_payment_gateway_form_saved_payment_method_title_html', $title, $token, $this );
	}

	/**
	 * Outputs payment fields for entering new card info.
	 *
	 * @since 2.6
	 */
	public function payment_fields() {
		if ( $this->gateway->supports( 'credit_card_form' ) ) {
			echo $this->credit_card_payment_fields();
			if ( $this->gateway->supports( 'tokenization' ) ) {
				$this->save_payment_method_checkbox();
			}
		} else if ( $this->gateway->supports( 'echeck_form' ) ) {
			echo $this->echeck_payment_fields();
			if ( $this->gateway->supports( 'tokenization' ) ) {
				$this->save_payment_method_checkbox();
			}
		} else {
			echo apply_filters( 'wc_payment_gateway_form_payment_fields_html', '', $this );
		}
	}

	/**
	 * Filters out tokens not for the current gateway.
	 *
	 * @since 2.6
	 * @param  string $gateway_id Gateway ID
	 * @param  array  $tokens     Set of tokens
	 * @return array              Subset of tokens based on gateway ID
	 */
	public function filter_tokens_by_gateway( $gateway_id, $tokens ) {
		$filtered_tokens = array();
		foreach ( $tokens as $key => $token ) {
			if ( $gateway_id === $token->get_gateway_id() ) {
				$filtered_tokens[ $key ] = $token;
			}
		}
		return $filtered_tokens;
	}

	/**
	 * Outputs fields for entering credit card information.
	 *
	 * @since 2.6
	 */
	public function credit_card_payment_fields() {
		$html           = '';
		$fields         = array();
		$cvc_class      = '';

		$cvc_field = '<p class="form-row form-row-wide">
			<label for="' . esc_attr( $this->gateway->id ) . '-card-cvc">' . __( 'Card Code', 'woocommerce' ) . ' <span class="required">*</span></label>
			<input id="' . esc_attr( $this->gateway->id ) . '-card-cvc" class="input-text wc-credit-card-form-card-cvc" type="text" autocomplete="off" placeholder="' . esc_attr__( 'CVC', 'woocommerce' ) . '" name="' . esc_attr( $this->gateway->id ) . '-card-cvc" style="width:100px" />
		</p>';

		$default_fields = array(
			'card-number-field' => '<p class="form-row form-row-wide">
				<label for="' . esc_attr( $this->gateway->id ) . '-card-number">' . __( 'Card Number', 'woocommerce' ) . ' <span class="required">*</span></label>
				<input id="' . esc_attr( $this->gateway->id ) . '-card-number" class="input-text wc-credit-card-form-card-number" type="text" maxlength="20" autocomplete="off" placeholder="•••• •••• •••• ••••" name="' . esc_attr( $this->gateway->id ) . '-card-number" />
			</p>',
			'card-expiry-field' => '<p class="form-row form-row-first">
				<label for="' . esc_attr( $this->gateway->id ) . '-card-expiry">' . __( 'Expiry (MM/YY)', 'woocommerce' ) . ' <span class="required">*</span></label>
				<input id="' . esc_attr( $this->gateway->id ) . '-card-expiry" class="input-text wc-credit-card-form-card-expiry" type="text" autocomplete="off" placeholder="' . esc_attr__( 'MM / YY', 'woocommerce' ) . '" name="' . esc_attr( $this->gateway->id ) . '-card-expiry" />
			</p>'
		);

		if ( ! $this->gateway->supports( 'credit_card_form_cvc_on_saved_method' ) ) {
			$default_fields['card-cvc-field'] = $cvc_field;
		}

		$fields = wp_parse_args( $fields, apply_filters( 'woocommerce_credit_card_form_fields', $default_fields, $this->gateway->id ) );
		?>

		<fieldset id="wc-<?php echo esc_attr( $this->gateway->id ); ?>-cc-form" class='wc-credit-card-form'>
			<?php do_action( 'woocommerce_credit_card_form_start', $this->gateway->id ); ?>
			<?php
				foreach ( $fields as $field ) {
					echo $field;
				}
			?>
			<?php do_action( 'woocommerce_credit_card_form_end', $this->gateway->id ); ?>
			<div class="clear"></div>
		</fieldset>
		<?php
		if ( $this->gateway->supports( 'credit_card_form_cvc_on_saved_method' ) ) {
			echo '<fieldset>' . $cvc_field . '</fieldset>';
		}
	}

	/**
	 * Outputs fields for entering echeck information.
	 *
	 * @since 2.6
	 * @return string eCheck form HTML
	 */
	public function echeck_payment_fields() {
		$html           = '';
		$fields         = array();

		$default_fields = array(
			'routing-number' => '<p class="form-row form-row-first">
				<label for="' . esc_attr( $this->gateway->id ) . '-routing-number">' . __( 'Routing Number', 'woocommerce' ) . ' <span class="required">*</span></label>
				<input id="' . esc_attr( $this->gateway->id ) . '-routing-number" class="input-text wc-echeck-form-routing-number" type="text" maxlength="9" autocomplete="off" placeholder="•••••••••" name="' . esc_attr( $this->gateway->id ) . '-routing-number" />
			</p>',
			'account-number' => '<p class="form-row form-row-wide">
				<label for="' . esc_attr( $this->gateway->id ) . '-account-number">' . __( 'Account Number', 'woocommerce' ) . ' <span class="required">*</span></label>
				<input id="' . esc_attr( $this->gateway->id ) . '-account-number" class="input-text wc-echeck-form-account-number" type="text" autocomplete="off" name="' . esc_attr( $this->gateway->id ) . '-account-number" maxlength="17" />
			</p>',
		);

		$fields = wp_parse_args( $fields, apply_filters( 'woocommerce_echeck_form_fields', $default_fields, $this->gateway->id ) );
		?>

		<fieldset id="<?php echo esc_attr( $this->gateway->id ); ?>-cc-form" class='wc-echeck-form'>
			<?php do_action( 'woocommerce_echeck_form_start', $this->gateway->id ); ?>
			<?php
				foreach ( $fields as $field ) {
					echo $field;
				}
			?>
			<?php do_action( 'woocommerce_echeck_form_end', $this->gateway->id ); ?>
			<div class="clear"></div>
		</fieldset><?php
	}

	/**
	 * Outputs a checkbox for saving a new payment method.
	 *
	 * @since 2.6
	 */
	public function save_payment_method_checkbox() {
		$html = sprintf(
			'<p class="form-row" id="wc-%s-new-payment-method-wrap">',
			esc_attr( $this->gateway->id )
		);
		$html .= sprintf(
			'<input name="wc-%1$s-new-payment-method" id="wc-%1$s-new-payment-method" type="checkbox" value="true" style="width:auto;"/>',
			esc_attr( $this->gateway->id )
		);
		$html .= sprintf(
			'<label for="wc-%s-new-payment-method" style="display:inline;">%s</label>',
			esc_attr( $this->gateway->id ),
			esc_html__( 'Save to Account', 'woocommerce' )
		);
		$html .= '</p><div class="clear"></div>';
		echo $html;
	}

	public function use_new_payment_method_checkbox() {
		$html = '<input type="radio" id="wc-' . esc_attr( $this->gateway->id ). '-new" name="wc-' . esc_attr( $this->gateway->id ) . '-payment-token" value="new" style="width:auto;">';
		$html .= '<label class="wc-' . esc_attr( $this->gateway->id ) . '-payment-form-new-checkbox wc-gateway-payment-token-label" for="wc-' . esc_attr( $this->gateway->id ) . '-new">';
		$html .=  esc_html__( 'Use a new payment method', 'woocommerce' );
		$html .= '</label>';
		echo '<div class="wc-' . esc_attr( $this->gateway->id ) . '-payment-form-new-checkbox-wrap">' . $html . '</div>';
	}

	public function load_scripts() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_enqueue_script(
			'woocommerce-payment-gateway-form',
			plugins_url(  '/assets/js/frontend/payment-gateway-form' . $suffix . '.js', WC_PLUGIN_FILE ),
			array( 'jquery' ),
			WC()->version
		);

		wp_localize_script( 'woocommerce-payment-gateway-form', 'woocommercePaymentGatewayParams', array(
			'gatewayID'    => $this->gateway->id,
			'userLoggedIn' => (bool) is_user_logged_in(),
		) );
	}

}
