<?php
/**
 * ActiveCurrencyScope class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Currency;

defined( 'ABSPATH' ) || exit;

/**
 * Holds a currency and operation active from ActiveCurrency::scope(). Call release() when the work ends, or let the
 * object be destroyed. Either restores the state from before the scope was created. Releasing more than once does
 * nothing.
 */
final class ActiveCurrencyScope {

	/**
	 * Stack index the scope was pushed at.
	 *
	 * @var int
	 */
	private $index;

	/**
	 * Id of the scope, matched against the stack entry before removing it.
	 *
	 * @var int
	 */
	private $id;

	/**
	 * Whether the scope has been released.
	 *
	 * @var bool
	 */
	private $released = false;

	/**
	 * Constructor.
	 *
	 * @internal Created by ActiveCurrency::scope().
	 * @param int $index Stack index the scope was pushed at.
	 * @param int $id    Id of the scope.
	 */
	public function __construct( int $index, int $id ) {
		$this->index = $index;
		$this->id    = $id;
	}

	/**
	 * Release the scope now, restoring the state from before it was created. Safe to call more than once.
	 *
	 * @return void
	 */
	public function release(): void {
		if ( $this->released ) {
			return;
		}

		$this->released = true;
		ActiveCurrency::release_entry( $this->index, $this->id );
	}

	/**
	 * Release the scope when it is destroyed, unless it was already released.
	 *
	 * @return void
	 */
	public function __destruct() {
		$this->release();
	}
}
