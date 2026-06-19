<?php
/**
 * WooPaymentsEmbeddedAccountSessionService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiClient;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiException;

/**
 * Creates sanitized WooPayments embedded account sessions for native admin surfaces.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsEmbeddedAccountSessionService {

	/**
	 * Native WooPayments API client.
	 *
	 * @var WooPaymentsApiClient
	 */
	private WooPaymentsApiClient $api_client;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param WooPaymentsApiClient $api_client Native WooPayments API client.
	 */
	final public function init( WooPaymentsApiClient $api_client ): void {
		$this->api_client = $api_client;
	}

	/**
	 * Create a sanitized embedded account session.
	 *
	 * @return array<string,mixed>
	 */
	public function create_session(): array {
		try {
			$session = $this->api_client->create_embedded_account_session();
		} catch ( WooPaymentsApiException $exception ) {
			unset( $exception );
			return array();
		}

		if ( ! $this->is_valid_session( $session ) ) {
			return array();
		}

		return array(
			'clientSecret'   => $session['client_secret'],
			'expiresAt'      => $session['expires_at'],
			'accountId'      => $session['account_id'],
			'isLive'         => $session['is_live'],
			'publishableKey' => $session['publishable_key'],
			'locale'         => get_user_locale(),
		);
	}

	/**
	 * Tell whether the platform session payload has the exact safe fields the frontend needs.
	 *
	 * @param array<string,mixed> $session Platform session payload.
	 * @return bool
	 */
	private function is_valid_session( array $session ): bool {
		return isset( $session['client_secret'], $session['expires_at'], $session['account_id'], $session['is_live'], $session['publishable_key'] )
			&& is_string( $session['client_secret'] )
			&& '' !== $session['client_secret']
			&& is_int( $session['expires_at'] )
			&& is_string( $session['account_id'] )
			&& '' !== $session['account_id']
			&& is_bool( $session['is_live'] )
			&& is_string( $session['publishable_key'] )
			&& '' !== $session['publishable_key'];
	}
}
