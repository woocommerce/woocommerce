<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiClient;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiException;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsEmbeddedAccountSessionService;
use WC_Unit_Test_Case;

/**
 * Tests for the WooPaymentsEmbeddedAccountSessionService class.
 */
class WooPaymentsEmbeddedAccountSessionServiceTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WooPaymentsEmbeddedAccountSessionService
	 */
	private WooPaymentsEmbeddedAccountSessionService $sut;

	/**
	 * Recording API client.
	 *
	 * @var WooPaymentsApiClient
	 */
	private WooPaymentsApiClient $api_client;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->api_client = $this->create_api_client();
		$this->sut        = new WooPaymentsEmbeddedAccountSessionService();
		$this->sut->init( $this->api_client );
	}

	/**
	 * @testdox Should map platform session fields and add the current user locale.
	 */
	public function test_create_session_maps_platform_fields_and_adds_locale(): void {
		$this->api_client->response = array(
			'client_secret'   => 'cs_test',
			'expires_at'      => 1781740800,
			'account_id'      => 'acct_native',
			'is_live'         => false,
			'publishable_key' => 'pk_test_native',
			'unexpected_key'  => 'must_not_leak',
		);

		$session = $this->sut->create_session();

		$this->assertSame( 'create_embedded_account_session', $this->api_client->last_call );
		$this->assertSame( 'cs_test', $session['clientSecret'] );
		$this->assertSame( 1781740800, $session['expiresAt'] );
		$this->assertSame( 'acct_native', $session['accountId'] );
		$this->assertFalse( $session['isLive'] );
		$this->assertSame( 'pk_test_native', $session['publishableKey'] );
		$this->assertSame( get_user_locale(), $session['locale'] );
		$this->assertArrayNotHasKey( 'unexpected_key', $session );
		$this->assertArrayNotHasKey( 'client_secret', $session );
		$this->assertArrayNotHasKey( 'publishable_key', $session );
	}

	/**
	 * @testdox Should fail closed when the platform session payload is malformed.
	 *
	 * @dataProvider malformed_payload_provider
	 *
	 * @param array<string,mixed> $payload Malformed platform payload.
	 */
	public function test_create_session_returns_empty_array_for_malformed_payloads( array $payload ): void {
		$this->api_client->response = $payload;

		$session = $this->sut->create_session();

		$this->assertSame( array(), $session, 'Malformed account-session payloads should not be exposed.' );
	}

	/**
	 * @testdox Should fail closed when the platform session request fails.
	 */
	public function test_create_session_returns_empty_array_when_api_request_fails(): void {
		$this->api_client->exception = new WooPaymentsApiException( 'Disconnected.', 'wcpay_wpcom_not_connected', 409 );

		$session = $this->sut->create_session();

		$this->assertSame( array(), $session, 'Disconnected account-session requests should fail closed.' );
	}

	/**
	 * Data provider for malformed payload tests.
	 *
	 * @return array<string,array<int,array<string,mixed>>>
	 */
	public function malformed_payload_provider(): array {
		return array(
			'missing client secret'   => array(
				array(
					'expires_at'      => 1781740800,
					'account_id'      => 'acct_native',
					'is_live'         => false,
					'publishable_key' => 'pk_test_native',
				),
			),
			'missing publishable key' => array(
				array(
					'client_secret' => 'cs_test',
					'expires_at'    => 1781740800,
					'account_id'    => 'acct_native',
					'is_live'       => false,
				),
			),
			'wrong scalar types'      => array(
				array(
					'client_secret'   => array( 'cs_test' ),
					'expires_at'      => '1781740800',
					'account_id'      => 'acct_native',
					'is_live'         => false,
					'publishable_key' => 'pk_test_native',
				),
			),
		);
	}

	/**
	 * Create a recording API client.
	 *
	 * @return WooPaymentsApiClient
	 */
	private function create_api_client(): WooPaymentsApiClient {
		return new class() extends WooPaymentsApiClient {

			/**
			 * Last called method.
			 *
			 * @var string
			 */
			public string $last_call = '';

			/**
			 * Response returned by the client.
			 *
			 * @var array<string,mixed>
			 */
			public array $response = array();

			/**
			 * Optional exception thrown by the next call.
			 *
			 * @var WooPaymentsApiException|null
			 */
			public ?WooPaymentsApiException $exception = null;

			/**
			 * Create an embedded account session.
			 *
			 * @return array<string,mixed>
			 * @throws WooPaymentsApiException When configured.
			 */
			public function create_embedded_account_session(): array {
				$this->last_call = __FUNCTION__;

				if ( null !== $this->exception ) {
					throw $this->exception;
				}

				return $this->response;
			}
		};
	}
}
