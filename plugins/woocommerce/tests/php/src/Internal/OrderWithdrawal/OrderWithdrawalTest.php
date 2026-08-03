<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\OrderWithdrawal;

use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Admin\Notes\Notes;
use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\OrderWithdrawal\OrderWithdrawalController;
use Automattic\WooCommerce\Internal\OrderWithdrawal\OrderWithdrawalFormProcessor;
use Automattic\WooCommerce\Internal\OrderWithdrawal\OrderWithdrawalFormState;
use Automattic\WooCommerce\Internal\OrderWithdrawal\OrderWithdrawalFormView;
use WC_Order;
use WC_Rate_Limiter;
use WC_Unit_Test_Case;

/**
 * Critical path tests for order withdrawal form handling.
 */
class OrderWithdrawalTest extends WC_Unit_Test_Case {

	private const FEATURE_OPTION                      = 'woocommerce_feature_order_withdrawal_enabled';
	private const ENDPOINT_OPTION                     = 'woocommerce_myaccount_order_withdrawal_endpoint';
	private const FLUSH_QUEUE_OPTION                  = 'woocommerce_queue_flush_rewrite_rules';
	private const MISSING_OPTION_MARK                 = '__woocommerce_order_withdrawal_missing_option__';
	private const ORDER_WITHDRAWAL_REQUESTED_META_KEY = '_order_withdrawal_requested';
	private const INBOX_NOTE_NAME_PREFIX              = 'wc-order-withdrawal-requested-';
	private const RATE_LIMIT_PREFIX                   = 'order_withdrawal_';

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
	 * Original REMOTE_ADDR value.
	 *
	 * @var string|null
	 */
	private ?string $original_remote_addr = null;

	/**
	 * Whether REMOTE_ADDR existed before the test.
	 *
	 * @var bool
	 */
	private bool $had_remote_addr = false;

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
	 * Created order IDs.
	 *
	 * @var int[]
	 */
	private array $created_order_ids = array();

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut                         = new OrderWithdrawalFormProcessor();
		$this->original_post               = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$this->had_request_method          = filter_has_var( INPUT_SERVER, 'REQUEST_METHOD' );
		$this->original_request_method     = $this->had_request_method ? filter_input( INPUT_SERVER, 'REQUEST_METHOD', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) : null;
		$this->had_remote_addr             = array_key_exists( 'REMOTE_ADDR', $_SERVER );
		$this->original_remote_addr        = $this->had_remote_addr ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : null;
		$this->original_session            = WC()->session;
		$this->original_feature_option     = get_option( self::FEATURE_OPTION, self::MISSING_OPTION_MARK );
		$this->original_endpoint_option    = get_option( self::ENDPOINT_OPTION, self::MISSING_OPTION_MARK );
		$this->original_flush_queue_option = get_option( self::FLUSH_QUEUE_OPTION, self::MISSING_OPTION_MARK );

		if ( ! WC()->session ) {
			WC()->initialize_session();
		}

		$_POST                     = array();
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_SERVER['REMOTE_ADDR']    = '203.0.113.10';
		$this->clear_order_withdrawal_rate_limits();
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

		if ( $this->had_remote_addr ) {
			$_SERVER['REMOTE_ADDR'] = (string) $this->original_remote_addr;
		} else {
			unset( $_SERVER['REMOTE_ADDR'] );
		}

		$this->restore_option( self::FEATURE_OPTION, $this->original_feature_option );
		$this->restore_option( self::ENDPOINT_OPTION, $this->original_endpoint_option );
		$this->restore_option( self::FLUSH_QUEUE_OPTION, $this->original_flush_queue_option );
		wc_clear_notices();
		$this->clear_order_withdrawal_rate_limits();
		$this->delete_created_orders();
		$this->delete_created_inbox_notes();
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
			'review action' => array( OrderWithdrawalFormProcessor::ACTION_REVIEW, 'review' ),
		);
	}

	/**
	 * @testdox Should add an order note and send emails when a confirmed submission matches an order exactly.
	 */
	public function test_process_current_request_adds_note_and_sends_emails_for_exact_order_match(): void {
		$order   = $this->create_order_for_form_data();
		$capture = $this->capture_wp_mail();

		try {
			$this->prepare_post_request(
				OrderWithdrawalFormProcessor::ACTION_CONFIRM,
				array( OrderWithdrawalFormProcessor::FIELD_ORDER_NUMBER => (string) $order->get_id() )
			);

			$state = $this->sut->process_current_request();

			$this->assertSame( 'confirmation', $state->screen, 'Matched confirm submissions should reach the confirmation screen.' );
			$this->assertCount( 2, $capture['captures'], 'The customer and merchant emails should both be sent.' );
			$this->assert_mail_sent_to( 'jane@example.test', $capture['captures'] );
			$this->assert_mail_sent_to( (string) get_option( 'admin_email' ), $capture['captures'] );
			$merchant_email = $this->get_captured_mail_to( (string) get_option( 'admin_email' ), $capture['captures'] );
			$this->assertStringContainsString( str_replace( '&', '&amp;', $order->get_edit_order_url() ), (string) $merchant_email['message'], 'The merchant email should link to the matched order.' );
			$this->assertStringContainsString( 'View matched order', (string) $merchant_email['message'], 'The merchant email should include clear link text for the matched order.' );
			$this->assertTrue( $this->order_has_note_containing( $order, 'Order withdrawal requested by Jane Doe (jane@example.test).' ), 'The matched order should receive a withdrawal note.' );
			$this->assertTrue( $this->order_has_note_containing( $order, 'Items requested for withdrawal: Line item 1' ), 'Specific-item details should be included in the order note.' );
			$this->assert_order_withdrawal_requested( $order );
		} finally {
			$capture['remove']();
		}
	}

	/**
	 * @testdox Should match orders by custom order number instead of assuming the order number is the ID.
	 */
	public function test_process_current_request_matches_custom_order_number(): void {
		$order               = $this->create_order_for_form_data();
		$custom_order_number = 'CUSTOM-1001';
		$capture             = $this->capture_wp_mail();
		$filter              = static function ( $order_number, $filtered_order ) use ( $order, $custom_order_number ) {
			if ( $filtered_order instanceof WC_Order && $order->get_id() === $filtered_order->get_id() ) {
				return $custom_order_number;
			}

			return $order_number;
		};

		add_filter( 'woocommerce_order_number', $filter, 10, 2 );

		try {
			$this->prepare_post_request(
				OrderWithdrawalFormProcessor::ACTION_CONFIRM,
				array( OrderWithdrawalFormProcessor::FIELD_ORDER_NUMBER => $custom_order_number )
			);

			$state = $this->sut->process_current_request();

			$this->assertSame( 'confirmation', $state->screen, 'Custom order number submissions should reach the confirmation screen.' );
			$this->assertCount( 2, $capture['captures'], 'The customer and merchant emails should both be sent.' );
			$this->assertTrue( $this->order_has_note_containing( $order, 'Order withdrawal requested by Jane Doe (jane@example.test).' ), 'The custom-number matched order should receive a withdrawal note.' );
			$this->assert_order_withdrawal_requested( $order );
		} finally {
			remove_filter( 'woocommerce_order_number', $filter, 10 );
			$capture['remove']();
		}
	}

	/**
	 * @testdox Should match normalized submitted order numbers only to the intended order.
	 */
	public function test_process_current_request_matches_normalized_order_number_to_intended_order(): void {
		$target_order = $this->create_order_for_form_data();
		$wrong_order  = $this->create_order_for_form_data();
		$capture      = $this->capture_wp_mail();

		try {
			$this->prepare_post_request(
				OrderWithdrawalFormProcessor::ACTION_CONFIRM,
				array( OrderWithdrawalFormProcessor::FIELD_ORDER_NUMBER => '#' . $target_order->get_id() )
			);

			$state          = $this->sut->process_current_request();
			$merchant_email = $this->get_captured_mail_to( (string) get_option( 'admin_email' ), $capture['captures'] );

			$this->assertSame( 'confirmation', $state->screen, 'Normalized order number submissions should reach the confirmation screen.' );
			$this->assertCount( 2, $capture['captures'], 'The customer and merchant emails should both be sent.' );
			$this->assertStringContainsString( str_replace( '&', '&amp;', $target_order->get_edit_order_url() ), (string) $merchant_email['message'], 'The merchant email should link to the intended order.' );
			$this->assertStringNotContainsString( str_replace( '&', '&amp;', $wrong_order->get_edit_order_url() ), (string) $merchant_email['message'], 'The merchant email should not link to the wrong order.' );
			$this->assertTrue( $this->order_has_note_containing( $target_order, 'Order withdrawal requested by Jane Doe (jane@example.test).' ), 'The intended order should receive a withdrawal note.' );
			$this->assertFalse( $this->order_has_note_containing( $wrong_order, 'Order withdrawal requested' ), 'The wrong order should not receive a withdrawal note.' );
			$this->assert_order_withdrawal_requested( $target_order );
		} finally {
			$capture['remove']();
		}
	}

	/**
	 * @testdox Should not match an order that shares the email but has a different billing name.
	 */
	public function test_process_current_request_matches_only_order_with_same_email_and_billing_name(): void {
		$target_order         = $this->create_order_for_form_data();
		$different_name_order = $this->create_order_for_form_data(
			array(
				OrderWithdrawalFormProcessor::FIELD_FIRST_NAME => 'Janet',
				OrderWithdrawalFormProcessor::FIELD_LAST_NAME  => 'Smith',
			)
		);
		$custom_order_number  = 'CUSTOM-1001';
		$capture              = $this->capture_wp_mail();
		$filter               = static function ( $order_number, $filtered_order ) use ( $target_order, $different_name_order, $custom_order_number ) {
			if (
				$filtered_order instanceof WC_Order
				&& in_array( $filtered_order->get_id(), array( $target_order->get_id(), $different_name_order->get_id() ), true )
			) {
				return $custom_order_number;
			}

			return $order_number;
		};

		add_filter( 'woocommerce_order_number', $filter, 10, 2 );

		try {
			$this->prepare_post_request(
				OrderWithdrawalFormProcessor::ACTION_CONFIRM,
				array( OrderWithdrawalFormProcessor::FIELD_ORDER_NUMBER => $custom_order_number )
			);

			$state          = $this->sut->process_current_request();
			$merchant_email = $this->get_captured_mail_to( (string) get_option( 'admin_email' ), $capture['captures'] );

			$this->assertSame( 'confirmation', $state->screen, 'Matching submissions should reach the confirmation screen.' );
			$this->assertCount( 2, $capture['captures'], 'The customer and merchant emails should both be sent.' );
			$this->assertStringContainsString( str_replace( '&', '&amp;', $target_order->get_edit_order_url() ), (string) $merchant_email['message'], 'The merchant email should link to the intended order.' );
			$this->assertStringNotContainsString( str_replace( '&', '&amp;', $different_name_order->get_edit_order_url() ), (string) $merchant_email['message'], 'The merchant email should not link to the order with a different billing name.' );
			$this->assertTrue( $this->order_has_note_containing( $target_order, 'Order withdrawal requested by Jane Doe (jane@example.test).' ), 'The intended order should receive a withdrawal note.' );
			$this->assertFalse( $this->order_has_note_containing( $different_name_order, 'Order withdrawal requested' ), 'The order with a different billing name should not receive a withdrawal note.' );
			$this->assert_order_withdrawal_requested( $target_order );
		} finally {
			remove_filter( 'woocommerce_order_number', $filter, 10 );
			$capture['remove']();
		}
	}

	/**
	 * @testdox Should reject duplicate withdrawal requests for an already flagged matched order.
	 */
	public function test_process_current_request_rejects_duplicate_withdrawal_for_matched_order(): void {
		$order = $this->create_order_for_form_data();
		$order->update_meta_data( self::ORDER_WITHDRAWAL_REQUESTED_META_KEY, 'yes' );
		$order->save_meta_data();
		$capture = $this->capture_wp_mail();

		try {
			$this->prepare_post_request(
				OrderWithdrawalFormProcessor::ACTION_CONFIRM,
				array( OrderWithdrawalFormProcessor::FIELD_ORDER_NUMBER => (string) $order->get_id() )
			);

			$state         = $this->sut->process_current_request();
			$error_notices = wc_get_notices( 'error' );

			$this->assertSame( 'review', $state->screen, 'Duplicate matched submissions should return to the review screen.' );
			$this->assertCount( 1, $error_notices, 'Duplicate matched submissions should add one error notice.' );
			$this->assertStringContainsString( 'already been submitted for this order', $error_notices[0]['notice'], 'The notice should explain that the order already has a withdrawal request.' );
			$this->assertCount( 0, $capture['captures'], 'Duplicate matched submissions should not send notification emails.' );
			$this->assertFalse( $this->order_has_note_containing( $order, 'Order withdrawal requested' ), 'Duplicate matched submissions should not add another order note.' );
			$this->assertCount( 0, $this->get_created_inbox_note_ids(), 'Duplicate matched submissions should not create merchant inbox notifications.' );

			wc_clear_notices();
			$this->prepare_post_request(
				OrderWithdrawalFormProcessor::ACTION_CONFIRM,
				array( OrderWithdrawalFormProcessor::FIELD_ORDER_NUMBER => (string) $order->get_id() )
			);

			$second_state         = $this->sut->process_current_request();
			$second_error_notices = wc_get_notices( 'error' );

			$this->assertSame( 'review', $second_state->screen, 'Duplicate matched submissions should not leave behind a rate limit.' );
			$this->assertCount( 1, $second_error_notices, 'The second duplicate submission should add only the duplicate-order error notice.' );
			$this->assertStringContainsString( 'already been submitted for this order', $second_error_notices[0]['notice'], 'The released rate limit should allow duplicate-order validation to run again.' );
		} finally {
			$capture['remove']();
		}
	}

	/**
	 * @testdox Should submit and send emails when adding the matched order note fails.
	 */
	public function test_process_current_request_treats_order_note_failure_as_best_effort(): void {
		$order           = $this->create_order_for_form_data();
		$capture         = $this->capture_wp_mail();
		$fail_order_note = static function ( $commentdata ) {
			if ( is_array( $commentdata ) && 'order_note' === ( $commentdata['comment_type'] ?? '' ) ) {
				throw new \RuntimeException( 'Order note insert failed.' );
			}

			return $commentdata;
		};

		add_filter( 'preprocess_comment', $fail_order_note, 10, 1 );

		try {
			$this->prepare_post_request(
				OrderWithdrawalFormProcessor::ACTION_CONFIRM,
				array( OrderWithdrawalFormProcessor::FIELD_ORDER_NUMBER => (string) $order->get_id() )
			);

			$state = $this->sut->process_current_request();

			$this->assertSame( 'confirmation', $state->screen, 'Order note failures should not block submission.' );
			$this->assertCount( 2, $capture['captures'], 'The customer and merchant emails should still be sent.' );
			$this->assert_mail_sent_to( 'jane@example.test', $capture['captures'] );
			$this->assert_mail_sent_to( (string) get_option( 'admin_email' ), $capture['captures'] );
			$this->assert_order_withdrawal_requested( $order );
		} finally {
			remove_filter( 'preprocess_comment', $fail_order_note, 10 );
			$capture['remove']();
		}
	}

	/**
	 * @testdox Should send emails without adding a note when no exact order match is found.
	 */
	public function test_process_current_request_sends_emails_without_note_when_order_does_not_match(): void {
		$order   = $this->create_order_for_form_data(
			array(
				OrderWithdrawalFormProcessor::FIELD_EMAIL => 'different@example.test',
				OrderWithdrawalFormProcessor::FIELD_EMAIL_CONFIRMATION => 'different@example.test',
			)
		);
		$capture = $this->capture_wp_mail();

		try {
			$this->prepare_post_request(
				OrderWithdrawalFormProcessor::ACTION_CONFIRM,
				array( OrderWithdrawalFormProcessor::FIELD_ORDER_NUMBER => (string) $order->get_id() )
			);

			$state = $this->sut->process_current_request();

			$this->assertSame( 'confirmation', $state->screen, 'Unmatched confirm submissions should still reach the confirmation screen.' );
			$this->assertCount( 2, $capture['captures'], 'The customer and merchant emails should both be sent even without a match.' );
			$this->assert_mail_sent_to( 'jane@example.test', $capture['captures'] );
			$this->assert_mail_sent_to( (string) get_option( 'admin_email' ), $capture['captures'] );
			$this->assertFalse( $this->order_has_note_containing( $order, 'Order withdrawal requested' ), 'An order note should not be added when the identifying data does not all match.' );
		} finally {
			$capture['remove']();
		}
	}

	/**
	 * @testdox Should add a merchant inbox notification with a view order action when a confirmed submission matches an order.
	 */
	public function test_process_current_request_adds_inbox_note_with_order_action_for_exact_order_match(): void {
		$order   = $this->create_order_for_form_data();
		$capture = $this->capture_wp_mail();

		try {
			$this->prepare_post_request(
				OrderWithdrawalFormProcessor::ACTION_CONFIRM,
				array( OrderWithdrawalFormProcessor::FIELD_ORDER_NUMBER => (string) $order->get_id() )
			);

			$state    = $this->sut->process_current_request();
			$note_ids = $this->get_created_inbox_note_ids();

			$this->assertSame( 'confirmation', $state->screen, 'Matched confirm submissions should reach the confirmation screen.' );
			$this->assertCount( 1, $note_ids, 'A matched submission should create one merchant inbox notification.' );

			$note = Notes::get_note( $note_ids[0] );

			$this->assertInstanceOf( Note::class, $note, 'The merchant inbox notification should be readable.' );
			$this->assertSame( Note::E_WC_ADMIN_NOTE_INFORMATIONAL, $note->get_type(), 'The inbox notification should be informational.' );
			$this->assertSame( Note::E_WC_ADMIN_NOTE_UNACTIONED, $note->get_status(), 'The inbox notification should start unactioned.' );
			$this->assertSame( 'Withdraw Order Request', $note->get_title(), 'The inbox notification should have the expected title.' );
			$this->assertStringContainsString( (string) $order->get_order_number(), $note->get_content(), 'The inbox notification should include the order number.' );
			$this->assertStringContainsString( 'Review the matched order to confirm the request details.', $note->get_content(), 'The inbox notification should direct merchants to the matched order.' );
			$this->assertStringNotContainsString( 'Jane Doe', $note->get_content(), 'The inbox notification should not include the customer name.' );
			$this->assertStringNotContainsString( 'jane@example.test', $note->get_content(), 'The inbox notification should not include the customer email address.' );
			$this->assertStringNotContainsString( 'Line item 1', $note->get_content(), 'The inbox notification should not include free-form withdrawal details.' );

			$actions = $note->get_actions();

			$this->assertCount( 1, $actions, 'The inbox notification should have one action.' );
			$this->assertSame( 'view-order', $actions[0]->name, 'The inbox notification action should be the view order action.' );
			$this->assertSame( $order->get_edit_order_url(), $actions[0]->query, 'The inbox notification action should link to the matched order.' );
		} finally {
			$capture['remove']();
		}
	}

	/**
	 * @testdox Should skip the merchant inbox notification when no order matches.
	 */
	public function test_process_current_request_skips_inbox_note_when_order_does_not_match(): void {
		$order   = $this->create_order_for_form_data(
			array(
				OrderWithdrawalFormProcessor::FIELD_EMAIL => 'different@example.test',
				OrderWithdrawalFormProcessor::FIELD_EMAIL_CONFIRMATION => 'different@example.test',
			)
		);
		$capture = $this->capture_wp_mail();

		try {
			$this->prepare_post_request(
				OrderWithdrawalFormProcessor::ACTION_CONFIRM,
				array( OrderWithdrawalFormProcessor::FIELD_ORDER_NUMBER => (string) $order->get_id() )
			);

			$state    = $this->sut->process_current_request();
			$note_ids = $this->get_created_inbox_note_ids();

			$this->assertSame( 'confirmation', $state->screen, 'Unmatched confirm submissions should still reach the confirmation screen.' );
			$this->assertCount( 0, $note_ids, 'An unmatched submission should not create a merchant inbox notification.' );
		} finally {
			$capture['remove']();
		}
	}

	/**
	 * @testdox Should delete a matched merchant inbox notification when its order is deleted.
	 */
	public function test_delete_order_withdrawal_inbox_note_for_order_deletes_matched_inbox_note(): void {
		$order   = $this->create_order_for_form_data();
		$capture = $this->capture_wp_mail();
		add_action( 'woocommerce_before_delete_order', array( $this->sut, 'delete_order_withdrawal_inbox_note_for_order' ), 10, 1 );

		try {
			$this->prepare_post_request(
				OrderWithdrawalFormProcessor::ACTION_CONFIRM,
				array( OrderWithdrawalFormProcessor::FIELD_ORDER_NUMBER => (string) $order->get_id() )
			);

			$this->sut->process_current_request();

			$note_ids = $this->get_created_inbox_note_ids();

			$this->assertCount( 1, $note_ids, 'A matched submission should create one merchant inbox notification.' );

			$order->delete( true );

			$this->assertCount( 0, $this->get_created_inbox_note_ids(), 'Deleting the order should remove the associated merchant inbox notification.' );
		} finally {
			remove_action( 'woocommerce_before_delete_order', array( $this->sut, 'delete_order_withdrawal_inbox_note_for_order' ), 10 );
			$capture['remove']();
		}
	}

	/**
	 * @testdox Should register cleanup hooks for HPOS and legacy order deletion.
	 */
	public function test_controller_registers_order_deletion_cleanup_hooks(): void {
		$controller = new OrderWithdrawalController();
		$controller->init( $this->sut, new OrderWithdrawalFormView() );

		try {
			$controller->register();

			$this->assertNotFalse( has_action( 'woocommerce_before_delete_order', array( $this->sut, 'delete_order_withdrawal_inbox_note_for_order' ) ) );
			$this->assertNotFalse( has_action( 'before_delete_post', array( $this->sut, 'delete_order_withdrawal_inbox_note_for_order' ) ) );
		} finally {
			remove_action( FeaturesController::FEATURE_ENABLED_CHANGED_ACTION, array( $controller, 'maybe_flush_rewrite_rules' ), 10 );
			remove_filter( 'woocommerce_get_query_vars', array( $controller, 'add_query_var' ), 10 );
			remove_filter( 'woocommerce_endpoint_order-withdrawal_title', array( $controller, 'get_endpoint_title' ), 10 );
			remove_filter( 'woocommerce_settings_pages', array( $controller, 'add_endpoint_setting' ), 10 );
			remove_action( 'woocommerce_account_order-withdrawal_endpoint', array( $controller, 'render_view' ), 10 );
			remove_action( 'woocommerce_before_delete_order', array( $this->sut, 'delete_order_withdrawal_inbox_note_for_order' ), 10 );
			remove_action( 'before_delete_post', array( $this->sut, 'delete_order_withdrawal_inbox_note_for_order' ), 10 );
			remove_action( 'woocommerce_privacy_remove_order_personal_data', array( $this->sut, 'delete_order_withdrawal_inbox_note_for_order' ), 10 );
		}
	}

	/**
	 * @testdox Should keep the user on review with an error notice when notification emails fail.
	 */
	public function test_process_current_request_surfaces_error_when_emails_fail(): void {
		$capture = $this->capture_wp_mail( false );

		try {
			$this->prepare_post_request(
				OrderWithdrawalFormProcessor::ACTION_CONFIRM,
				array( OrderWithdrawalFormProcessor::FIELD_ORDER_NUMBER => '999999999' )
			);

			$state         = $this->sut->process_current_request();
			$error_notices = wc_get_notices( 'error' );

			$this->assertSame( 'review', $state->screen, 'Email failures should keep the submitted details on the review screen.' );
			$this->assertCount( 2, $capture['captures'], 'The processor should attempt both notification emails before surfacing the failure.' );
			$this->assertNotEmpty( $error_notices, 'Email failures should add an error notice.' );
			$this->assertStringContainsString( 'We could not submit your withdrawal request.', $error_notices[0]['notice'], 'The error notice should tell the user the submission did not complete.' );
			$this->assertCount( 0, $this->get_created_inbox_note_ids(), 'Email failures should not create merchant inbox notifications.' );

			wc_clear_notices();
			$this->prepare_post_request(
				OrderWithdrawalFormProcessor::ACTION_CONFIRM,
				array( OrderWithdrawalFormProcessor::FIELD_ORDER_NUMBER => '999999999' )
			);

			$second_state         = $this->sut->process_current_request();
			$second_error_notices = wc_get_notices( 'error' );

			$this->assertSame( 'review', $second_state->screen, 'Email failures should not leave behind a rate limit.' );
			$this->assertCount( 4, $capture['captures'], 'The second failed submission should attempt notification emails again.' );
			$this->assertNotEmpty( $second_error_notices, 'The second email failure should add an error notice.' );
			$this->assertStringContainsString( 'We could not submit your withdrawal request.', $second_error_notices[0]['notice'], 'The released rate limit should allow email delivery to be attempted again.' );
		} finally {
			$capture['remove']();
		}
	}

	/**
	 * @testdox Should rate limit confirmed submissions by IP address before sending emails.
	 */
	public function test_process_current_request_rate_limits_confirmed_submissions_by_ip_address(): void {
		$capture = $this->capture_wp_mail();

		try {
			$this->prepare_post_request(
				OrderWithdrawalFormProcessor::ACTION_CONFIRM,
				array( OrderWithdrawalFormProcessor::FIELD_ORDER_NUMBER => '999999998' )
			);

			$first_state = $this->sut->process_current_request();

			$this->prepare_post_request(
				OrderWithdrawalFormProcessor::ACTION_CONFIRM,
				array(
					OrderWithdrawalFormProcessor::FIELD_EMAIL              => 'another@example.test',
					OrderWithdrawalFormProcessor::FIELD_EMAIL_CONFIRMATION => 'another@example.test',
					OrderWithdrawalFormProcessor::FIELD_ORDER_NUMBER       => '999999999',
				)
			);

			$second_state  = $this->sut->process_current_request();
			$error_notices = wc_get_notices( 'error' );

			$this->assertSame( 'confirmation', $first_state->screen, 'The first confirmed submission should complete.' );
			$this->assertSame( 'review', $second_state->screen, 'A repeated submission from the same IP should return to the review screen.' );
			$this->assertCount( 2, $capture['captures'], 'The rate-limited submission should not send additional notification emails.' );
			$this->assertNotEmpty( $error_notices, 'Rate-limited submissions should add an error notice.' );
			$this->assertStringContainsString( 'Please wait before submitting another withdrawal request.', $error_notices[0]['notice'], 'The notice should ask the customer to wait.' );
		} finally {
			$capture['remove']();
		}
	}

	/**
	 * @testdox Should rate limit confirmed submissions by email before sending emails.
	 */
	public function test_process_current_request_rate_limits_confirmed_submissions_by_email(): void {
		$capture = $this->capture_wp_mail();

		try {
			$this->prepare_post_request(
				OrderWithdrawalFormProcessor::ACTION_CONFIRM,
				array( OrderWithdrawalFormProcessor::FIELD_ORDER_NUMBER => '999999998' )
			);

			$first_state = $this->sut->process_current_request();

			$_SERVER['REMOTE_ADDR'] = '203.0.113.11';
			$this->prepare_post_request(
				OrderWithdrawalFormProcessor::ACTION_CONFIRM,
				array( OrderWithdrawalFormProcessor::FIELD_ORDER_NUMBER => '999999999' )
			);

			$second_state  = $this->sut->process_current_request();
			$error_notices = wc_get_notices( 'error' );

			$this->assertSame( 'confirmation', $first_state->screen, 'The first confirmed submission should complete.' );
			$this->assertSame( 'review', $second_state->screen, 'A repeated submission for the same email should return to the review screen.' );
			$this->assertCount( 2, $capture['captures'], 'The rate-limited submission should not send additional notification emails.' );
			$this->assertNotEmpty( $error_notices, 'Rate-limited submissions should add an error notice.' );
			$this->assertStringContainsString( 'Please wait before submitting another withdrawal request.', $error_notices[0]['notice'], 'The notice should ask the customer to wait.' );
		} finally {
			$capture['remove']();
		}
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
	 * Create an order from the default valid form data.
	 *
	 * @param array<string,string> $field_overrides Field value overrides keyed by unprefixed field key.
	 */
	private function create_order_for_form_data( array $field_overrides = array() ): WC_Order {
		$data  = array_merge( $this->get_valid_form_data(), $field_overrides );
		$order = wc_create_order();

		if ( ! $order instanceof WC_Order ) {
			$this->fail( 'Expected wc_create_order() to create a WC_Order instance.' );
		}

		$order->set_billing_first_name( $data[ OrderWithdrawalFormProcessor::FIELD_FIRST_NAME ] );
		$order->set_billing_last_name( $data[ OrderWithdrawalFormProcessor::FIELD_LAST_NAME ] );
		$order->set_billing_email( $data[ OrderWithdrawalFormProcessor::FIELD_EMAIL ] );
		$order->save();

		$this->created_order_ids[] = $order->get_id();

		return $order;
	}

	/**
	 * Capture wp_mail() calls without sending real email.
	 *
	 * @param bool $send_result Value returned to wp_mail().
	 * @return array{captures: array<int,array<string,mixed>>, remove: callable}
	 */
	private function capture_wp_mail( bool $send_result = true ): array {
		$captures = array();

		$capture = static function ( $short_circuit, $atts ) use ( &$captures, $send_result ) {
			unset( $short_circuit );
			$captures[] = is_array( $atts ) ? $atts : array();

			return $send_result;
		};

		add_filter( 'pre_wp_mail', $capture, 10, 2 );

		$remove = static function () use ( $capture ) {
			remove_filter( 'pre_wp_mail', $capture, 10 );
		};

		return array(
			'captures' => &$captures,
			'remove'   => $remove,
		);
	}

	/**
	 * Assert an email was sent to a recipient.
	 *
	 * @param string                         $recipient Recipient email.
	 * @param array<int,array<string,mixed>> $captures   Captured email arguments.
	 */
	private function assert_mail_sent_to( string $recipient, array $captures ): void {
		$recipients = array();

		foreach ( $captures as $mail ) {
			$to = $mail['to'] ?? '';

			if ( is_array( $to ) ) {
				$recipients = array_merge( $recipients, $to );
			} else {
				$recipients[] = (string) $to;
			}
		}

		$this->assertContains( $recipient, $recipients, sprintf( 'Expected an email to be sent to %s.', $recipient ) );
	}

	/**
	 * Get a captured email sent to a recipient.
	 *
	 * @param string                         $recipient Recipient email.
	 * @param array<int,array<string,mixed>> $captures   Captured email arguments.
	 * @return array<string,mixed>
	 */
	private function get_captured_mail_to( string $recipient, array $captures ): array {
		foreach ( $captures as $mail ) {
			$to         = $mail['to'] ?? '';
			$recipients = is_array( $to ) ? $to : array( (string) $to );

			if ( in_array( $recipient, $recipients, true ) ) {
				return $mail;
			}
		}

		$this->fail( sprintf( 'Expected an email to be sent to %s.', $recipient ) );
	}

	/**
	 * Whether an order has a note containing specific text.
	 *
	 * @param WC_Order $order  Order.
	 * @param string   $needle Note content to search for.
	 */
	private function order_has_note_containing( WC_Order $order, string $needle ): bool {
		$notes = wc_get_order_notes( array( 'order_id' => $order->get_id() ) );

		foreach ( $notes as $note ) {
			if ( false !== strpos( (string) $note->content, $needle ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Assert that an order has been flagged as having a withdrawal request.
	 *
	 * @param WC_Order $order Order.
	 */
	private function assert_order_withdrawal_requested( WC_Order $order ): void {
		$updated_order = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $updated_order, 'The order should still exist.' );
		$this->assertSame( 'yes', $updated_order->get_meta( self::ORDER_WITHDRAWAL_REQUESTED_META_KEY, true, 'edit' ), 'The matched order should be flagged as having a withdrawal request.' );
	}

	/**
	 * Get the IDs of order withdrawal inbox notes created during a test.
	 *
	 * @return int[]
	 */
	private function get_created_inbox_note_ids(): array {
		global $wpdb;

		$note_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT note_id FROM {$wpdb->prefix}wc_admin_notes WHERE name LIKE %s",
				$wpdb->esc_like( self::INBOX_NOTE_NAME_PREFIX ) . '%'
			)
		);

		return array_map( 'intval', (array) $note_ids );
	}

	/**
	 * Delete inbox notes created during a test.
	 */
	private function delete_created_inbox_notes(): void {
		foreach ( $this->get_created_inbox_note_ids() as $note_id ) {
			$note = Notes::get_note( $note_id );

			if ( $note instanceof Note ) {
				$note->delete();
			}
		}
	}

	/**
	 * Delete orders created during a test.
	 */
	private function delete_created_orders(): void {
		foreach ( $this->created_order_ids as $order_id ) {
			$order = wc_get_order( $order_id );

			if ( $order instanceof WC_Order ) {
				$order->delete( true );
			}
		}

		$this->created_order_ids = array();
	}

	/**
	 * Clear order withdrawal rate limits created during tests.
	 */
	private function clear_order_withdrawal_rate_limits(): void {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}wc_rate_limits WHERE rate_limit_key LIKE %s",
				$wpdb->esc_like( self::RATE_LIMIT_PREFIX ) . '%'
			)
		);

		\WC_Cache_Helper::invalidate_cache_group( WC_Rate_Limiter::CACHE_GROUP );
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
