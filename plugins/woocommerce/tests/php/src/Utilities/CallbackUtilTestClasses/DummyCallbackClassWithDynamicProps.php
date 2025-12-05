<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Utilities\CallbackUtilTestClasses;

/**
 * Dummy class with dynamic properties (like the real-world issue).
 */
class DummyCallbackClassWithDynamicProps {
	/**
	 * Timestamp property.
	 *
	 * @var float
	 */
	public $timestamp;

	/**
	 * Random value property.
	 *
	 * @var int
	 */
	public $random_value;

	/**
	 * Constructor that sets dynamic properties.
	 */
	public function __construct() {
		$this->timestamp    = microtime( true );
		$this->random_value = wp_rand();
	}

	/**
	 * Test method with dynamic props.
	 *
	 * @return string
	 */
	public function my_method() {
		return 'method with dynamic props';
	}
}
