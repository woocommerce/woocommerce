<?php
/**
 * Tests for the account functions.
 *
 * @package WooCommerce\Tests
 */

declare( strict_types = 1 );

/**
 * Tests for wc-account-functions.php.
 */
class WC_Account_Functions_Test extends WC_Unit_Test_Case {

	/**
	 * @testdox Should give saved method list item filters the token's own display name, and keep the name a filter sets.
	 */
	public function test_saved_payment_methods_list_item_carries_token_display_name(): void {
		add_filter(
			'woocommerce_payment_token_class',
			static function ( $class_name, $type ) {
				return FakeCustomPaymentToken::TYPE === $type ? FakeCustomPaymentToken::class : $class_name;
			},
			10,
			2
		);

		$user_id = self::factory()->user->create();
		$token   = new FakeCustomPaymentToken();
		$token->set_token( 'fake-custom-token' );
		$token->set_gateway_id( WC_Gateway_BACS::ID );
		$token->set_user_id( $user_id );
		$token->save();

		$display_name_seen_by_filter = null;
		add_filter(
			'woocommerce_payment_methods_list_item',
			static function ( $item ) use ( &$display_name_seen_by_filter ) {
				$display_name_seen_by_filter = $item['display_name'] ?? null;
				$item['display_name']        = 'Label set by an extension';
				return $item;
			}
		);

		$list = wc_get_account_saved_payment_methods_list( array(), $user_id );

		$this->assertSame( FakeCustomPaymentToken::DISPLAY_NAME, $display_name_seen_by_filter, 'Item filters should receive the token display name.' );
		$this->assertSame( 'Label set by an extension', $list[ FakeCustomPaymentToken::TYPE ][0]['display_name'], 'A display name set by an item filter should be kept.' );
	}
}
