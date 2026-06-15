<?php
/**
 * MultiCurrencySwitcherProjectionService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency\Services;

use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyCurrency;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyState;

/**
 * Projects multi-currency switcher markup without registering runtime surfaces.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencySwitcherProjectionService {

	private const SWITCHER_CLASS = 'js-woopayments-currency-switcher';
	private const WIDGET_ID_BASE = 'currency_switcher_widget';

	private const DEFAULT_WIDGET_SETTINGS = array(
		'title'  => '',
		'symbol' => true,
		'flag'   => false,
	);

	private const DEFAULT_WIDGET_ARGS = array(
		'before_widget' => '',
		'after_widget'  => '',
		'before_title'  => '',
		'after_title'   => '',
	);

	private const FLAGLESS_CURRENCIES = array(
		'ANG' => true,
		'BTC' => true,
		'XAF' => true,
		'XCD' => true,
		'XOF' => true,
		'XPF' => true,
	);

	/**
	 * State builder.
	 *
	 * @var MultiCurrencyStateBuilder
	 */
	private MultiCurrencyStateBuilder $state_builder;

	/**
	 * Constructor.
	 *
	 * @param MultiCurrencyStateBuilder $state_builder State builder.
	 *
	 * @since 11.0.0
	 */
	public function __construct( MultiCurrencyStateBuilder $state_builder ) {
		$this->state_builder = $state_builder;
	}

	/**
	 * Project WooPayments-compatible widget markup.
	 *
	 * @param array<string,mixed> $instance           Widget instance settings.
	 * @param array<string,mixed> $args               Widget wrapper arguments.
	 * @param array<string,mixed> $query_args         Query arguments to preserve.
	 * @param bool                $switching_disabled Whether switching is disabled.
	 * @return string
	 *
	 * @since 11.0.0
	 */
	public function get_widget_markup(
		array $instance = array(),
		array $args = array(),
		array $query_args = array(),
		bool $switching_disabled = false
	): string {
		$state = $this->state_builder->build();
		if ( $switching_disabled || ! $state->has_additional_currencies_enabled() ) {
			return '';
		}

		$instance = wp_parse_args( $instance, self::DEFAULT_WIDGET_SETTINGS );
		$args     = wp_parse_args( $args, self::DEFAULT_WIDGET_ARGS );
		/**
		 * Filters the currency switcher widget title.
		 *
		 * @param string              $title    Widget title.
		 * @param array<string,mixed> $instance Widget instance settings.
		 * @param string              $id_base  Widget ID base.
		 *
		 * @since 11.0.0
		 */
		$title  = (string) apply_filters( 'widget_title', (string) $instance['title'], $instance, self::WIDGET_ID_BASE );
		$markup = (string) $args['before_widget'];

		if ( '' !== $title ) {
			$markup .= (string) $args['before_title'] . wp_kses_post( $title ) . (string) $args['after_title'];
		}

		$markup .= '<form>';
		$markup .= self::get_hidden_query_inputs( $query_args );
		$markup .= $this->get_select_markup(
			$state,
			(bool) $instance['symbol'],
			(bool) $instance['flag'],
			$this->get_accessible_label( $title )
		);
		$markup .= '</form>';
		$markup .= (string) $args['after_widget'];

		return $markup;
	}

	/**
	 * Project WooPayments-compatible dynamic block markup.
	 *
	 * @param array<string,mixed> $block_attributes   Block attributes.
	 * @param array<string,mixed> $query_args         Query arguments to preserve.
	 * @param bool                $switching_disabled Whether switching is disabled.
	 * @return string
	 *
	 * @since 11.0.0
	 */
	public function get_block_markup(
		array $block_attributes = array(),
		array $query_args = array(),
		bool $switching_disabled = false
	): string {
		$state = $this->state_builder->build();
		if ( $switching_disabled || ! $state->has_additional_currencies_enabled() ) {
			return '';
		}

		$styles        = $this->get_block_styles( $block_attributes );
		$div_styles    = self::implode_styles_array( $styles['div'] );
		$select_styles = self::implode_styles_array( $styles['select'] );
		$with_symbol   = isset( $block_attributes['symbol'] ) ? (bool) $block_attributes['symbol'] : true;
		$with_flag     = isset( $block_attributes['flag'] ) ? (bool) $block_attributes['flag'] : false;

		$markup  = '<form>';
		$markup .= self::get_hidden_query_inputs( $query_args );
		$markup .= '<div class="currency-switcher-holder" style="' . esc_attr( $div_styles ) . '">';
		$markup .= $this->get_select_markup(
			$state,
			$with_symbol,
			$with_flag,
			$this->get_accessible_label( '' ),
			$select_styles
		);
		$markup .= '</div></form>';

		return $markup;
	}

	/**
	 * Get select markup for the enabled currency list.
	 *
	 * @param MultiCurrencyState $state         Multi-currency state.
	 * @param bool               $with_symbol   Whether to include currency symbols.
	 * @param bool               $with_flag     Whether to include currency flags.
	 * @param string             $aria_label    Accessible select label.
	 * @param string             $select_styles Optional select styles.
	 * @return string
	 */
	private function get_select_markup(
		MultiCurrencyState $state,
		bool $with_symbol,
		bool $with_flag,
		string $aria_label,
		string $select_styles = ''
	): string {
		$style_attribute = '' !== $select_styles
			? ' style="' . esc_attr( $select_styles ) . '"'
			: '';
		$markup          = '<select name="currency" class="' . esc_attr( self::SWITCHER_CLASS ) . '" aria-label="' . esc_attr( $aria_label ) . '" onchange="this.form.submit()"' . $style_attribute . '>';
		$selected_code   = $state->get_selected_currency()->get_code();

		foreach ( $state->get_enabled_currencies() as $currency ) {
			$markup .= $this->get_currency_option_markup( $currency, $selected_code, $with_symbol, $with_flag );
		}

		$markup .= '</select>';

		return $markup;
	}

	/**
	 * Get option markup for a currency.
	 *
	 * @param MultiCurrencyCurrency $currency      Currency.
	 * @param string                $selected_code Selected currency code.
	 * @param bool                  $with_symbol   Whether to include currency symbols.
	 * @param bool                  $with_flag     Whether to include currency flags.
	 * @return string
	 */
	private function get_currency_option_markup(
		MultiCurrencyCurrency $currency,
		string $selected_code,
		bool $with_symbol,
		bool $with_flag
	): string {
		$code     = $currency->get_code();
		$selected = $selected_code === $code ? ' selected' : '';

		return '<option value="' . esc_attr( $code ) . '"' . $selected . '>' . wp_kses_post( $this->get_currency_option_label( $currency, $with_symbol, $with_flag ) ) . '</option>';
	}

	/**
	 * Get a currency option label.
	 *
	 * @param MultiCurrencyCurrency $currency    Currency.
	 * @param bool                  $with_symbol Whether to include the symbol.
	 * @param bool                  $with_flag   Whether to include the flag.
	 * @return string
	 */
	private function get_currency_option_label( MultiCurrencyCurrency $currency, bool $with_symbol, bool $with_flag ): string {
		$code        = $currency->get_code();
		$symbol      = $currency->get_symbol();
		$same_symbol = html_entity_decode( $symbol, ENT_QUOTES, 'UTF-8' ) === $code;
		$text        = $code;

		if ( $with_symbol && ! $same_symbol ) {
			$text = $symbol . ' ' . $text;
		}

		if ( $with_flag ) {
			$flag = self::get_flag_by_currency( $code );
			if ( '' !== $flag ) {
				$text = $flag . ' ' . $text;
			}
		}

		return $text;
	}

	/**
	 * Get hidden inputs for preserved query arguments.
	 *
	 * @param array<string,mixed> $query_args Query arguments.
	 * @return string
	 */
	private static function get_hidden_query_inputs( array $query_args ): string {
		if ( empty( $query_args ) ) {
			return '';
		}

		$params = explode( '&', urldecode( http_build_query( $query_args ) ) );
		$markup = '';

		foreach ( $params as $param ) {
			if ( '' === $param ) {
				continue;
			}

			$name_value = explode( '=', $param, 2 );
			$name       = (string) $name_value[0];
			$value      = (string) ( $name_value[1] ?? '' );

			if ( 'currency' === $name ) {
				continue;
			}

			$markup .= '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" />';
		}

		return $markup;
	}

	/**
	 * Get block styles grouped by target element.
	 *
	 * @param array<string,mixed> $block_attributes Block attributes.
	 * @return array{div: array<string,string>, select: array<string,string>}
	 */
	private function get_block_styles( array $block_attributes ): array {
		return array(
			'div'    => array(
				'line-height' => self::get_numeric_style_value( $block_attributes['fontLineHeight'] ?? null, '1.2' ),
			),
			'select' => array(
				'padding'          => '2px',
				'border'           => ! empty( $block_attributes['border'] ) ? '1px solid' : '0px solid',
				'border-radius'    => isset( $block_attributes['borderRadius'] ) ? absint( $block_attributes['borderRadius'] ) . 'px' : '3px',
				'border-color'     => self::sanitize_color_value( $block_attributes['borderColor'] ?? null, '#000000' ),
				'font-size'        => isset( $block_attributes['fontSize'] ) ? absint( $block_attributes['fontSize'] ) . 'px' : '11px',
				'color'            => self::sanitize_color_value( $block_attributes['fontColor'] ?? null, '#000000' ),
				'background-color' => self::sanitize_color_value( $block_attributes['backgroundColor'] ?? null, '#000000' ),
			),
		);
	}

	/**
	 * Implode style declarations into CSS text.
	 *
	 * @param array<string,string> $styles CSS style declarations.
	 * @return string
	 */
	private static function implode_styles_array( array $styles ): string {
		$return_str = '';

		foreach ( $styles as $key => $value ) {
			$return_str .= $key . ': ' . $value . '; ';
		}

		return trim( $return_str );
	}

	/**
	 * Get a numeric CSS value.
	 *
	 * @param mixed  $value    Raw value.
	 * @param string $fallback Fallback value.
	 * @return string
	 */
	private static function get_numeric_style_value( $value, string $fallback ): string {
		return is_numeric( $value ) ? (string) (float) $value : $fallback;
	}

	/**
	 * Sanitize a CSS color value.
	 *
	 * @param mixed  $value    Raw color value.
	 * @param string $fallback Fallback color value.
	 * @return string
	 */
	private static function sanitize_color_value( $value, string $fallback ): string {
		if ( ! is_scalar( $value ) ) {
			return $fallback;
		}

		$value = trim( (string) $value );
		if ( 'transparent' === strtolower( $value ) ) {
			return 'transparent';
		}

		$sanitized = sanitize_hex_color( $value );

		return null !== $sanitized ? $sanitized : $fallback;
	}

	/**
	 * Get an accessible label for the currency select.
	 *
	 * @param string $title Widget title.
	 * @return string
	 */
	private function get_accessible_label( string $title ): string {
		$title = trim( wp_strip_all_tags( $title ) );

		return '' !== $title ? $title : __( 'Currency', 'woocommerce' );
	}

	/**
	 * Get an ISO flag by currency code.
	 *
	 * @param string $currency_code Currency code.
	 * @return string
	 */
	private static function get_flag_by_currency( string $currency_code ): string {
		$currency_code = strtoupper( $currency_code );
		if ( isset( self::FLAGLESS_CURRENCIES[ $currency_code ] ) ) {
			return '';
		}

		$country_code = substr( $currency_code, 0, -1 );

		return is_string( $country_code ) ? self::get_flag_by_country( $country_code ) : '';
	}

	/**
	 * Get an ISO flag by country code.
	 *
	 * @param string $country_code Country code.
	 * @return string
	 */
	private static function get_flag_by_country( string $country_code ): string {
		$country_code = strtoupper( $country_code );
		if ( 1 !== preg_match( '/^[A-Z]{2}$/', $country_code ) ) {
			return '';
		}

		$first  = 0x1F1E6 + ord( $country_code[0] ) - ord( 'A' );
		$second = 0x1F1E6 + ord( $country_code[1] ) - ord( 'A' );

		return html_entity_decode(
			'&#x' . dechex( $first ) . ';&#x' . dechex( $second ) . ';',
			ENT_NOQUOTES,
			'UTF-8'
		);
	}
}
