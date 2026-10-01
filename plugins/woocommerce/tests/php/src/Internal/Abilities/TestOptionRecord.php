<?php
/**
 * Test option record class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities;

use Automattic\WooCommerce\Abilities\InMemorySubject;

/**
 * An option stored by a test extension, changed through an in-memory write.
 */
class TestOptionRecord implements InMemorySubject {

	/**
	 * Option name.
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Value applied in memory.
	 *
	 * @var string
	 */
	public $value;

	/**
	 * Load the option.
	 *
	 * @param string $name Option name.
	 */
	public function __construct( string $name ) {
		$this->name  = $name;
		$this->value = (string) get_option( $name, '' );
	}

	/**
	 * Current state.
	 *
	 * @return array
	 */
	public function snapshot(): array {
		return array(
			'name'  => $this->name,
			'value' => $this->value,
		);
	}

	/**
	 * Save the value, refusing `unsavable`.
	 *
	 * @return true|\WP_Error
	 */
	public function save() {
		if ( 'unsavable' === $this->value ) {
			return new \WP_Error( 'test_option_unsavable', 'Value cannot be saved.' );
		}
		update_option( $this->name, $this->value );
		return true;
	}
}
