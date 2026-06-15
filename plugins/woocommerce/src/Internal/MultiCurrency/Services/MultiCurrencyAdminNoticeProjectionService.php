<?php
/**
 * MultiCurrencyAdminNoticeProjectionService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency\Services;

/**
 * Projects multi-currency admin notices without registering hooks.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyAdminNoticeProjectionService {

	private const NOTICE_KEY_CURRENCY_CHANGED = 'currency_changed';
	private const NOTICE_OPTION_NAME          = 'wcpay_multi_currency_show_store_currency_changed_notice';
	private const NOTICE_OPTION_HIDDEN_VALUE  = 'no';
	private const HIDE_NOTICE_QUERY_ARG       = 'wcpay-multi-currency-hide-notice';
	private const NONCE_QUERY_ARG             = '_wcpay_multi_currency_notice_nonce';

	/**
	 * Project admin notice hook metadata.
	 *
	 * @return array{actions: array<int,array<string,mixed>>}
	 *
	 * @since 11.0.0
	 */
	public static function get_hook_manifest(): array {
		return array(
			'actions' => array(
				array(
					'hook'     => 'admin_notices',
					'callback' => 'admin_notices',
					'priority' => 10,
				),
				array(
					'hook'     => 'wp_loaded',
					'callback' => 'hide_notices',
					'priority' => 10,
				),
			),
		);
	}

	/**
	 * Project the manual-rate currency-changed notice.
	 *
	 * @param mixed $manual_rate_currencies Manual-rate currency labels.
	 * @return array{key:string,class:string,message:string,dismissible:bool}|null
	 *
	 * @since 11.0.0
	 */
	public static function get_manual_rate_notice( $manual_rate_currencies ): ?array {
		$currency_names = self::get_currency_names( $manual_rate_currencies );
		if ( empty( $currency_names ) ) {
			return null;
		}

		return array(
			'key'         => self::NOTICE_KEY_CURRENCY_CHANGED,
			'class'       => 'notice notice-warning',
			'message'     => sprintf(
				/* translators: %s: List of currencies set to manual rates. */
				__( 'The store currency was recently changed. The following currencies are set to manual rates and may need updates: %s', 'woocommerce' ),
				implode( ', ', $currency_names )
			),
			'dismissible' => true,
		);
	}

	/**
	 * Project notices visible to the current user.
	 *
	 * @param bool  $can_manage_woocommerce Whether the user can manage WooCommerce.
	 * @param mixed $manual_rate_currencies Manual-rate currency labels.
	 * @return array<int,array{key:string,class:string,message:string,dismissible:bool}>
	 *
	 * @since 11.0.0
	 */
	public static function get_notices_for_user( bool $can_manage_woocommerce, $manual_rate_currencies ): array {
		if ( ! $can_manage_woocommerce ) {
			return array();
		}

		$notice = self::get_manual_rate_notice( $manual_rate_currencies );

		return null === $notice ? array() : array( $notice );
	}

	/**
	 * Project admin notice markup.
	 *
	 * @param array<string,mixed> $notice      Notice metadata.
	 * @param string              $dismiss_url Optional dismiss URL.
	 * @return string
	 *
	 * @since 11.0.0
	 */
	public static function get_notice_markup( array $notice, string $dismiss_url = '' ): string {
		$markup  = '<div class="' . esc_attr( (string) ( $notice['class'] ?? '' ) ) . '" style="position:relative;">';
		$markup .= self::get_dismiss_link_markup( (bool) ( $notice['dismissible'] ?? false ), $dismiss_url );
		$markup .= '<p>';
		$markup .= wp_kses(
			(string) ( $notice['message'] ?? '' ),
			array(
				'a' => array(
					'href'   => array(),
					'target' => array(),
				),
			)
		);
		$markup .= '</p></div>';

		return $markup;
	}

	/**
	 * Project a notice dismissal intent.
	 *
	 * @param array<string,mixed> $query                  Query arguments.
	 * @param bool                $nonce_valid            Whether the nonce was validated by the caller.
	 * @param bool                $can_manage_woocommerce Whether the user can manage WooCommerce.
	 * @return array{should_hide: bool, option_name: string|null, option_value: string|null, error: string|null}
	 *
	 * @since 11.0.0
	 */
	public static function get_hide_notice_intent( array $query, bool $nonce_valid, bool $can_manage_woocommerce ): array {
		$notice_key = self::get_clean_query_arg( $query, self::HIDE_NOTICE_QUERY_ARG );
		$nonce      = self::get_clean_query_arg( $query, self::NONCE_QUERY_ARG );
		if ( null === $notice_key || null === $nonce ) {
			return self::get_noop_hide_intent();
		}

		if ( ! $nonce_valid ) {
			return self::get_noop_hide_intent( 'invalid_nonce' );
		}

		if ( ! $can_manage_woocommerce ) {
			return self::get_noop_hide_intent( 'forbidden' );
		}

		if ( self::NOTICE_KEY_CURRENCY_CHANGED !== $notice_key ) {
			return self::get_noop_hide_intent( 'unsupported_notice' );
		}

		return array(
			'should_hide'  => true,
			'option_name'  => self::NOTICE_OPTION_NAME,
			'option_value' => self::NOTICE_OPTION_HIDDEN_VALUE,
			'error'        => null,
		);
	}

	/**
	 * Project dismiss link markup.
	 *
	 * @param bool   $dismissible Whether the notice is dismissible.
	 * @param string $dismiss_url Dismiss URL.
	 * @return string
	 */
	private static function get_dismiss_link_markup( bool $dismissible, string $dismiss_url ): string {
		if ( ! $dismissible || '' === $dismiss_url ) {
			return '';
		}

		return '<a href="' . esc_url( $dismiss_url ) . '" class="woocommerce-message-close notice-dismiss" style="position:relative;float:right;padding:9px 0 9px 9px;text-decoration:none;"></a>';
	}

	/**
	 * Project a no-op hide intent.
	 *
	 * @param string|null $error Error key.
	 * @return array{should_hide: bool, option_name: string|null, option_value: string|null, error: string|null}
	 */
	private static function get_noop_hide_intent( ?string $error = null ): array {
		return array(
			'should_hide'  => false,
			'option_name'  => null,
			'option_value' => null,
			'error'        => $error,
		);
	}

	/**
	 * Get a sanitized scalar query argument.
	 *
	 * @param array<string,mixed> $query Query arguments.
	 * @param string              $key   Query argument key.
	 * @return string|null
	 */
	private static function get_clean_query_arg( array $query, string $key ): ?string {
		$raw_value = $query[ $key ] ?? null;
		if ( ! is_scalar( $raw_value ) ) {
			return null;
		}

		$clean_value = wc_clean( wp_unslash( (string) $raw_value ) );

		return is_string( $clean_value ) && '' !== $clean_value ? $clean_value : null;
	}

	/**
	 * Get manual-rate currency names.
	 *
	 * @param mixed $manual_rate_currencies Manual-rate currency labels.
	 * @return array<int,string>
	 */
	private static function get_currency_names( $manual_rate_currencies ): array {
		if ( ! is_array( $manual_rate_currencies ) ) {
			return array();
		}

		$currency_names = array();
		foreach ( $manual_rate_currencies as $currency_name ) {
			if ( is_scalar( $currency_name ) && '' !== (string) $currency_name ) {
				$currency_names[] = (string) $currency_name;
			}
		}

		return $currency_names;
	}
}
