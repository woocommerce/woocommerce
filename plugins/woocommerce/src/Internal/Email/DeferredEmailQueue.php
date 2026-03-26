<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Email;

/**
 * Handles deferred transactional email sending via Action Scheduler.
 *
 * Collects email callbacks during a request and dispatches them as a single
 * batched Action Scheduler action on shutdown, replacing the legacy
 * WC_Background_Emailer approach.
 *
 * @since 10.8.0
 */
final class DeferredEmailQueue {

	/**
	 * Action Scheduler hook for processing queued emails.
	 */
	private const AS_HOOK = 'woocommerce_send_queued_transactional_emails';

	/**
	 * Action Scheduler group for email actions.
	 */
	private const AS_GROUP = 'woocommerce-emails';

	/**
	 * Default number of emails per Action Scheduler job.
	 */
	private const DEFAULT_CHUNK_SIZE = 10;

	/**
	 * Queue of email callbacks collected during the current request.
	 *
	 * @var array<int, array{filter: string, args: array}>
	 */
	private array $queue = array();

	/**
	 * Whether the shutdown hook has been registered.
	 *
	 * @var bool
	 */
	private bool $shutdown_registered = false;

	/**
	 * Initialize hooks.
	 *
	 * @internal
	 */
	final public function init(): void { // phpcs:ignore Generic.CodeAnalysis.UnnecessaryFinalModifier.Found
		add_action( self::AS_HOOK, array( $this, 'send_queued_transactional_emails' ) );
	}

	/**
	 * Add an email callback to the queue.
	 *
	 * @param string $filter The action hook name that triggered the email.
	 * @param array  $args   The arguments passed to the action hook.
	 */
	public function push( string $filter, array $args ): void {
		$this->queue[] = array(
			'filter' => $filter,
			'args'   => $args,
		);

		if ( ! $this->shutdown_registered ) {
			add_action( 'shutdown', array( $this, 'dispatch' ), 100 );
			$this->shutdown_registered = true;
		}
	}

	/**
	 * Dispatch queued emails via Action Scheduler on shutdown.
	 *
	 * @internal
	 */
	public function dispatch(): void {
		if ( empty( $this->queue ) ) {
			return;
		}

		/**
		 * Filter the number of emails per Action Scheduler job.
		 *
		 * @since 10.8.0
		 * @param int $chunk_size Number of emails per batch. Default 10.
		 */
		$chunk_size = max( 1, (int) apply_filters( 'woocommerce_deferred_email_chunk_size', self::DEFAULT_CHUNK_SIZE ) );
		$chunks     = array_chunk( $this->queue, $chunk_size );

		foreach ( $chunks as $chunk ) {
			\WC()->queue()->add( self::AS_HOOK, array( $chunk ), self::AS_GROUP );
		}

		$this->queue = array();
	}

	/**
	 * Process a batch of queued transactional emails from Action Scheduler.
	 *
	 * @internal
	 *
	 * @param mixed $queue The batch of email callbacks to process.
	 */
	public function send_queued_transactional_emails( $queue ): void {
		if ( ! is_array( $queue ) ) {
			return;
		}

		foreach ( $queue as $callback ) {
			if ( ! is_array( $callback ) || ! isset( $callback['filter'], $callback['args'] ) || ! is_string( $callback['filter'] ) || ! is_array( $callback['args'] ) ) {
				continue;
			}
			try {
				\WC_Emails::send_queued_transactional_email( $callback['filter'], $callback['args'] );
			} catch ( \Throwable $e ) {
				wc_get_logger()->error(
					sprintf( 'Deferred email failed for %s: %s', $callback['filter'], $e->getMessage() ),
					array( 'source' => 'deferred-emails' )
				);
			}
		}
	}
}
