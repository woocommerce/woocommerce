<?php
/**
 * ApiClientTest class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\FraudProtection;

use Automattic\WooCommerce\Internal\FraudProtection\ApiClient;
use Automattic\WooCommerce\RestApi\UnitTests\LoggerSpyTrait;
use WC_Unit_Test_Case;
use WP_Error;

/**
 * Tests for the ApiClient class.
 */
class ApiClientTest extends WC_Unit_Test_Case {

	use LoggerSpyTrait;

	/**
	 * The System Under Test.
	 *
	 * @var ApiClient
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut = new ApiClient();

		update_option( 'jetpack_options', array( 'id' => 12345 ) );
		update_option( 'jetpack_private_options', array( 'blog_token' => 'IAM.AJETPACKBLOGTOKEN' ) );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_all_filters( 'pre_http_request' );
		delete_option( 'jetpack_options' );
		delete_option( 'jetpack_private_options' );
		parent::tearDown();
	}

	/**
	 * @testdox Track Event should return allow when Jetpack blog ID is not found.
	 */
	public function test_track_event_returns_allow_when_blog_id_not_found(): void {
		update_option( 'jetpack_options', array( 'id' => null ) );

		$result = $this->sut->track_event( 'cart_updated', array( 'session_id' => 'test-session' ) );

		$this->assertSame( ApiClient::DECISION_ALLOW, $result, 'Should fail open with allow decision' );
		$this->assertLogged( 'error', 'Jetpack blog ID not found', array( 'source' => 'woo-fraud-protection' ) );
	}

	/**
	 * @testdox Track Event should return allow when HTTP request fails.
	 */
	public function test_track_event_returns_allow_when_http_request_fails(): void {
		add_filter(
			'pre_http_request',
			function () {
				return new WP_Error( 'http_error', 'Connection failed', 'error_data' );
			}
		);

		$result = $this->sut->track_event( 'cart_updated', array( 'session_id' => 'test-session' ) );

		$this->assertSame( ApiClient::DECISION_ALLOW, $result, 'Should fail open with allow decision' );
		$this->assertLogged(
			'error',
			'Connection failed',
			array(
				'source' => 'woo-fraud-protection',
				'error'  => 'error_data',
			)
		);
	}

	/**
	 * @testdox Track Event should return allow when API returns HTTP error status.
	 */
	public function test_track_event_returns_allow_when_api_returns_http_error(): void {
		add_filter(
			'pre_http_request',
			function () {
				return array(
					'response' => array( 'code' => 500 ),
					'body'     => 'Internal Server Error',
				);
			}
		);

		$result = $this->sut->track_event( 'cart_updated', array( 'session_id' => 'test-session' ) );

		$this->assertSame( ApiClient::DECISION_ALLOW, $result, 'Should fail open with allow decision' );
		$this->assertLogged(
			'error',
			'Endpoint POST transact/fraud-protection/events returned status code 500',
			array(
				'source'   => 'woo-fraud-protection',
				'response' => 'Internal Server Error',
			)
		);
	}

	/**
	 * @testdox Track Event should return allow when API returns HTTP error with JSON body.
	 */
	public function test_track_event_returns_allow_when_api_returns_http_error_with_json_body(): void {
		$response = array(
			'error'   => 'invalid_request',
			'message' => 'Missing required field',
		);

		add_filter(
			'pre_http_request',
			function () use ( $response ) {
				return array(
					'response' => array( 'code' => 400 ),
					'body'     => wp_json_encode( $response ),
				);
			}
		);

		$result = $this->sut->track_event( 'cart_updated', array( 'session_id' => 'test-session' ) );

		$this->assertSame( ApiClient::DECISION_ALLOW, $result, 'Should fail open with allow decision' );
		$this->assertLogged(
			'error',
			'Endpoint POST transact/fraud-protection/events returned status code 400',
			array(
				'source'   => 'woo-fraud-protection',
				'response' => $response,
			)
		);
	}

	/**
	 * @testdox Track Event should return allow when API returns invalid JSON.
	 */
	public function test_track_event_returns_allow_when_api_returns_invalid_json(): void {
		add_filter(
			'pre_http_request',
			function () {
				return array(
					'response' => array( 'code' => 200 ),
					'body'     => 'not valid json',
				);
			}
		);

		$result = $this->sut->track_event( 'cart_updated', array( 'session_id' => 'test-session' ) );

		$this->assertSame( ApiClient::DECISION_ALLOW, $result, 'Should fail open with allow decision' );
		$this->assertLogged(
			'error',
			'Failed to decode JSON response: Syntax error',
			array(
				'source'   => 'woo-fraud-protection',
				'response' => 'not valid json',
			)
		);
	}

	/**
	 * @testdox Track Event should return allow when API response is missing verdict field.
	 */
	public function test_track_event_returns_allow_when_response_missing_verdict_field(): void {
		add_filter(
			'pre_http_request',
			function () {
				return array(
					'response' => array( 'code' => 200 ),
					'body'     => wp_json_encode( array( 'fraud_event_id' => 123 ) ),
				);
			}
		);

		$result = $this->sut->track_event( 'cart_updated', array( 'session_id' => 'test-session' ) );

		$this->assertSame( ApiClient::DECISION_ALLOW, $result, 'Should fail open with allow decision' );
		$this->assertLogged(
			'error',
			'missing "verdict" field',
			array(
				'source'   => 'woo-fraud-protection',
				'response' => array( 'fraud_event_id' => 123 ),
			)
		);
	}

	/**
	 * @testdox Track Event should return allow when API returns invalid verdict value.
	 */
	public function test_track_event_returns_allow_when_invalid_verdict_value(): void {
		add_filter(
			'pre_http_request',
			function () {
				return array(
					'response' => array( 'code' => 200 ),
					'body'     => wp_json_encode( array( 'verdict' => 'invalid_verdict' ) ),
				);
			}
		);

		$result = $this->sut->track_event( 'cart_updated', array( 'session_id' => 'test-session' ) );

		$this->assertSame( ApiClient::DECISION_ALLOW, $result, 'Should fail open with allow decision' );
		$this->assertLogged(
			'error',
			'Invalid verdict value "invalid_verdict"',
			array(
				'source'   => 'woo-fraud-protection',
				'response' => array( 'verdict' => 'invalid_verdict' ),
			)
		);
	}

	/**
	 * @testdox Track Event should return allow verdict from API.
	 */
	public function test_track_event_returns_allow_verdict_from_api(): void {
		add_filter(
			'pre_http_request',
			function () {
				return array(
					'response' => array( 'code' => 200 ),
					'body'     => wp_json_encode(
						array(
							'fraud_event_id' => 123,
							'verdict'        => 'allow',
							'risk_score'     => 10,
						)
					),
				);
			}
		);

		$result = $this->sut->track_event( 'cart_updated', array( 'session_id' => 'test-session' ) );

		$this->assertSame( ApiClient::DECISION_ALLOW, $result, 'Should return allow verdict' );
		$this->assertLogged(
			'info',
			'Fraud verdict received: allow',
			array(
				'source'   => 'woo-fraud-protection',
				'response' => array(
					'fraud_event_id' => 123,
					'verdict'        => 'allow',
					'risk_score'     => 10,
				),
			)
		);
		$this->assertNoErrorLogged();
	}

	/**
	 * @testdox Track Event should return block verdict from API.
	 */
	public function test_track_event_returns_block_verdict_from_api(): void {
		add_filter(
			'pre_http_request',
			function () {
				return array(
					'response' => array( 'code' => 200 ),
					'body'     => wp_json_encode(
						array(
							'fraud_event_id' => 123,
							'verdict'        => 'block',
							'risk_score'     => 95,
							'reason_tags'    => array( 'failures_per_ip' ),
						)
					),
				);
			}
		);

		$result = $this->sut->track_event( 'checkout_started', array( 'session_id' => 'test-session' ) );

		$this->assertSame( ApiClient::DECISION_BLOCK, $result, 'Should return block verdict' );
		$this->assertLogged(
			'info',
			'Fraud verdict received: block',
			array(
				'source'   => 'woo-fraud-protection',
				'response' => array(
					'fraud_event_id' => 123,
					'verdict'        => 'block',
					'risk_score'     => 95,
					'reason_tags'    => array( 'failures_per_ip' ),
				),
			)
		);
		$this->assertNoErrorLogged();
	}

	/**
	 * @testdox Track Event should return allow when API returns challenge verdict (challenge flow not yet implemented).
	 */
	public function test_track_event_returns_allow_when_challenge_verdict_from_api(): void {
		add_filter(
			'pre_http_request',
			function () {
				return array(
					'response' => array( 'code' => 200 ),
					'body'     => wp_json_encode(
						array(
							'fraud_event_id' => 123,
							'verdict'        => 'challenge',
							'risk_score'     => 65,
						)
					),
				);
			}
		);

		$result = $this->sut->track_event( 'checkout_started', array( 'session_id' => 'test-session' ) );

		$this->assertSame( ApiClient::DECISION_ALLOW, $result, 'Should fail open with allow until challenge flow is implemented' );
		$this->assertLogged( 'error', 'Invalid verdict value "challenge"' );
	}

	/**
	 * @testdox Should filter out null values from session data payload.
	 */
	public function test_filters_null_values_from_payload(): void {
		$captured_request_body = null;

		add_filter(
			'pre_http_request',
			function ( $preempt, $args ) use ( &$captured_request_body ) {
				$captured_request_body = $args['body'];
				return array(
					'response' => array( 'code' => 200 ),
					'body'     => wp_json_encode( array( 'verdict' => 'allow' ) ),
				);
			},
			10,
			2
		);

		$session_data = array(
			'session_id'   => 'test-session',
			'ip_address'   => '192.168.1.1',
			'email'        => null,
			'user_agent'   => 'Mozilla/5.0',
			'billing_name' => null,
		);

		$this->sut->track_event( 'cart_updated', $session_data );

		$this->assertNotNull( $captured_request_body, 'Request body should be captured' );

		$decoded_body = json_decode( $captured_request_body, true );
		$this->assertArrayHasKey( 'event_type', $decoded_body, 'Should include not-null event_type' );
		$this->assertArrayHasKey( 'session_id', $decoded_body, 'Should include not-null session_id' );
		$this->assertArrayHasKey( 'ip_address', $decoded_body, 'Should include not-null ip_address' );
		$this->assertArrayHasKey( 'user_agent', $decoded_body, 'Should include not-null user_agent' );
		$this->assertArrayNotHasKey( 'email', $decoded_body, 'Should filter out null email' );
		$this->assertArrayNotHasKey( 'billing_name', $decoded_body, 'Should filter out null billing_name' );
	}
}
