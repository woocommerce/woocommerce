<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\OrderWithdrawal;

use Automattic\WooCommerce\Internal\OrderWithdrawal\OrderWithdrawalFormProcessor;
use Automattic\WooCommerce\Internal\OrderWithdrawal\OrderWithdrawalFormState;
use Automattic\WooCommerce\Internal\OrderWithdrawal\OrderWithdrawalFormView;
use WC_Unit_Test_Case;

/**
 * Critical path tests for order withdrawal form handling.
 */
class OrderWithdrawalTest extends WC_Unit_Test_Case {

	private const FEATURE_OPTION      = 'woocommerce_feature_order_withdrawal_enabled';
	private const ENDPOINT_OPTION     = 'woocommerce_myaccount_order_withdrawal_endpoint';
	private const FLUSH_QUEUE_OPTION  = 'woocommerce_queue_flush_rewrite_rules';
	private const MISSING_OPTION_MARK = '__woocommerce_order_withdrawal_missing_option__';

	/**
	 * The System Under Test.
	 *
	 * @var OrderWithdrawalFormProcessor
	 */
	private $sut;

	/**
	 * Original POST data.
	 *
	 * @var array<string,mixed>
	 */
	private array $original_post = array();

	/**
	 * Original REQUEST_METHOD value.
	 *
	 * @var string|null
	 */
	private ?string $original_request_method = null;

	/**
	 * Whether REQUEST_METHOD existed before the test.
	 *
	 * @var bool
	 */
	private bool $had_request_method = false;

	/**
	 * Original WooCommerce session.
	 *
	 * @var \WC_Session|null
	 */
	private $original_session;

	/**
	 * Original feature option value.
	 *
	 * @var mixed
	 */
	private $original_feature_option;

	/**
	 * Original endpoint option value.
	 *
	 * @var mixed
	 */
	private $original_endpoint_option;

	/**
	 * Original flush queue option value.
	 *
	 * @var mixed
	 */
	private $original_flush_queue_option;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut                         = new OrderWithdrawalFormProcessor();
		$this->original_post               = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$this->had_request_method          = filter_has_var( INPUT_SERVER, 'REQUEST_METHOD' );
		$this->original_request_method     = $this->had_request_method ? filter_input( INPUT_SERVER, 'REQUEST_METHOD', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) : null;
		$this->original_session            = WC()->session;
		$this->original_feature_option     = get_option( self::FEATURE_OPTION, self::MISSING_OPTION_MARK );
		$this->original_endpoint_option    = get_option( self::ENDPOINT_OPTION, self::MISSING_OPTION_MARK );
		$this->original_flush_queue_option = get_option( self::FLUSH_QUEUE_OPTION, self::MISSING_OPTION_MARK );

		if ( ! WC()->session ) {
			WC()->initialize_session();
		}

		$_POST                     = array();
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$this->disable_feature();
		delete_option( self::ENDPOINT_OPTION );
		delete_option( self::FLUSH_QUEUE_OPTION );
		wc_clear_notices();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		$_POST = $this->original_post;

		if ( $this->had_request_method ) {
			$_SERVER['REQUEST_METHOD'] = (string) $this->original_request_method;
		} else {
			unset( $_SERVER['REQUEST_METHOD'] );
		}

		$this->restore_option( self::FEATURE_OPTION, $this->original_feature_option );
		$this->restore_option( self::ENDPOINT_OPTION, $this->original_endpoint_option );
		$this->restore_option( self::FLUSH_QUEUE_OPTION, $this->original_flush_queue_option );
		wc_clear_notices();
		WC()->session = $this->original_session;

		parent::tearDown();
	}

	/**
	 * @testdox Should return the empty form state for non-POST requests.
	 */
	public function test_process_current_request_returns_default_form_state_for_get_requests(): void {
		$state = $this->sut->process_current_request();

		$this->assertSame( 'form', $state->screen, 'GET requests should render the form screen.' );
		$this->assertEmpty( $state->errors, 'GET requests should not have validation errors.' );
		$this->assertSame( '', $state->data[ OrderWithdrawalFormProcessor::FIELD_FIRST_NAME ], 'Default first name should be empty.' );
		$this->assertSame( OrderWithdrawalFormProcessor::WITHDRAWAL_TYPE_FULL, $state->data[ OrderWithdrawalFormProcessor::FIELD_WITHDRAWAL_TYPE ], 'The default withdrawal type should be the full order.' );
	}

	/**
	 * @testdox Should move valid submissions to the requested next screen.
	 * @dataProvider provide_valid_submission_actions
	 *
	 * @param string $action          Submitted form action.
	 * @param string $expected_screen Expected screen.
	 */
	public function test_process_current_request_moves_valid_submissions_to_next_screen( string $action, string $expected_screen ): void {
		$this->prepare_post_request( $action );

		$state = $this->sut->process_current_request();

		$this->assertSame( $expected_screen, $state->screen, 'Valid submissions should advance to the requested screen.' );
		$this->assertEmpty( $state->errors, 'Valid submissions should not have validation errors.' );
		$this->assertSame( 'Jane', $state->data[ OrderWithdrawalFormProcessor::FIELD_FIRST_NAME ], 'Submitted first name should be retained.' );
		$this->assertSame( 'jane@example.test', $state->data[ OrderWithdrawalFormProcessor::FIELD_EMAIL ], 'Submitted email should be retained.' );
	}

	/**
	 * Data provider for {@see test_process_current_request_moves_valid_submissions_to_next_screen()}.
	 *
	 * @return array<string,array{0:string,1:string}>
	 */
	public function provide_valid_submission_actions(): array {
		return array(
			'review action'  => array( OrderWithdrawalFormProcessor::ACTION_REVIEW, 'review' ),
			'confirm action' => array( OrderWithdrawalFormProcessor::ACTION_CONFIRM, 'confirmation' ),
		);
	}

	/**
	 * @testdox Should reject specific-item withdrawals that do not list the items.
	 */
	public function test_process_current_request_requires_details_for_specific_item_withdrawals(): void {
		$this->prepare_post_request(
			OrderWithdrawalFormProcessor::ACTION_REVIEW,
			array(
				OrderWithdrawalFormProcessor::FIELD_WITHDRAWAL_TYPE    => OrderWithdrawalFormProcessor::WITHDRAWAL_TYPE_SPECIFIC,
				OrderWithdrawalFormProcessor::FIELD_ADDITIONAL_DETAILS => '',
			)
		);

		$state         = $this->sut->process_current_request();
		$error_notices = wc_get_notices( 'error' );

		$this->assertSame( 'form', $state->screen, 'Invalid submissions should stay on the form screen.' );
		$this->assertArrayHasKey( OrderWithdrawalFormProcessor::FIELD_ADDITIONAL_DETAILS, $state->errors, 'Specific-item withdrawals should require item details.' );
		$this->assertCount( 1, $error_notices, 'The validation failure should add one error notice.' );
		$this->assertSame( OrderWithdrawalFormProcessor::get_field_name( OrderWithdrawalFormProcessor::FIELD_ADDITIONAL_DETAILS ), $error_notices[0]['data']['id'] ?? null, 'The notice should identify the details field.' );
	}

	/**
	 * @testdox Should require the fields needed to identify the customer and order.
	 */
	public function test_process_current_request_requires_customer_and_order_fields(): void {
		$required_fields = array(
			OrderWithdrawalFormProcessor::FIELD_FIRST_NAME,
			OrderWithdrawalFormProcessor::FIELD_LAST_NAME,
			OrderWithdrawalFormProcessor::FIELD_EMAIL,
			OrderWithdrawalFormProcessor::FIELD_EMAIL_CONFIRMATION,
			OrderWithdrawalFormProcessor::FIELD_ORDER_NUMBER,
			OrderWithdrawalFormProcessor::FIELD_WITHDRAWAL_TYPE,
		);
		$field_overrides = array_fill_keys( $required_fields, '' );

		$this->prepare_post_request( OrderWithdrawalFormProcessor::ACTION_REVIEW, $field_overrides );

		$state      = $this->sut->process_current_request();
		$notice_ids = wp_list_pluck( wp_list_pluck( wc_get_notices( 'error' ), 'data' ), 'id' );

		$this->assertSame( 'form', $state->screen, 'Submissions missing required fields should stay on the form screen.' );
		$this->assertSame( $required_fields, array_keys( $state->errors ), 'The required customer and order fields should all fail validation.' );
		$this->assertSame( array_map( array( OrderWithdrawalFormProcessor::class, 'get_field_name' ), $required_fields ), $notice_ids, 'Each required-field error should add a notice tied to that field.' );
	}

	/**
	 * @testdox Should reject POST requests that fail nonce verification.
	 */
	public function test_process_current_request_rejects_invalid_nonce(): void {
		$this->prepare_post_request( OrderWithdrawalFormProcessor::ACTION_REVIEW, array(), 'not-a-valid-nonce' );

		$state         = $this->sut->process_current_request();
		$error_notices = wc_get_notices( 'error' );

		$this->assertSame( 'form', $state->screen, 'Invalid nonce submissions should stay on the form screen.' );
		$this->assertEmpty( $state->errors, 'Nonce failures should not run field validation.' );
		$this->assertSame( '', $state->data[ OrderWithdrawalFormProcessor::FIELD_FIRST_NAME ], 'Nonce failures should not retain posted data.' );
		$this->assertCount( 1, $error_notices, 'Nonce failures should add an error notice.' );
	}

	/**
	 * @testdox Should prepare the template data needed for the review screen.
	 */
	public function test_form_view_prepares_review_template_args(): void {
		$view  = new OrderWithdrawalFormView();
		$state = new OrderWithdrawalFormState( 'review', $this->get_valid_form_data(), array() );

		$args = $view->get_template_args( $state, 'https://example.test/account/withdraw-order/', 'https://example.test/shop/' );

		$hidden_fields = wp_list_pluck( $args['hidden_fields'], 'value', 'name' );
		$review_rows   = wp_list_pluck( $args['review_rows'], 'value', 'label' );

		$this->assertSame( 'review', $args['screen'], 'The view should expose the current screen.' );
		$this->assertArrayHasKey( OrderWithdrawalFormProcessor::FIELD_EMAIL, $args['fields'], 'The view should expose prepared form fields.' );
		$this->assertSame( 'jane@example.test', $hidden_fields[ OrderWithdrawalFormProcessor::get_field_name( OrderWithdrawalFormProcessor::FIELD_EMAIL ) ], 'Hidden fields should use posted field names.' );
		$this->assertSame( 'Jane Doe', $review_rows['Name'], 'The review rows should include the customer name.' );
		$this->assertSame( 'Specific items only', $review_rows['Withdrawing'], 'The review rows should include the withdrawal type label.' );
		$this->assertSame( 'Line item 1', $review_rows['Additional details'], 'The review rows should include additional details.' );
		$this->assertSame( 'https://example.test/account/withdraw-order/', $args['form_action_url'], 'The view should expose the form action URL.' );
	}

	/**
	 * Prepare a form POST request.
	 *
	 * @param string               $action          Submitted action.
	 * @param array<string,string> $field_overrides Field value overrides keyed by unprefixed field key.
	 * @param string|null          $nonce           Nonce value.
	 */
	private function prepare_post_request( string $action, array $field_overrides = array(), ?string $nonce = null ): void {
		$fields = array_merge( $this->get_valid_form_data(), $field_overrides );

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_POST                     = array(
			OrderWithdrawalFormProcessor::ACTION_FIELD => $action,
			OrderWithdrawalFormProcessor::NONCE_FIELD  => $nonce ?? wp_create_nonce( OrderWithdrawalFormProcessor::NONCE_ACTION ),
		);

		foreach ( $fields as $field_key => $value ) {
			$_POST[ OrderWithdrawalFormProcessor::get_field_name( $field_key ) ] = $value;
		}
	}

	/**
	 * Get valid order withdrawal form data.
	 *
	 * @return array<string,string>
	 */
	private function get_valid_form_data(): array {
		return array(
			OrderWithdrawalFormProcessor::FIELD_FIRST_NAME => 'Jane',
			OrderWithdrawalFormProcessor::FIELD_LAST_NAME  => 'Doe',
			OrderWithdrawalFormProcessor::FIELD_EMAIL      => 'jane@example.test',
			OrderWithdrawalFormProcessor::FIELD_EMAIL_CONFIRMATION => 'jane@example.test',
			OrderWithdrawalFormProcessor::FIELD_ORDER_NUMBER => '1001',
			OrderWithdrawalFormProcessor::FIELD_WITHDRAWAL_TYPE => OrderWithdrawalFormProcessor::WITHDRAWAL_TYPE_SPECIFIC,
			OrderWithdrawalFormProcessor::FIELD_ADDITIONAL_DETAILS => 'Line item 1',
		);
	}

	/**
	 * Disable the order withdrawal feature.
	 */
	private function disable_feature(): void {
		update_option( self::FEATURE_OPTION, 'no' );
	}

	/**
	 * Restore an option to its original state.
	 *
	 * @param string $option Option name.
	 * @param mixed  $value  Original value.
	 */
	private function restore_option( string $option, $value ): void {
		if ( self::MISSING_OPTION_MARK === $value ) {
			delete_option( $option );
			return;
		}

		update_option( $option, $value );
	}
}
