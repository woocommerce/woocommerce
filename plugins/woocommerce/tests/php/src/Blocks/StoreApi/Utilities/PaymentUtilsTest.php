<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Utilities;

use Automattic\WooCommerce\StoreApi\Utilities\PaymentUtils;
use WC_Helper_Payment_Token;
use WC_Unit_Test_Case;

/**
 * Tests for the PaymentUtils class.
 */
class PaymentUtilsTest extends WC_Unit_Test_Case {

	/**
	 * Set up a logged-in customer with one saved card.
	 */
	public function setUp(): void {
		parent::setUp();

		$user_id = self::factory()->user->create();
		wp_set_current_user( $user_id );
		WC_Helper_Payment_Token::create_cc_token( $user_id );
	}

	/**
	 * @testdox Should return saved methods in the account list shape with the token ID added.
	 */
	public function test_get_saved_payment_methods_keeps_account_list_shape(): void {
		$payment_methods = PaymentUtils::get_saved_payment_methods();

		$default = $payment_methods['default'];
		$this->assertIsInt( $default['tokenId'], 'The token ID should be added for Checkout token selection.' );
		$this->assertArrayHasKey( 'actions', $default, 'The shared utility should keep the account list shape, including actions.' );
		$this->assertArrayNotHasKey( 'display_name', $default, 'Display names are a Checkout hydration concern, not part of the shared utility.' );
	}

	/**
	 * @testdox Should remove its temporary list item filter when a later item filter throws.
	 */
	public function test_get_saved_payment_methods_removes_token_id_filter_when_item_filter_throws(): void {
		$throwing_filter = static function () {
			throw new \RuntimeException( 'Item filter failed.' );
		};
		add_filter( 'woocommerce_payment_methods_list_item', $throwing_filter, 20 );

		$thrown = null;
		try {
			PaymentUtils::get_saved_payment_methods();
		} catch ( \RuntimeException $exception ) {
			$thrown = $exception;
		}
		remove_filter( 'woocommerce_payment_methods_list_item', $throwing_filter, 20 );

		$this->assertInstanceOf( \RuntimeException::class, $thrown, 'The item filter exception should propagate.' );

		$account_methods = wc_get_customer_saved_methods_list( get_current_user_id() );
		$this->assertArrayNotHasKey( 'tokenId', $account_methods['cc'][0], 'The temporary token ID filter must not leak into later account lists.' );
	}
}
