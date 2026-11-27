<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Templates\MyAccount;

use WC_Customer;
use WC_Unit_Test_Case;

/**
 * Tests for the My Account addresses template.
 */
class MyAddressTemplateTest extends WC_Unit_Test_Case {

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		update_option( 'woocommerce_ship_to_countries', '' );
		update_option( 'woocommerce_ship_to_destination', 'shipping' );
	}

	/**
	 * @testdox Core address links use whole-sentence labels instead of "Add %s" with the heading.
	 */
	public function test_core_address_links_use_whole_sentence_labels(): void {
		$this->log_in_customer( array() );

		$html = $this->render_my_address_template();

		$this->assertStringContainsString( 'Add billing address', $html, 'The empty billing address should have a sentence-case add link.' );
		$this->assertStringContainsString( 'Add shipping address', $html, 'The empty shipping address should have a sentence-case add link.' );
		$this->assertStringNotContainsString( 'Add Billing address', $html, 'The heading should not be pasted into the link label.' );
	}

	/**
	 * @testdox Edit and copy links use whole-sentence labels for the core address titles.
	 */
	public function test_edit_and_copy_links_use_whole_sentence_labels(): void {
		$this->log_in_customer(
			array(
				'billing_first_name' => 'Jane',
				'billing_address_1'  => 'Rua A, 1',
				'billing_city'       => 'Fortaleza',
				'billing_country'    => 'BR',
			)
		);

		$html = $this->render_my_address_template();

		$this->assertStringContainsString( 'Edit billing address', $html, 'A saved billing address should have a sentence-case edit link.' );
		$this->assertStringContainsString( 'Add shipping address', $html, 'The empty shipping address should still offer an add link.' );
		$this->assertStringContainsString( 'Copy billing address', $html, 'The copy link should name the source address in sentence case.' );
	}

	/**
	 * @testdox The whole-sentence labels go through the woocommerce text domain.
	 */
	public function test_link_labels_are_translatable(): void {
		$this->log_in_customer( array() );
		add_filter(
			'gettext_woocommerce',
			static function ( $translation, $text ) {
				return 'Add billing address' === $text ? 'Adicionar endereço de cobrança' : $translation;
			},
			10,
			2
		);

		$html = $this->render_my_address_template();

		$this->assertStringContainsString( 'Adicionar endereço de cobrança', $html, 'The add link should use the translated sentence.' );
	}

	/**
	 * @testdox Titles changed through woocommerce_my_account_get_addresses keep the generic labels.
	 */
	public function test_filtered_titles_fall_back_to_generic_labels(): void {
		$this->log_in_customer( array() );
		add_filter(
			'woocommerce_my_account_get_addresses',
			static function () {
				return array(
					'billing' => 'Invoice details',
					'pickup'  => 'Pickup point',
				);
			}
		);

		$html = $this->render_my_address_template();

		$this->assertStringContainsString( 'Add Invoice details', $html, 'A filtered core title should use the generic label with that title.' );
		$this->assertStringContainsString( 'Add Pickup point', $html, 'A custom address type should use the generic label.' );
		$this->assertStringNotContainsString( 'Add billing address', $html, 'The core label should not override a filtered title.' );
	}

	/**
	 * Create a customer with the given props and log them in.
	 *
	 * @param array<string, string> $props Customer props, e.g. billing_first_name.
	 */
	private function log_in_customer( array $props ): void {
		$customer = new WC_Customer();
		$customer->set_username( 'address_template_' . wp_generate_password( 6, false ) );
		$customer->set_email( wp_generate_password( 8, false ) . '@example.org' );
		$customer->set_password( 'password' );
		$customer->set_props( $props );
		$customer->save();
		wp_set_current_user( $customer->get_id() );
	}

	/**
	 * Render the My Account addresses template.
	 *
	 * @return string
	 */
	private function render_my_address_template(): string {
		return wc_get_template_html( 'myaccount/my-address.php' );
	}
}
