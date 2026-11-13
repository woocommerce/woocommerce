<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\EmailEditor\PersonalizationTags;

use Automattic\WooCommerce\EmailEditor\Engine\Logger\Email_Editor_Logger;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tags_Registry;
use Automattic\WooCommerce\Internal\EmailEditor\PersonalizationTags\CustomerTagsProvider;
use WC_Unit_Test_Case;

/**
 * Tests for the CustomerTagsProvider class: which customer values an email shows.
 */
class CustomerTagsProviderTest extends WC_Unit_Test_Case {

	/**
	 * Registry with the customer tags registered.
	 *
	 * @var Personalization_Tags_Registry
	 */
	private $registry;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->registry = new Personalization_Tags_Registry( new Email_Editor_Logger() );
		( new CustomerTagsProvider() )->register_tags( $this->registry );
	}

	/**
	 * @testdox Takes customer values from the order's billing details.
	 * @testWith ["customer-email", "billing@example.com"]
	 *           ["customer-first-name", "Ada"]
	 *           ["customer-last-name", "Lovelace"]
	 *           ["customer-full-name", "Ada Lovelace"]
	 *           ["customer-country", "Germany"]
	 *
	 * @param string $tag      Tag name without the woocommerce/ prefix.
	 * @param string $expected Expected value.
	 */
	public function test_values_from_order( string $tag, string $expected ): void {
		$order = wc_create_order();
		$order->set_billing_email( 'billing@example.com' );
		$order->set_billing_first_name( 'Ada' );
		$order->set_billing_last_name( 'Lovelace' );
		$order->set_billing_country( 'DE' );

		$this->assertSame( $expected, $this->value( $tag, array( 'order' => $order ) ) );
	}

	/**
	 * @testdox Takes customer values from the WordPress user when there is no order.
	 * @testWith ["customer-first-name", "Grace"]
	 *           ["customer-last-name", "Hopper"]
	 *           ["customer-full-name", "Grace Hopper"]
	 *           ["customer-username", "grace"]
	 *
	 * @param string $tag      Tag name without the woocommerce/ prefix.
	 * @param string $expected Expected value.
	 */
	public function test_values_from_user( string $tag, string $expected ): void {
		$user = $this->factory->user->create_and_get(
			array(
				'user_login' => 'grace',
				'first_name' => 'Grace',
				'last_name'  => 'Hopper',
			)
		);

		$this->assertSame( $expected, $this->value( $tag, array( 'wp_user' => $user ) ) );
	}

	/**
	 * @testdox Uses the recipient for the email and leaves other values empty without an order or user.
	 * @testWith ["customer-email", "recipient@example.com"]
	 *           ["customer-first-name", ""]
	 *           ["customer-full-name", ""]
	 *           ["customer-username", ""]
	 *           ["customer-country", ""]
	 *
	 * @param string $tag      Tag name without the woocommerce/ prefix.
	 * @param string $expected Expected value.
	 */
	public function test_values_without_order_or_user( string $tag, string $expected ): void {
		$this->assertSame( $expected, $this->value( $tag, array( 'recipient_email' => 'recipient@example.com' ) ) );
	}

	/**
	 * @testdox Shows the country code when WooCommerce doesn't know the country.
	 */
	public function test_unknown_country_shows_its_code(): void {
		$order = wc_create_order();
		$order->set_billing_country( 'XX' );

		$this->assertSame( 'XX', $this->value( 'customer-country', array( 'order' => $order ) ) );
	}

	/**
	 * Resolves a tag in the given context.
	 *
	 * @param string $tag     Tag name without the woocommerce/ prefix.
	 * @param array  $context Personalization context.
	 */
	private function value( string $tag, array $context ): string {
		return $this->registry->get_by_token( "[woocommerce/{$tag}]" )->execute_callback( $context );
	}
}
