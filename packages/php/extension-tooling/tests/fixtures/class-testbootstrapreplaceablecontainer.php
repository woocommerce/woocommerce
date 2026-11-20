<?php
/**
 * Replaceable inner container fixture.
 *
 * @package WooCommerce\ExtensionTools
 */

/**
 * Record service replacements and resets.
 */
class TestBootstrapReplaceableContainer {

	/**
	 * Ordered log of calls.
	 *
	 * @var array
	 */
	public $calls = array();

	/**
	 * Replace a service.
	 *
	 * @param string $service Service class.
	 * @param object $replacement Replacement instance.
	 */
	public function replace( string $service, object $replacement ): void {
		$this->calls[] = array( $service, $replacement );
	}

	/**
	 * Reset resolved services.
	 */
	public function reset_all_resolved() {
		$this->calls[] = array( 'reset_all_resolved' );
	}
}
