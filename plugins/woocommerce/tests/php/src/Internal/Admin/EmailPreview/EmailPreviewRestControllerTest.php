<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\EmailPreview;

use Automattic\WooCommerce\Internal\Admin\EmailPreview\EmailPreview;
use Automattic\WooCommerce\Internal\Admin\EmailPreview\EmailPreviewRestController;
use PHPUnit\Framework\MockObject\MockObject;
use WC_REST_Unit_Test_Case;
use WP_REST_Request;

/**
 * EmailPreviewRestController API controller test.
 *
 * @class PaymentsRestController
 */
class EmailPreviewRestControllerTest extends WC_REST_Unit_Test_Case {
	/**
	 * Endpoint.
	 *
	 * @var string
	 */
	const ENDPOINT = '/wc-admin-email/settings/email';

	/**
	 * Email address.
	 *
	 * @var string
	 */
	const EMAIL = 'example@wordpress.com';

	/**
	 * Site title.
	 *
	 * @var string
	 */
	const SITE_TITLE = 'Test Blog';

	/**
	 * @var EmailPreviewRestController
	 */
	protected EmailPreviewRestController $controller;

	/**
	 * @var MockObject|EmailPreview
	 */
	protected $mock_service;

	/**
	 * The ID of the store admin user.
	 *
	 * @var int
	 */
	protected $store_admin_id;

	/**
	 * Set up test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->store_admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $this->store_admin_id );

		$this->mock_service = $this->getMockBuilder( EmailPreview::class )->getMock();

		$this->controller = new EmailPreviewRestController();
		$this->controller->register_routes();
	}

	/**
	 * Test sending email preview without required arguments
	 */
	public function test_send_preview_without_args() {
		$request  = $this->get_email_preview_request();
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'Missing parameter(s): type, email', $response->get_data()['message'] );

		$request  = $this->get_email_preview_request( EmailPreview::DEFAULT_EMAIL_TYPE );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'Missing parameter(s): email', $response->get_data()['message'] );

		$request  = $this->get_email_preview_request( null, self::EMAIL );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'Missing parameter(s): type', $response->get_data()['message'] );
	}

	/**
	 * Test sending email preview with invalid arguments
	 */
	public function test_send_preview_with_invalid_args() {
		$request  = $this->get_email_preview_request( 'non-existent-type', self::EMAIL );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'Invalid parameter(s): type', $response->get_data()['message'] );

		$request  = $this->get_email_preview_request( EmailPreview::DEFAULT_EMAIL_TYPE, 'invalid-email' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'Invalid parameter(s): email', $response->get_data()['message'] );
	}

	/**
	 * Test sending email preview by a user without the needed capabilities.
	 */
	public function test_send_preview_by_user_without_caps() {
		$filter_callback = fn() => array(
			'manage_woocommerce' => false,
		);
		add_filter( 'user_has_cap', $filter_callback );

		$request  = $this->get_email_preview_request( EmailPreview::DEFAULT_EMAIL_TYPE, self::EMAIL );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( rest_authorization_required_code(), $response->get_status() );

		remove_filter( 'user_has_cap', $filter_callback );
	}

	/**
	 * Test sending email preview with a successful sending.
	 */
	public function test_send_preview_success_response() {
		$request  = $this->get_email_preview_request( EmailPreview::DEFAULT_EMAIL_TYPE, self::EMAIL );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'Test email sent to ' . self::EMAIL . '.', $response->get_data()['message'] );
	}

	/**
	 * Test sending email preview with a failed sending.
	 */
	public function test_send_preview_error_response() {
		add_filter( 'woocommerce_mail_callback', array( $this, 'simulate_failed_sending' ), 10, 0 );

		$request  = $this->get_email_preview_request( EmailPreview::DEFAULT_EMAIL_TYPE, self::EMAIL );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 500, $response->get_status() );
		$this->assertEquals( 'Error sending test email. Please try again.', $response->get_data()['message'] );

		remove_filter( 'woocommerce_mail_callback', array( $this, 'simulate_failed_sending' ), 10 );
	}

	/**
	 * Helper method to simulate a failed email sending.
	 *
	 * @return callable
	 */
	public function simulate_failed_sending() {
		return function () {
			return false;
		};
	}

	/**
	 * Helper method to construct a request to send an email preview.
	 *
	 * @param string|null $type Email type to preview.
	 * @param string|null $email Email address to send the preview to.
	 * @return WP_REST_Request
	 */
	private function get_email_preview_request( ?string $type = null, ?string $email = null ) {
		$nonce   = wp_create_nonce( EmailPreviewRestController::NONCE_KEY );
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/send-preview' );
		$request->set_query_params( array( 'nonce' => $nonce ) );
		$params = array();
		if ( $type ) {
			$params['type'] = $type;
		}
		if ( $email ) {
			$params['email'] = $email;
		}
		$request->set_body_params( $params );
		return $request;
	}

	/**
	 * Test preview subject without required arguments
	 */
	public function test_preview_subject_without_args() {
		$request  = $this->get_preview_subject_request();
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'Missing parameter(s): type', $response->get_data()['message'] );
	}

	/**
	 * Test sending email preview with invalid arguments
	 */
	public function test_preview_subject_with_invalid_args() {
		$request  = $this->get_preview_subject_request( 'non-existent-type' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'Invalid parameter(s): type', $response->get_data()['message'] );
	}

	/**
	 * Test sending email preview by a user without the needed capabilities.
	 */
	public function test_preview_subject_by_user_without_caps() {
		$filter_callback = fn() => array(
			'manage_woocommerce' => false,
		);
		add_filter( 'user_has_cap', $filter_callback );

		$request  = $this->get_preview_subject_request( EmailPreview::DEFAULT_EMAIL_TYPE );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( rest_authorization_required_code(), $response->get_status() );

		remove_filter( 'user_has_cap', $filter_callback );
	}

	/**
	 * Test sending email preview with a successful sending.
	 */
	public function test_preview_subject_success_response() {
		$request  = $this->get_preview_subject_request( EmailPreview::DEFAULT_EMAIL_TYPE );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'Your ' . self::SITE_TITLE . ' order has been received!', $response->get_data()['subject'] );
	}

	/**
	 * Helper method to construct a request to get preview subject.
	 *
	 * @param string|null $type Email type to preview.
	 * @return WP_REST_Request
	 */
	private function get_preview_subject_request( ?string $type = null ) {
		$nonce   = wp_create_nonce( EmailPreviewRestController::NONCE_KEY );
		$request = new WP_REST_Request( 'GET', self::ENDPOINT . '/preview-subject' );
		$params  = array(
			'nonce' => $nonce,
		);
		if ( $type ) {
			$params['type'] = $type;
		}
		$request->set_query_params( $params );
		return $request;
	}

	/**
	 * Test saving transient without required arguments
	 */
	public function test_save_transient_without_args() {
		$request  = $this->get_save_transient_request();
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'Missing parameter(s): key, value', $response->get_data()['message'] );

		$request  = $this->get_save_transient_request( EmailPreview::get_email_style_setting_ids()[0] );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'Missing parameter(s): value', $response->get_data()['message'] );

		$request  = $this->get_save_transient_request( null, 'value' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'Missing parameter(s): key', $response->get_data()['message'] );
	}

	/**
	 * Test saving transient with invalid arguments
	 */
	public function test_save_transient_with_invalid_args() {
		$request  = $this->get_save_transient_request( 'non-allowed-key', 'value' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'Invalid parameter(s): key', $response->get_data()['message'] );
	}

	/**
	 * Test saving transient for an unregistered email fails
	 */
	public function test_save_transient_with_unregistered_email() {
		$keys = EmailPreview::get_email_content_setting_ids( 'unregistered_email_id' );
		foreach ( $keys as $key ) {
			$request  = $this->get_save_transient_request( $key, 'value' );
			$response = $this->server->dispatch( $request );
			$this->assertEquals( 400, $response->get_status() );
			$this->assertEquals( 'Invalid parameter(s): key', $response->get_data()['message'] );
		}
	}

	/**
	 * Test saving transient for registered email
	 */
	public function test_save_transient_with_registered_email() {
		$keys = EmailPreview::get_email_content_setting_ids( EmailPreview::DEFAULT_EMAIL_ID );
		foreach ( $keys as $key ) {
			$request  = $this->get_save_transient_request( $key, 'value' );
			$response = $this->server->dispatch( $request );
			$this->assertEquals( 200, $response->get_status() );
			$this->assertEquals( 'Transient saved for key ' . $key . '.', $response->get_data()['message'] );
			$this->assertEquals( 'value', get_transient( $key ) );
		}
	}

	/**
	 * Test saving transient by a user without the needed capabilities.
	 */
	public function test_save_transient_by_user_without_caps() {
		$filter_callback = fn() => array(
			'manage_woocommerce' => false,
		);
		add_filter( 'user_has_cap', $filter_callback );

		$request  = $this->get_save_transient_request( EmailPreview::get_email_style_setting_ids()[0], 'value' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( rest_authorization_required_code(), $response->get_status() );

		remove_filter( 'user_has_cap', $filter_callback );
	}

	/**
	 * Test saving transient with a successful saving.
	 */
	public function test_save_transient_success_response() {
		$request  = $this->get_save_transient_request( EmailPreview::get_email_style_setting_ids()[0], 'value' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'Transient saved for key ' . EmailPreview::get_email_style_setting_ids()[0] . '.', $response->get_data()['message'] );
	}

	/**
	 * Test saving transient with different sanitization logic.
	 */
	public function test_save_transient_sanitization_logic_with_newlines() {
		$textarea_key            = 'woocommerce_email_footer_text';
		$textarea_value          = "Line 1\nLine 2\nLine 3";
		$expected_textarea_value = "Line 1\nLine 2\nLine 3";

		$request  = $this->get_save_transient_request( $textarea_key, $textarea_value );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'Transient saved for key ' . $textarea_key . '.', $response->get_data()['message'] );
		$this->assertEquals( $expected_textarea_value, get_transient( $textarea_key ) );

		$text_key            = 'woocommerce_email_header_image';
		$text_value          = "Line 1\nLine 2\nLine 3";
		$expected_text_value = 'Line 1 Line 2 Line 3';

		$request  = $this->get_save_transient_request( $text_key, $text_value );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'Transient saved for key ' . $text_key . '.', $response->get_data()['message'] );
		$this->assertEquals( $expected_text_value, get_transient( $text_key ) );
	}

	/**
	 * Test saving transient with a failed saving.
	 */
	public function test_save_transient_error_response() {
		set_transient( EmailPreview::get_email_style_setting_ids()[0], 'value', HOUR_IN_SECONDS );

		// Saving the transient will fail because the transient key is already set to the same value.
		$request  = $this->get_save_transient_request( EmailPreview::get_email_style_setting_ids()[0], 'value' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 500, $response->get_status() );
		$this->assertEquals( 'Error saving transient. Please try again.', $response->get_data()['message'] );
	}

	/**
	 * Helper method to construct a request to save transient value.
	 *
	 * @param string|null $key Transient key.
	 * @param string|null $value Transient value.
	 * @return WP_REST_Request
	 */
	private function get_save_transient_request( ?string $key = null, ?string $value = null ) {
		$nonce   = wp_create_nonce( EmailPreviewRestController::NONCE_KEY );
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/save-transient' );
		$request->set_query_params( array( 'nonce' => $nonce ) );
		$params = array();
		if ( $key ) {
			$params['key'] = $key;
		}
		if ( $value ) {
			$params['value'] = $value;
		}
		$request->set_body_params( $params );
		return $request;
	}
}
