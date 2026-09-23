<?php
/**
 * Tests for the My Account payment methods template.
 */

declare( strict_types = 1 );

/**
 * My Account payment methods template test.
 */
class WC_My_Account_Payment_Methods_Template_Test extends WC_Unit_Test_Case {

	/**
	 * @testdox Should label saved methods without a card brand by their own name, keep brand labels, and skip names that only repeat the token type.
	 */
	public function test_labels_saved_methods_without_brand(): void {
		add_filter(
			'woocommerce_payment_token_class',
			static function ( $class_name, $type ) {
				return FakeCustomPaymentToken::TYPE === $type ? FakeCustomPaymentToken::class : $class_name;
			},
			10,
			2
		);
		add_filter(
			'woocommerce_payment_methods_list_item',
			static function ( $item, $token ) {
				if ( 'branded' === $token->get_token() ) {
					$item['method']['brand'] = 'wallet';
				}
				if ( 'unnamed' === $token->get_token() ) {
					$item['display_name'] = 'Fake_Custom';
				}
				return $item;
			},
			10,
			2
		);

		$user_id = self::factory()->user->create();
		wp_set_current_user( $user_id );
		foreach ( array( 'plain', 'branded', 'unnamed' ) as $token_value ) {
			$token = new FakeCustomPaymentToken();
			$token->set_token( $token_value );
			$token->set_gateway_id( WC_Gateway_BACS::ID );
			$token->set_user_id( $user_id );
			$token->save();
		}

		$html = wc_get_template_html( 'myaccount/payment-methods.php' );

		$document = new DOMDocument();
		$errors   = libxml_use_internal_errors( true );
		$document->loadHTML( '<!doctype html><html><body>' . $html . '</body></html>' );
		libxml_clear_errors();
		libxml_use_internal_errors( $errors );
		$xpath = new DOMXPath( $document );

		$method_cells = $xpath->query( '//td[contains(concat(" ", normalize-space(@class), " "), " payment-method-method ")]' );
		$delete_links = $xpath->query( '//a[contains(concat(" ", normalize-space(@class), " "), " delete ")]' );
		if ( false === $method_cells || false === $delete_links ) {
			throw new \RuntimeException( 'Unable to query the payment methods table.' );
		}

		$method_labels = array();
		foreach ( $method_cells as $method_cell ) {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API property name.
			$method_labels[] = trim( $method_cell->textContent );
		}
		$this->assertSame(
			array( FakeCustomPaymentToken::DISPLAY_NAME, 'Wallet', '' ),
			$method_labels,
			'Rows without a brand should show the token name, a brand should win, and a name equal to the token type should be skipped.'
		);
		$this->assertSame( 3, $delete_links->length, 'Every row should keep its Delete action.' );
		$this->assertStringNotContainsString( 'Undefined array key', $html, 'The template should not emit PHP notices for tokens without a brand.' );
	}
}
