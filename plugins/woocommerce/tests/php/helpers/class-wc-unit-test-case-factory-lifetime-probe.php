<?php
/**
 * WC lifecycle probe for per-test factory ownership.
 *
 * @package WooCommerce\Tests
 */

declare( strict_types = 1 );

/** Inner WC test case run directly with its real setup and teardown. */
class WC_Unit_Test_Case_Factory_Lifetime_Probe extends WC_Unit_Test_Case {
	/** @var WeakReference<WC_Unit_Test_Factory> Reference to the owned factory. */
	public WeakReference $factory_reference;

	/** @var bool Whether the factory was available after parent teardown. */
	public bool $factory_accessible_after_parent_teardown = false;

	/** Exercise the factory and capture a weak reference. */
	public function exercise_factory(): void {
		$this->assertInstanceOf( WC_Unit_Test_Factory::class, $this->factory );
		$this->assertSame( $this->factory, $this->factory->user->factory );
		$this->factory_reference = WeakReference::create( $this->factory );
	}

	/**
	 * Return the owned factory.
	 *
	 * @return WC_Unit_Test_Factory Factory owned by the test.
	 */
	public function return_factory(): WC_Unit_Test_Factory {
		$this->exercise_factory();
		return $this->factory;
	}

	/** Fail after exercising the factory. */
	public function fail_body(): void {
		$this->exercise_factory();
		$this->fail( 'Fixture lifetime probe failure' );
	}

	/** Record factory availability after parent teardown. */
	public function tearDown(): void {
		parent::tearDown();
		$this->factory_accessible_after_parent_teardown = $this->factory instanceof WC_Unit_Test_Factory;
	}

	/**
	 * Check whether the factory property remains initialized.
	 *
	 * @return bool Whether the factory property is initialized.
	 */
	public function is_factory_initialized(): bool {
		return array_key_exists( 'factory', get_object_vars( $this ) );
	}
}
