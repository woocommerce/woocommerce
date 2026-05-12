<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Email;

use Automattic\WooCommerce\Internal\StockNotifications\Factory as StockNotificationFactory;
use Automattic\WooCommerce\Internal\StockNotifications\Notification as StockNotification;

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
	 * Key for object references stored in queued email args.
	 */
	private const QUEUED_OBJECT_KEY = '__woocommerce_deferred_email_object';

	/**
	 * Type key for object reference data stored in queued email args.
	 */
	private const QUEUED_OBJECT_TYPE_KEY = 'type';

	/**
	 * ID key for object reference data stored in queued email args.
	 */
	private const QUEUED_OBJECT_ID_KEY = 'id';

	/**
	 * Product object reference type.
	 */
	private const QUEUED_OBJECT_TYPE_PRODUCT = 'product';

	/**
	 * Order object reference type.
	 */
	private const QUEUED_OBJECT_TYPE_ORDER = 'order';

	/**
	 * Payment gateway object reference type.
	 */
	private const QUEUED_OBJECT_TYPE_PAYMENT_GATEWAY = 'payment_gateway';

	/**
	 * Stock notification object reference type.
	 */
	private const QUEUED_OBJECT_TYPE_STOCK_NOTIFICATION = 'stock_notification';

	/**
	 * Supported object argument types.
	 */
	private const QUEUED_OBJECT_TYPES = array(
		self::QUEUED_OBJECT_TYPE_PRODUCT            => \WC_Product::class,
		self::QUEUED_OBJECT_TYPE_ORDER              => \WC_Order::class,
		self::QUEUED_OBJECT_TYPE_PAYMENT_GATEWAY    => \WC_Payment_Gateway::class,
		self::QUEUED_OBJECT_TYPE_STOCK_NOTIFICATION => StockNotification::class,
	);

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
	 * Returns false when any argument cannot be represented in Action Scheduler
	 * storage, allowing callers to fall back to sending the email synchronously.
	 *
	 * @param string $filter The action hook name that triggered the email.
	 * @param array  $args   The arguments passed to the action hook.
	 * @return bool True if the email was queued.
	 */
	public function push( string $filter, array $args ): bool {
		if ( ! $this->can_defer( $args ) ) {
			return false;
		}

		try {
			$args = $this->prepare_args_for_queue( $args );
		} catch ( \UnexpectedValueException $e ) {
			return false;
		}

		$this->queue[] = array(
			'filter' => $filter,
			'args'   => $args,
		);

		if ( ! $this->shutdown_registered ) {
			add_action( 'shutdown', array( $this, 'dispatch' ), 100 );
			$this->shutdown_registered = true;
		}

		return true;
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
			\WC()->queue()->add(
				self::AS_HOOK,
				array( $item['filter'], $item['args'] ),
				self::AS_GROUP
			);
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

		$args = $this->restore_args_from_queue( $args );
		if ( null === $args ) {
			return;
		}

		\WC_Emails::send_queued_transactional_email( $filter, $args );
	}

	/**
	 * Check whether the arguments can be safely stored in Action Scheduler.
	 *
	 * @param array $args The arguments passed to the action hook.
	 * @return bool
	 */
	private function can_defer( array $args ): bool {
		foreach ( $args as $arg ) {
			if ( ! $this->can_defer_arg( $arg ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Check whether an argument can be safely stored in Action Scheduler.
	 *
	 * @param mixed $arg The argument to check.
	 * @return bool
	 */
	private function can_defer_arg( $arg ): bool {
		if ( is_array( $arg ) ) {
			return $this->can_defer( $arg );
		}

		return ! is_object( $arg ) || $this->is_supported_object_arg( $arg );
	}

	/**
	 * Check whether an object argument has a supported ID-based representation.
	 *
	 * @param object $arg The object argument to check.
	 * @return bool
	 */
	private function is_supported_object_arg( object $arg ): bool {
		if ( null === $this->get_queued_object_type( $arg ) || ! is_callable( array( $arg, 'get_id' ) ) ) {
			return false;
		}

		/**
		 * Supported queued object types expose get_id().
		 *
		 * @var \WC_Product|\WC_Order|\WC_Payment_Gateway|StockNotification $arg
		 */
		return $this->is_restorable_object_id( $arg->get_id() );
	}

	/**
	 * Convert queued arguments to JSON-safe values for Action Scheduler storage.
	 *
	 * @param array $args The arguments for the email callback.
	 * @return array
	 * @throws \UnexpectedValueException When a queued object argument cannot be prepared.
	 */
	private function prepare_args_for_queue( array $args ): array {
		foreach ( $args as $key => $arg ) {
			$args[ $key ] = $this->prepare_arg_for_queue( $arg );
		}

		return $args;
	}

	/**
	 * Convert a queued argument to a JSON-safe value.
	 *
	 * @param mixed $arg The argument to convert.
	 * @return mixed
	 * @throws \UnexpectedValueException When a queued object argument cannot be prepared.
	 */
	private function prepare_arg_for_queue( $arg ) {
		if ( is_array( $arg ) ) {
			return $this->prepare_args_for_queue( $arg );
		}

		if ( is_object( $arg ) ) {
			$type = $this->get_queued_object_type( $arg );

			if ( null === $type || ! is_callable( array( $arg, 'get_id' ) ) ) {
				throw new \UnexpectedValueException( 'Queued email object argument cannot be prepared.' );
			}

			/**
			 * Supported queued object types expose get_id().
			 *
			 * @var \WC_Product|\WC_Order|\WC_Payment_Gateway|StockNotification $arg
			 */
			$id = $arg->get_id();

			if ( ! $this->is_restorable_object_id( $id ) ) {
				throw new \UnexpectedValueException( 'Queued email object argument cannot be prepared.' );
			}

			return $this->create_queued_object_reference( $type, $id );
		}

		return $arg;
	}

	/**
	 * Check whether an object ID can be restored from Action Scheduler storage.
	 *
	 * @param mixed $id The object ID.
	 * @return bool
	 */
	private function is_restorable_object_id( $id ): bool {
		if ( ! is_int( $id ) && ! is_string( $id ) ) {
			return false;
		}

		return ! empty( $id );
	}

	/**
	 * Create a JSON-safe reference to a WooCommerce object.
	 *
	 * @param string     $type The object reference type.
	 * @param int|string $id   The object ID.
	 * @return array
	 */
	private function create_queued_object_reference( string $type, $id ): array {
		return array(
			self::QUEUED_OBJECT_KEY => array(
				self::QUEUED_OBJECT_TYPE_KEY => $type,
				self::QUEUED_OBJECT_ID_KEY   => $id,
			),
		);
	}

	/**
	 * Restore queued arguments after Action Scheduler storage.
	 *
	 * @param array $args The arguments for the email callback.
	 * @return array|null
	 */
	private function restore_args_from_queue( array $args ): ?array {
		try {
			return $this->restore_queued_args( $args );
		} catch ( \UnexpectedValueException $e ) {
			return null;
		}
	}

	/**
	 * Restore queued arguments after Action Scheduler storage.
	 *
	 * @param array $args The arguments for the email callback.
	 * @return array
	 */
	private function restore_queued_args( array $args ): array {
		foreach ( $args as $key => $arg ) {
			$args[ $key ] = $this->restore_arg_from_queue( $arg );
		}

		return $args;
	}

	/**
	 * Restore a queued argument after Action Scheduler storage.
	 *
	 * @param mixed $arg The argument to restore.
	 * @return mixed
	 * @throws \UnexpectedValueException When a queued object reference cannot be restored.
	 */
	private function restore_arg_from_queue( $arg ) {
		if ( ! is_array( $arg ) ) {
			return $arg;
		}

		if ( ! array_key_exists( self::QUEUED_OBJECT_KEY, $arg ) ) {
			return $this->restore_queued_args( $arg );
		}

		$reference = $arg[ self::QUEUED_OBJECT_KEY ];

		if ( ! is_array( $reference ) || ! isset( $reference[ self::QUEUED_OBJECT_TYPE_KEY ], $reference[ self::QUEUED_OBJECT_ID_KEY ] ) ) {
			throw new \UnexpectedValueException( 'Queued email object reference is invalid.' );
		}

		$object = $this->restore_queued_object_reference(
			(string) $reference[ self::QUEUED_OBJECT_TYPE_KEY ],
			$reference[ self::QUEUED_OBJECT_ID_KEY ]
		);

		if ( ! is_object( $object ) ) {
			throw new \UnexpectedValueException( 'Queued email object reference cannot be restored.' );
		}

		return $object;
	}

	/**
	 * Get the queued object type for a supported object argument.
	 *
	 * @param object $arg The object argument.
	 * @return string|null
	 */
	private function get_queued_object_type( object $arg ): ?string {
		foreach ( self::QUEUED_OBJECT_TYPES as $type => $class_name ) {
			if ( $arg instanceof $class_name ) {
				return $type;
			}
		}

		return null;
	}

	/**
	 * Restore a queued WooCommerce object reference.
	 *
	 * @param string     $type The object reference type.
	 * @param int|string $id   The object ID.
	 * @return mixed
	 */
	private function restore_queued_object_reference( string $type, $id ) {
		if ( ! is_int( $id ) && ! is_string( $id ) ) {
			return null;
		}

		switch ( $type ) {
			case self::QUEUED_OBJECT_TYPE_PRODUCT:
				return wc_get_product( $id );

			case self::QUEUED_OBJECT_TYPE_ORDER:
				return wc_get_order( $id );

			case self::QUEUED_OBJECT_TYPE_PAYMENT_GATEWAY:
				$gateways = \WC()->payment_gateways()->payment_gateways();
				return $gateways[ $id ] ?? null;

			case self::QUEUED_OBJECT_TYPE_STOCK_NOTIFICATION:
				return StockNotificationFactory::get_notification( (int) $id );
		}

		return null;
	}
}
