<?php
/**
 * Tool-local reservation phases.
 *
 * @package WooCommerce\Tools\ModelChecking
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tools\ModelChecking;

/**
 * Names phases in the model, not WooCommerce order statuses.
 */
final class Phase {
	/** No call has started. */
	public const IDLE = 'idle';
	/** A database attempt can run. */
	public const QUERY = 'query';
	/** Failed reservation needs cleanup. */
	public const CLEANUP = 'cleanup';
	/** The call reserved stock. */
	public const RESERVED = 'reserved';
	/** The call failed and cleaned up. */
	public const FAILED = 'failed';
	/** The deliberately broken variant has read availability. */
	public const WRITE = 'write';
}
