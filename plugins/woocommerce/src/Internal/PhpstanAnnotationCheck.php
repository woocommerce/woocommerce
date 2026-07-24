<?php
/**
 * Throwaway file used to verify PHPStan CI annotations. Do not merge.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal;

/**
 * Class with deliberate static analysis errors.
 *
 * It exists only so a pull request contains PHPStan failures, which lets us confirm
 * that the workflow's inline annotations land on the right file and line in the diff.
 * Nothing loads this class, and it must never be merged.
 */
class PhpstanAnnotationCheck {

	/**
	 * Returns a string even though the signature promises an int.
	 *
	 * @return int
	 */
	public function wrong_return_type(): int {
		return 'this is not an int';
	}

	/**
	 * Calls a method that does not exist on this class.
	 *
	 * @return void
	 */
	public function undefined_method_call() {
		$this->this_method_does_not_exist();
	}
}
