<?php
/**
 * MultiCurrencyUserSettingsProjectionService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency\Services;

/**
 * Projects My Account multi-currency user settings without registering hooks.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyUserSettingsProjectionService {

	private const FIELD_NAME               = 'wcpay_selected_currency';
	private const EDIT_ACCOUNT_ACTION      = 'woocommerce_edit_account_form';
	private const SAVE_ACCOUNT_ACTION      = 'woocommerce_save_account_details';
	private const PRESENTMENT_SWITCH_CLASS = 'woocommerce-form-row woocommerce-form-row--first form-row form-row-first';

	/**
	 * Project activation blockers for the account presentment currency field.
	 *
	 * @param int $enabled_currency_count Enabled currency count.
	 * @return array<int,string>
	 *
	 * @since 11.0.0
	 */
	public static function get_activation_blockers( int $enabled_currency_count ): array {
		return 1 < $enabled_currency_count ? array() : array( 'single_currency' );
	}

	/**
	 * Project whether account presentment currency settings should activate.
	 *
	 * @param int $enabled_currency_count Enabled currency count.
	 * @return bool
	 *
	 * @since 11.0.0
	 */
	public static function should_activate( int $enabled_currency_count ): bool {
		return array() === self::get_activation_blockers( $enabled_currency_count );
	}

	/**
	 * Project account details hook metadata.
	 *
	 * @param int $enabled_currency_count Enabled currency count.
	 * @return array{actions: array<int,array<string,mixed>>, blockers: array<int,string>}
	 *
	 * @since 11.0.0
	 */
	public static function get_hook_manifest( int $enabled_currency_count ): array {
		$blockers = self::get_activation_blockers( $enabled_currency_count );
		if ( ! empty( $blockers ) ) {
			return array(
				'actions'  => array(),
				'blockers' => $blockers,
			);
		}

		return array(
			'actions'  => array(
				array(
					'hook'     => self::EDIT_ACCOUNT_ACTION,
					'callback' => 'add_presentment_currency_switch',
					'priority' => 10,
				),
				array(
					'hook'     => self::SAVE_ACCOUNT_ACTION,
					'callback' => 'save_presentment_currency',
					'priority' => 10,
				),
			),
			'blockers' => array(),
		);
	}

	/**
	 * Project presentment currency options.
	 *
	 * @param array<int,array{code:string,symbol:string}> $enabled_currencies Enabled currencies.
	 * @param string                                      $selected_code      Selected currency code.
	 * @return array<int,array{code:string,symbol:string,label:string,selected:bool}>
	 *
	 * @since 11.0.0
	 */
	public static function get_currency_options( array $enabled_currencies, string $selected_code ): array {
		$options       = array();
		$selected_code = strtoupper( $selected_code );

		foreach ( $enabled_currencies as $currency ) {
			$code = strtoupper( $currency['code'] );
			if ( '' === $code ) {
				continue;
			}

			$symbol    = $currency['symbol'];
			$options[] = array(
				'code'     => $code,
				'symbol'   => $symbol,
				'label'    => trim( $symbol . ' ' . $code ),
				'selected' => $selected_code === $code,
			);
		}

		return $options;
	}

	/**
	 * Project account-details presentment currency field markup.
	 *
	 * @param array<int,array{code:string,symbol:string}> $enabled_currencies Enabled currencies.
	 * @param string                                      $selected_code      Selected currency code.
	 * @return string
	 *
	 * @since 11.0.0
	 */
	public static function get_presentment_currency_field_markup( array $enabled_currencies, string $selected_code ): string {
		$markup  = '<p class="' . esc_attr( self::PRESENTMENT_SWITCH_CLASS ) . '">';
		$markup .= '<label for="' . esc_attr( self::FIELD_NAME ) . '">' . esc_html__( 'Default currency', 'woocommerce' ) . '</label>';
		$markup .= '<select name="' . esc_attr( self::FIELD_NAME ) . '" id="' . esc_attr( self::FIELD_NAME ) . '">';

		foreach ( self::get_currency_options( $enabled_currencies, $selected_code ) as $option ) {
			$selected = $option['selected'] ? ' selected' : '';
			$markup  .= '<option value="' . esc_attr( $option['code'] ) . '"' . $selected . '>' . wp_kses_post( $option['label'] ) . '</option>';
		}

		$markup .= '</select>';
		$markup .= '<span><em>' . esc_html__( 'Select your preferred currency for shopping and payments.', 'woocommerce' ) . '</em></span>';
		$markup .= '</p>';
		$markup .= '<div class="clear"></div>';

		return $markup;
	}

	/**
	 * Project sanitized save intent from explicit posted data.
	 *
	 * @param array<string,mixed> $posted_data Posted data.
	 * @return array{should_update: bool, currency_code: string|null}
	 *
	 * @since 11.0.0
	 */
	public static function get_save_presentment_currency_intent( array $posted_data ): array {
		$noop = array(
			'should_update' => false,
			'currency_code' => null,
		);

		$raw_currency_code = $posted_data[ self::FIELD_NAME ] ?? null;
		if ( ! is_scalar( $raw_currency_code ) ) {
			return $noop;
		}

		$cleaned_currency_code = wc_clean( wp_unslash( (string) $raw_currency_code ) );
		$currency_code         = is_string( $cleaned_currency_code ) ? trim( $cleaned_currency_code ) : '';
		if ( '' === $currency_code ) {
			return $noop;
		}

		return array(
			'should_update' => true,
			'currency_code' => $currency_code,
		);
	}
}
