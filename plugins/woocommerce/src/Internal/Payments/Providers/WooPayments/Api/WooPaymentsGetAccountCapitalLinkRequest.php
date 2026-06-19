<?php
/**
 * WooPaymentsGetAccountCapitalLinkRequest class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api;

/**
 * Request object for the preserved WooPayments Capital account-link hook.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsGetAccountCapitalLinkRequest extends WooPaymentsApiRequest {

	protected const DEFAULT_PARAMS = array();

	private const API = 'accounts/capital_links';

	private const TYPE = 'capital_financing_offer';

	/**
	 * Create a request for the Capital view-offer link.
	 *
	 * @param string $return_url  URL to return to after viewing the offer.
	 * @param string $refresh_url URL to use when the link expires or is invalid.
	 * @return self
	 */
	public static function from_urls( string $return_url, string $refresh_url ): self {
		$request = new self();
		$request->set_api( self::API );
		$request->set_method( 'POST' );
		$request->set_type( self::TYPE );
		$request->set_return_url( $return_url );
		$request->set_refresh_url( $refresh_url );

		return $request;
	}

	/**
	 * Register the legacy WooPayments request alias when the extension is absent.
	 */
	public static function register_legacy_aliases(): void {
		parent::register_legacy_aliases();

		if ( ! class_exists( 'WCPay\Core\Server\Request\Get_Account_Capital_Link', false ) ) {
			class_alias( self::class, 'WCPay\Core\Server\Request\Get_Account_Capital_Link' );
		}
	}

	/**
	 * Preserve the legacy Capital link type setter.
	 *
	 * @param string $type Capital link type.
	 */
	public function set_type( string $type ): void {
		$this->set_param( 'type', $type );
	}

	/**
	 * Preserve the legacy return URL setter.
	 *
	 * @param string $return_url URL to return to after viewing the offer.
	 */
	public function set_return_url( string $return_url ): void {
		$this->set_param( 'return_url', $return_url );
	}

	/**
	 * Preserve the legacy refresh URL setter.
	 *
	 * @param string $refresh_url URL to use when the link expires or is invalid.
	 */
	public function set_refresh_url( string $refresh_url ): void {
		$this->set_param( 'refresh_url', $refresh_url );
	}

	/**
	 * Capital link requests must use the connection-owner user token.
	 *
	 * @return bool
	 */
	public function should_use_user_token(): bool {
		return true;
	}
}
