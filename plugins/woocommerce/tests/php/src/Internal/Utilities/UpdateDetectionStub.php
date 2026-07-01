<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Utilities;

use Automattic\WooCommerce\Internal\Utilities\UpdateDetection;

/**
 * UpdateDetection stub with a controllable update-window state that records suppressed-work log calls.
 */
class UpdateDetectionStub extends UpdateDetection {

	/**
	 * The update-window state to report.
	 *
	 * @var bool
	 */
	public $in_progress = false;

	/**
	 * Recorded log_suppressed_work calls, each an array with 'context' and 'throwable' keys.
	 *
	 * @var array
	 */
	public $logged = array();

	/**
	 * Initialize the stub.
	 *
	 * @param bool $in_progress The update-window state to report.
	 */
	public function __construct( bool $in_progress = false ) {
		$this->in_progress = $in_progress;
	}

	/**
	 * Report the configured update-window state.
	 *
	 * @return bool
	 */
	public function is_update_in_progress(): bool {
		return $this->in_progress;
	}

	/**
	 * Record a suppressed-work log call instead of logging.
	 *
	 * @param string          $context The context identifier.
	 * @param \Throwable|null $throwable The caught error, if any.
	 */
	public function log_suppressed_work( string $context, ?\Throwable $throwable = null ): void {
		$this->logged[] = array(
			'context'   => $context,
			'throwable' => $throwable,
		);
	}
}
