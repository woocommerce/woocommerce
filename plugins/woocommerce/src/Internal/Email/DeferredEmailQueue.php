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
		try {
			$args = $this->prepare_arg_for_queue( $args );
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
	 * Convert a queued argument to a JSON-safe value.
	 *
	 * @param mixed $arg The argument to convert.
	 * @return mixed
	 * @throws \UnexpectedValueException When a queued object argument cannot be prepared.
	 */
	private function prepare_arg_for_queue( $arg ) {
		if ( is_array( $arg ) ) {
			foreach ( $arg as $key => $value ) {
				$arg[ $key ] = $this->prepare_arg_for_queue( $value );
			}

			return $arg;
		}

		if ( is_object( $arg ) ) {
			foreach ( $this->get_supported_object_types() as $type => $object_type ) {
				if ( ! $arg instanceof $object_type['class'] ) {
					continue;
				}

				$id = $object_type['get_id']( $arg );

				if ( empty( $id ) || ( ! is_int( $id ) && ! is_string( $id ) ) ) {
					throw new \UnexpectedValueException( 'Queued email object argument cannot be prepared.' );
				}

				return array(
					self::QUEUED_OBJECT_KEY => array(
						'type' => $type,
						'id'   => $id,
					),
				);
			}

			throw new \UnexpectedValueException( 'Queued email object argument cannot be prepared.' );
		}

		return $arg;
	}

	/**
	 * Restore queued arguments after Action Scheduler storage.
	 *
	 * @param array $args The arguments for the email callback.
	 * @return array|null
	 */
	private function restore_args_from_queue( array $args ): ?array {
		try {
			foreach ( $args as $key => $arg ) {
				$args[ $key ] = $this->restore_arg_from_queue( $arg );
			}

			return $args;
		} catch ( \UnexpectedValueException $e ) {
			return null;
		}
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
			foreach ( $arg as $key => $value ) {
				$arg[ $key ] = $this->restore_arg_from_queue( $value );
			}

			return $arg;
		}

		$reference = $arg[ self::QUEUED_OBJECT_KEY ];

		if ( ! is_array( $reference ) || ! isset( $reference['type'], $reference['id'] ) ) {
			throw new \UnexpectedValueException( 'Queued email object reference is invalid.' );
		}

		$id = $reference['id'];

		if ( ! is_int( $id ) && ! is_string( $id ) ) {
			throw new \UnexpectedValueException( 'Queued email object reference is invalid.' );
		}

		$object_type = $this->get_supported_object_types()[ (string) $reference['type'] ] ?? null;

		if ( ! is_array( $object_type ) ) {
			throw new \UnexpectedValueException( 'Queued email object reference is invalid.' );
		}

		$object = $object_type['fetch']( $id );

		if ( ! is_object( $object ) ) {
			throw new \UnexpectedValueException( 'Queued email object reference cannot be restored.' );
		}

		return $object;
	}

	/**
	 * Get supported queued object types.
	 *
	 * @return array<string, array{class: class-string, get_id: callable, fetch: callable}>
	 */
	private function get_supported_object_types(): array {
		return array(
			'product'            => array(
				'class'  => \WC_Product::class,
				'get_id' => static function ( $queued_object ) {
					return $queued_object instanceof \WC_Product ? $queued_object->get_id() : null;
				},
				'fetch'  => static function ( $id ) {
					return \WC()->call_function( 'wc_get_product', $id );
				},
			),
			'order'              => array(
				'class'  => \WC_Order::class,
				'get_id' => static function ( $queued_object ) {
					return $queued_object instanceof \WC_Order ? $queued_object->get_id() : null;
				},
				'fetch'  => static function ( $id ) {
					return \WC()->call_function( 'wc_get_order', $id );
				},
			),
			'payment_gateway'    => array(
				'class'  => \WC_Payment_Gateway::class,
				'get_id' => static function ( $queued_object ) {
					return $queued_object instanceof \WC_Payment_Gateway ? $queued_object->id : null;
				},
				'fetch'  => static function ( $id ) {
					$gateways = \WC()->payment_gateways()->payment_gateways();
					return $gateways[ $id ] ?? null;
				},
			),
			'stock_notification' => array(
				'class'  => StockNotification::class,
				'get_id' => static function ( $queued_object ) {
					return $queued_object instanceof StockNotification ? $queued_object->get_id() : null;
				},
				'fetch'  => static function ( $id ) {
					return StockNotificationFactory::get_notification( (int) $id );
				},
			),
		);
	}
}
