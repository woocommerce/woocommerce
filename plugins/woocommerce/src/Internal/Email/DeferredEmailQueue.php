<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Email;

/**
 * Handles deferred transactional email sending via Action Scheduler.
 *
 * Collects email callbacks during a request and dispatches each one as an
 * individual Action Scheduler action on shutdown, replacing the legacy
 * WC_Background_Emailer approach.
 *
 * @since 10.8.0
 */
final class DeferredEmailQueue {

	/**
	 * Action Scheduler hook for processing a queued email.
	 */
	private const AS_HOOK = 'woocommerce_send_queued_transactional_email';

	/**
	 * Action Scheduler group for email actions.
	 */
	private const AS_GROUP = 'woocommerce-emails';

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
		// Registered unconditionally so previously-scheduled AS jobs can still
		// be processed even if the feature is later disabled.
		add_action( self::AS_HOOK, array( $this, 'send_queued_transactional_email' ), 10, 2 );
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
	 * Each email is scheduled as an individual AS action for atomic
	 * processing and per-email failure isolation.
	 *
	 * @internal
	 */
	public function dispatch(): void {
		if ( empty( $this->queue ) ) {
			return;
		}

		foreach ( $this->queue as $item ) {
			\WC()->queue()->add( self::AS_HOOK, array( $item['filter'], $item['args'] ), self::AS_GROUP );
		}

		$this->queue               = array();
		$this->shutdown_registered = false;
	}

	/**
	 * Process a single queued transactional email from Action Scheduler.
	 *
	 * @internal
	 *
	 * @param mixed $filter The action hook name.
	 * @param mixed $args   The arguments for the email callback.
	 */
	public function send_queued_transactional_email( $filter, $args ): void {
		if ( ! is_string( $filter ) || ! is_array( $args ) ) {
			return;
		}

		\WC_Emails::send_queued_transactional_email( $filter, $args );
	}
}
