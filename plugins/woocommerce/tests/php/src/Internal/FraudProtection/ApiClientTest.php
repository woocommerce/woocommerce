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
	 * @testdox Send Event should return allow when Jetpack blog ID is not found.
	 */
	public function test_send_event_returns_allow_when_blog_id_not_found(): void {
		update_option( 'jetpack_options', array( 'id' => null ) );

		$result = $this->sut->send_event( 'cart_updated', array( 'session_id' => 'test-session' ) );

		$this->assertSame( ApiClient::DECISION_ALLOW, $result, 'Should fail open with allow decision' );
		$this->assertLogged( 'error', 'Jetpack blog ID not found', array( 'source' => 'woo-fraud-protection' ) );
	}

	/**
	 * @testdox Send Event should return allow when HTTP request fails.
	 */
	public function test_send_event_returns_allow_when_http_request_fails(): void {
		add_filter(
			'pre_http_request',
			function () {
				return new WP_Error( 'http_error', 'Connection failed', 'error_data' );
			}
		);

		$result = $this->sut->send_event( 'cart_updated', array( 'session_id' => 'test-session' ) );

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
	 * @testdox Send Event should return allow when API returns HTTP error status.
	 */
	public function test_send_event_returns_allow_when_api_returns_http_error(): void {
		add_filter(
			'pre_http_request',
			function () {
				return array(
					'response' => array( 'code' => 500 ),
					'body'     => 'Internal Server Error',
				);
			}
		);

		$result = $this->sut->send_event( 'cart_updated', array( 'session_id' => 'test-session' ) );

		$this->assertSame( ApiClient::DECISION_ALLOW, $result, 'Should fail open with allow decision' );
		$this->assertLogged(
			'error',
			'Endpoint POST transact/fraud_protection/events returned status code 500',
			array(
				'source'   => 'woo-fraud-protection',
				'response' => 'Internal Server Error',
			)
		);
	}

	/**
	 * @testdox Send Event should return allow when API returns HTTP error with JSON body.
	 */
	public function test_send_event_returns_allow_when_api_returns_http_error_with_json_body(): void {
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

		$result = $this->sut->send_event( 'cart_updated', array( 'session_id' => 'test-session' ) );

		$this->assertSame( ApiClient::DECISION_ALLOW, $result, 'Should fail open with allow decision' );
		$this->assertLogged(
			'error',
			'Endpoint POST transact/fraud_protection/events returned status code 400',
			array(
				'source'   => 'woo-fraud-protection',
				'response' => $response,
			)
		);
	}

	/**
	 * @testdox Send Event should return allow when API returns invalid JSON.
	 */
	public function test_send_event_returns_allow_when_api_returns_invalid_json(): void {
		add_filter(
			'pre_http_request',
			function () {
				return array(
					'response' => array( 'code' => 200 ),
					'body'     => 'not valid json',
				);
			}
		);

		$result = $this->sut->send_event( 'cart_updated', array( 'session_id' => 'test-session' ) );

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
	 * @testdox Send Event should return allow when API response is missing decision field.
	 */
	public function test_send_event_returns_allow_when_response_missing_decision_field(): void {
		add_filter(
			'pre_http_request',
			function () {
				return array(
					'response' => array( 'code' => 200 ),
					'body'     => wp_json_encode( array( 'fraud_event_id' => 123 ) ),
				);
			}
		);

		$result = $this->sut->send_event( 'cart_updated', array( 'session_id' => 'test-session' ) );

		$this->assertSame( ApiClient::DECISION_ALLOW, $result, 'Should fail open with allow decision' );
		$this->assertLogged(
			'error',
			'missing "decision" field',
			array(
				'source'   => 'woo-fraud-protection',
				'response' => array( 'fraud_event_id' => 123 ),
			)
		);
	}

	/**
	 * @testdox Send Event should return allow when API returns invalid decision value.
	 */
	public function test_send_event_returns_allow_when_invalid_decision_value(): void {
		add_filter(
			'pre_http_request',
			function () {
				return array(
					'response' => array( 'code' => 200 ),
					'body'     => wp_json_encode( array( 'decision' => 'invalid_decision' ) ),
				);
			}
		);

		$result = $this->sut->send_event( 'cart_updated', array( 'session_id' => 'test-session' ) );

		$this->assertSame( ApiClient::DECISION_ALLOW, $result, 'Should fail open with allow decision' );
		$this->assertLogged(
			'error',
			'Invalid decision value "invalid_decision"',
			array(
				'source'   => 'woo-fraud-protection',
				'response' => array( 'decision' => 'invalid_decision' ),
			)
		);
	}

	/**
	 * @testdox Send Event should return allow decision from API.
	 */
	public function test_send_event_returns_allow_decision_from_api(): void {
		add_filter(
			'pre_http_request',
			function () {
				return array(
					'response' => array( 'code' => 200 ),
					'body'     => wp_json_encode(
						array(
							'fraud_event_id' => 123,
							'decision'       => 'allow',
							'risk_score'     => 10,
						)
					),
				);
			}
		);

		$result = $this->sut->send_event( 'cart_updated', array( 'session_id' => 'test-session' ) );

		$this->assertSame( ApiClient::DECISION_ALLOW, $result, 'Should return allow decision' );
		$this->assertLogged(
			'info',
			'Fraud decision received: allow',
			array(
				'source'   => 'woo-fraud-protection',
				'response' => array(
					'fraud_event_id' => 123,
					'decision'       => 'allow',
					'risk_score'     => 10,
				),
			)
		);
		$this->assertNoErrorLogged();
	}

	/**
	 * @testdox Send Event should return block decision from API.
	 */
	public function test_send_event_returns_block_decision_from_api(): void {
		add_filter(
			'pre_http_request',
			function () {
				return array(
					'response' => array( 'code' => 200 ),
					'body'     => wp_json_encode(
						array(
							'fraud_event_id' => 123,
							'decision'       => 'block',
							'risk_score'     => 95,
							'reason_tags'    => array( 'failures_per_ip' ),
						)
					),
				);
			}
		);

		$result = $this->sut->send_event( 'checkout_started', array( 'session_id' => 'test-session' ) );

		$this->assertSame( ApiClient::DECISION_BLOCK, $result, 'Should return block decision' );
		$this->assertLogged(
			'info',
			'Fraud decision received: block',
			array(
				'source'   => 'woo-fraud-protection',
				'response' => array(
					'fraud_event_id' => 123,
					'decision'       => 'block',
					'risk_score'     => 95,
					'reason_tags'    => array( 'failures_per_ip' ),
				),
			)
		);
		$this->assertNoErrorLogged();
	}

	/**
	 * @testdox Send Event should return allow when API returns challenge decision (challenge flow not yet implemented).
	 */
	public function test_send_event_returns_allow_when_challenge_decision_from_api(): void {
		add_filter(
			'pre_http_request',
			function () {
				return array(
					'response' => array( 'code' => 200 ),
					'body'     => wp_json_encode(
						array(
							'fraud_event_id' => 123,
							'decision'       => 'challenge',
							'risk_score'     => 65,
						)
					),
				);
			}
		);

		$result = $this->sut->send_event( 'checkout_started', array( 'session_id' => 'test-session' ) );

		$this->assertSame( ApiClient::DECISION_ALLOW, $result, 'Should fail open with allow until challenge flow is implemented' );
		$this->assertLogged( 'error', 'Invalid decision value "challenge"' );
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
					'body'     => wp_json_encode( array( 'decision' => 'allow' ) ),
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

		$this->sut->send_event( 'cart_updated', $session_data );

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
