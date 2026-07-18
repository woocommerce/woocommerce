<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Utilities;

use Automattic\WooCommerce\StoreApi\Utilities\PaymentUtils;

/**
 * Tests for the PaymentUtils class.
 */
class PaymentUtilsTest extends \WC_Unit_Test_Case {

	/**
	 * The current user before each test.
	 *
	 * @var int
	 */
	private $original_user_id;

	/**
	 * The registered payment gateways before each test.
	 *
	 * @var array
	 */
	private $original_payment_gateways;

	/**
	 * The saved payment token fixture.
	 *
	 * @var PaymentUtilsTestToken
	 */
	private $token;

	/**
	 * The payment token class filter callback.
	 *
	 * @var callable|null
	 */
	private $payment_token_class_filter;

	/**
	 * The payment method probe callback.
	 *
	 * @var callable|null
	 */
	private $payment_method_probe;

	/**
	 * The throwing payment method callback.
	 *
	 * @var callable|null
	 */
	private $throwing_payment_method_callback;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->original_user_id                 = get_current_user_id();
		$this->original_payment_gateways        = WC()->payment_gateways()->payment_gateways;
		$this->payment_method_probe             = null;
		$this->throwing_payment_method_callback = null;
		$this->payment_token_class_filter       = static function ( string $token_class, string $token_type ): string {
			return 'payment_utils_test' === $token_type ? PaymentUtilsTestToken::class : $token_class;
		};

		add_filter( 'woocommerce_payment_token_class', $this->payment_token_class_filter, 10, 2 );

		$user_id = self::factory()->user->create();
		wp_set_current_user( $user_id );

		$gateway                                   = new class() extends \WC_Payment_Gateway {
			/**
			 * Set up the generic test gateway.
			 */
			public function __construct() {
				$this->id      = 'payment_utils_test_gateway';
				$this->enabled = 'yes';
			}
		};
		$payment_gateways                          = $this->original_payment_gateways;
		$payment_gateways[]                        = $gateway;
		WC()->payment_gateways()->payment_gateways = $payment_gateways;

		$this->token = new PaymentUtilsTestToken();
		$this->token->set_token( 'evergreen-wallet-2468' );
		$this->token->set_gateway_id( 'payment_utils_test_gateway' );
		$this->token->set_user_id( $user_id );
		$this->token->set_default( true );
		$this->token->save();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		try {
			if ( null !== $this->payment_method_probe ) {
				remove_filter( 'woocommerce_payment_methods_list_item', $this->payment_method_probe, 20 );
			}
			if ( null !== $this->throwing_payment_method_callback ) {
				remove_filter( 'woocommerce_payment_methods_list_item', $this->throwing_payment_method_callback, 20 );
			}

			remove_filter( 'woocommerce_payment_methods_list_item', array( PaymentUtils::class, 'include_token_id_with_payment_methods' ), 10 );
			remove_filter( 'woocommerce_payment_methods_list_item', array( PaymentUtils::class, 'prepare_payment_method_for_checkout' ), 10 );

			if ( isset( $this->token ) && $this->token->get_id() ) {
				$this->token->delete( true );
			}
		} finally {
			WC()->payment_gateways()->payment_gateways = $this->original_payment_gateways;
			wp_set_current_user( $this->original_user_id );

			if ( null !== $this->payment_token_class_filter ) {
				remove_filter( 'woocommerce_payment_token_class', $this->payment_token_class_filter, 10 );
			}

			parent::tearDown();
		}
	}

	/**
	 * @testdox Should add the token ID and normalize card brands without changing account-only fields.
	 */
	public function test_include_token_id_with_payment_methods_preserves_existing_behavior(): void {
		$list_item = array(
			'method'  => array(
				'gateway' => 'payment_utils_test_gateway',
				'brand'   => 'visa',
			),
			'actions' => array(
				'delete' => array(
					'url'  => 'https://example.com/delete',
					'name' => 'Delete',
				),
			),
		);

		$result = PaymentUtils::include_token_id_with_payment_methods( $list_item, $this->token );

		$this->assertSame( $this->token->get_id(), $result['tokenId'], 'The token ID should be available to Checkout.' );
		$this->assertSame( 'Visa', $result['method']['brand'], 'The card brand should use its normalized label.' );
		$this->assertSame( $list_item['actions'], $result['actions'], 'The existing callback should preserve account actions.' );
		$this->assertArrayNotHasKey( 'display_name', $result, 'The existing callback should not add a token display name.' );
	}

	/**
	 * @testdox Should prepare saved payment methods for Checkout without changing later My Account lists.
	 */
	public function test_get_saved_payment_methods_prepares_checkout_items_without_leaking_filter_state(): void {
		$probe_saw_actions          = false;
		$this->payment_method_probe = static function ( array $list_item ) use ( &$probe_saw_actions ): array {
			$probe_saw_actions = array_key_exists( 'actions', $list_item );
			return $list_item;
		};
		add_filter( 'woocommerce_payment_methods_list_item', $this->payment_method_probe, 20, 2 );

		try {
			$checkout_payment_methods = PaymentUtils::get_saved_payment_methods();
		} finally {
			remove_filter( 'woocommerce_payment_methods_list_item', $this->payment_method_probe, 20 );
			$this->payment_method_probe = null;
		}

		$account_payment_methods = wc_get_customer_saved_methods_list( get_current_user_id() );
		$checkout_item           = $checkout_payment_methods['enabled']['payment_utils_test'][0];
		$account_item            = $account_payment_methods['payment_utils_test'][0];

		$this->assertTrue( $probe_saw_actions, 'Later callbacks should receive My Account actions during Checkout preparation.' );
		$this->assertSame( $this->token->get_id(), $checkout_item['tokenId'], 'Checkout items should include the saved token ID.' );
		$this->assertArrayHasKey( 'display_name', $checkout_item, 'Checkout items should include the payment token display name.' );
		$this->assertSame( $this->token->get_display_name(), $checkout_item['display_name'], 'Checkout items should use the payment token display name.' );
		$this->assertArrayNotHasKey( 'actions', $checkout_item, 'Checkout items should omit My Account actions.' );
		$this->assertSame( $checkout_item, $checkout_payment_methods['default'], 'The default Checkout method should use the prepared item.' );
		$this->assertFalse(
			has_filter( 'woocommerce_payment_methods_list_item', array( PaymentUtils::class, 'prepare_payment_method_for_checkout' ) ),
			'The Checkout preparation callback should be removed after collecting saved methods.'
		);
		$this->assertArrayHasKey( 'actions', $account_item, 'A later My Account list should retain its actions.' );
		$this->assertArrayNotHasKey( 'tokenId', $account_item, 'A later My Account list should omit the Checkout token ID.' );
		$this->assertArrayNotHasKey( 'display_name', $account_item, 'A later My Account list should omit the Checkout display name.' );
	}

	/**
	 * @testdox Should remove Checkout callbacks when a payment method callback throws an exception.
	 */
	public function test_get_saved_payment_methods_removes_checkout_callbacks_when_callback_throws(): void {
		$checkout_filter_was_active             = false;
		$this->throwing_payment_method_callback = static function () use ( &$checkout_filter_was_active ) {
			$checkout_filter_was_active = 10 === has_filter(
				'woocommerce_payment_methods_list_item',
				array( PaymentUtils::class, 'prepare_payment_method_for_checkout' )
			);
			throw new \RuntimeException( 'Payment method callback failed.' );
		};
		add_filter( 'woocommerce_payment_methods_list_item', $this->throwing_payment_method_callback, 20, 2 );

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'Payment method callback failed.' );

		try {
			PaymentUtils::get_saved_payment_methods();
		} finally {
			$checkout_filter_is_active = has_filter(
				'woocommerce_payment_methods_list_item',
				array( PaymentUtils::class, 'prepare_payment_method_for_checkout' )
			);

			remove_filter( 'woocommerce_payment_methods_list_item', $this->throwing_payment_method_callback, 20 );
			$this->throwing_payment_method_callback = null;

			$account_payment_methods = wc_get_customer_saved_methods_list( get_current_user_id() );
			$account_item            = $account_payment_methods['payment_utils_test'][0];

			$this->assertArrayHasKey( 'actions', $account_item, 'A later My Account list should retain its actions after Checkout preparation fails.' );
			$this->assertArrayNotHasKey( 'tokenId', $account_item, 'A later My Account list should not inherit the Checkout token ID after an exception.' );
			$this->assertArrayNotHasKey( 'display_name', $account_item, 'A later My Account list should not inherit the Checkout display name after an exception.' );
			$this->assertTrue( $checkout_filter_was_active, 'The Checkout preparation callback should be active while collecting saved methods.' );
			$this->assertFalse(
				$checkout_filter_is_active,
				'The Checkout preparation callback should be removed when collecting saved methods fails.'
			);
		}
	}
}

// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound, Squiz.Classes.ClassFileName.NoMatch, SlevomatCodingStandard.Files.TypeNameMatchesFileName.NoMatchBetweenTypeNameAndFileName

/**
 * Generic custom payment token used by PaymentUtils tests.
 */
class PaymentUtilsTestToken extends \WC_Payment_Token {

	/**
	 * The custom token type.
	 *
	 * @var string
	 */
	protected $type = 'payment_utils_test';

	/**
	 * Get the token's native display name.
	 *
	 * @param string $deprecated Deprecated argument retained for compatibility.
	 * @return string
	 */
	public function get_display_name( $deprecated = '' ) {
		return 'Evergreen wallet ending in 2468';
	}
}
