<?php
/**
 * Integration tests for the ConsumerRegistry (the set of engine-consuming extensions).
 *
 * Integration suite because invalid registrations raise `_doing_it_wrong()` notices.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Integration\Integration\Ownership;

use EngineIntegrationTestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Ownership\ConsumerRegistry;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Integration\Ownership\ConsumerRegistry
 */
class ConsumerRegistryTest extends EngineIntegrationTestCase {

	private const REGISTER = 'Automattic\WooCommerce\SubscriptionsEngine\Integration\Ownership\ConsumerRegistry::register';

	public function set_up(): void {
		parent::set_up();
		ConsumerRegistry::reset();
	}

	public function tear_down(): void {
		ConsumerRegistry::reset();
		parent::tear_down();
	}

	/**
	 * @testdox No owner has a due handler when nothing is registered.
	 */
	public function test_no_handler_owners_when_nothing_is_registered(): void {
		$this->assertSame( array(), ConsumerRegistry::get_slugs_with_contract_due_handler() );
	}

	/**
	 * @testdox Registering a slug without configuration records no due handler.
	 */
	public function test_register_records_a_consumer(): void {
		ConsumerRegistry::register( 'lite' );

		$this->assertNull( ConsumerRegistry::get_contract_due_handler( 'lite' ) );
		$this->assertSame( array(), ConsumerRegistry::get_slugs_with_contract_due_handler(), 'A slug without a handler is not a dispatch owner.' );
	}

	/**
	 * @testdox A callable due handler is stored and returned for its slug.
	 */
	public function test_register_stores_a_callable_due_handler(): void {
		$handler = static function (): void {};

		ConsumerRegistry::register( 'lite', array( 'contract_due_handler' => $handler ) );

		$this->assertSame( $handler, ConsumerRegistry::get_contract_due_handler( 'lite' ) );
		$this->assertSame( array( 'lite' ), ConsumerRegistry::get_slugs_with_contract_due_handler() );
	}

	/**
	 * @testdox Only slugs that registered a due handler are listed as dispatch owners.
	 */
	public function test_slugs_with_due_handler_lists_only_handler_owners(): void {
		ConsumerRegistry::register( 'lite', array( 'contract_due_handler' => static function (): void {} ) );
		ConsumerRegistry::register( 'premium' );

		$this->assertSame( array( 'lite' ), ConsumerRegistry::get_slugs_with_contract_due_handler() );
		$this->assertNull( ConsumerRegistry::get_contract_due_handler( 'unknown' ), 'An unregistered slug has no handler.' );
	}

	/**
	 * @testdox A non-callable due handler raises a notice and is dropped; the slug still registers.
	 */
	public function test_non_callable_due_handler_is_dropped(): void {
		$this->setExpectedIncorrectUsage( self::REGISTER );

		ConsumerRegistry::register( 'lite', array( 'contract_due_handler' => 'not_a_function_anywhere' ) );

		$this->assertNull( ConsumerRegistry::get_contract_due_handler( 'lite' ) );
		$this->assertSame( array(), ConsumerRegistry::get_slugs_with_contract_due_handler() );

		// The slug registered: a later registration is a repeat and does not take.
		ConsumerRegistry::register( 'lite', array( 'contract_due_handler' => static function (): void {} ) );
		$this->assertNull( ConsumerRegistry::get_contract_due_handler( 'lite' ) );
	}

	/**
	 * @testdox An unknown configuration key raises a notice and is ignored.
	 */
	public function test_unknown_config_key_is_ignored(): void {
		$this->setExpectedIncorrectUsage( self::REGISTER );
		$handler = static function (): void {};

		ConsumerRegistry::register(
			'lite',
			array(
				'contract_due_handler' => $handler,
				'renewal_handler'      => $handler,
			)
		);

		$this->assertSame( $handler, ConsumerRegistry::get_contract_due_handler( 'lite' ), 'Known keys still apply.' );
	}

	/**
	 * @testdox A repeat registration raises a notice and keeps the first registration.
	 */
	public function test_repeat_registration_keeps_the_first(): void {
		$this->setExpectedIncorrectUsage( self::REGISTER );
		$first = static function (): void {};

		ConsumerRegistry::register( 'lite', array( 'contract_due_handler' => $first ) );
		ConsumerRegistry::register( 'lite', array( 'contract_due_handler' => static function (): void {} ) );
		ConsumerRegistry::register( ' lite ' );

		$this->assertSame( $first, ConsumerRegistry::get_contract_due_handler( 'lite' ) );
		$this->assertSame( array( 'lite' ), ConsumerRegistry::get_slugs_with_contract_due_handler() );
	}

	/**
	 * @testdox An empty or whitespace-only slug is ignored and a slug is stored trimmed.
	 */
	public function test_register_ignores_blank_slugs_and_trims(): void {
		$handler = static function (): void {};

		ConsumerRegistry::register( '' );
		ConsumerRegistry::register( "  \t\n", array( 'contract_due_handler' => $handler ) );
		$this->assertSame( array(), ConsumerRegistry::get_slugs_with_contract_due_handler(), 'A blank slug must not register.' );

		ConsumerRegistry::register( '  woocommerce-subscriptions-lite  ', array( 'contract_due_handler' => $handler ) );
		$this->assertSame( array( 'woocommerce-subscriptions-lite' ), ConsumerRegistry::get_slugs_with_contract_due_handler(), 'The slug is stored trimmed.' );
		$this->assertSame( $handler, ConsumerRegistry::get_contract_due_handler( ' woocommerce-subscriptions-lite ' ) );
	}

	/**
	 * @testdox Unregister removes only the named consumer and its handler.
	 */
	public function test_unregister_removes_only_the_named_consumer(): void {
		ConsumerRegistry::register( 'lite', array( 'contract_due_handler' => static function (): void {} ) );
		ConsumerRegistry::register( 'premium', array( 'contract_due_handler' => static function (): void {} ) );

		ConsumerRegistry::unregister( 'lite' );
		$this->assertSame( array( 'premium' ), ConsumerRegistry::get_slugs_with_contract_due_handler(), 'Only the named consumer is removed.' );
		$this->assertNull( ConsumerRegistry::get_contract_due_handler( 'lite' ) );

		ConsumerRegistry::unregister( 'unknown' );
		$this->assertSame( array( 'premium' ), ConsumerRegistry::get_slugs_with_contract_due_handler(), 'An unknown slug is a no-op.' );

		ConsumerRegistry::unregister( ' premium ' );
		$this->assertSame( array(), ConsumerRegistry::get_slugs_with_contract_due_handler(), 'The slug is trimmed, matching register().' );
	}

	/**
	 * @testdox Reset clears every registration.
	 */
	public function test_reset_clears_every_registration(): void {
		ConsumerRegistry::register( 'lite', array( 'contract_due_handler' => static function (): void {} ) );
		ConsumerRegistry::reset();

		$this->assertSame( array(), ConsumerRegistry::get_slugs_with_contract_due_handler() );
		$this->assertNull( ConsumerRegistry::get_contract_due_handler( 'lite' ) );
	}
}
