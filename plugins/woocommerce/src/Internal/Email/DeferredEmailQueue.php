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
	 * @var array<int, array{filter: string, args: array, locale: string}>
	 */
	private array $queue = array();

	/**
	 * Whether the shutdown hook has been registered.
	 *
	 * @var bool
	 */
	private bool $shutdown_registered = false;

	/**
	 * The locale of the queued email being sent, empty when it was queued without one.
	 *
	 * @var string
	 */
	private string $sending_locale = '';

	/**
	 * Object IDs of the emails switched to the queued locale, most recent last.
	 *
	 * @var int[]
	 */
	private array $switched_emails = array();

	/**
	 * Initialize hooks.
	 *
	 * @internal
	 */
	final public function init(): void { // phpcs:ignore Generic.CodeAnalysis.UnnecessaryFinalModifier.Found
		// Registered unconditionally so previously-scheduled AS jobs can still
		// be processed even if the feature is later disabled.
		add_action( self::AS_HOOK, array( $this, 'send_queued_transactional_email' ), 10, 3 );
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
			'locale' => determine_locale(),
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
				array( $item['filter'], $item['args'], $item['locale'] ),
				self::AS_GROUP
			);
		}

		$this->queue               = array();
		$this->shutdown_registered = false;
	}

	/**
	 * Process a single queued transactional email from Action Scheduler.
	 *
	 * Emails that are not sent to the customer use the locale of the request that queued them, as
	 * they would without deferral. The request running the queue can be in another locale, e.g. an
	 * async runner started from an admin's dashboard runs with that admin's profile language.
	 *
	 * @internal
	 *
	 * @param mixed $filter The action hook name.
	 * @param mixed $args   The arguments for the email callback.
	 * @param mixed $locale The locale the email was queued in (since 11.3.0); empty for emails queued before.
	 */
	public function send_queued_transactional_email( $filter, $args, $locale = '' ): void {
		if ( ! is_string( $filter ) || ! is_array( $args ) ) {
			return;
		}

		$args = $this->restore_args_from_queue( $args );
		if ( null === $args ) {
			return;
		}

		if ( ! is_string( $locale ) || '' === $locale ) {
			\WC_Emails::send_queued_transactional_email( $filter, $args );
			return;
		}

		// Switch per email rather than for the whole action: customer emails switch to the site locale
		// through get_locale(), which would return the queued locale while it is switched in.
		$this->sending_locale = $locale;
		add_filter( 'woocommerce_allow_switching_email_locale', array( $this, 'switch_email_to_queued_locale' ), PHP_INT_MAX, 2 );
		add_filter( 'woocommerce_allow_restoring_email_locale', array( $this, 'restore_email_from_queued_locale' ), PHP_INT_MAX, 2 );

		try {
			\WC_Emails::send_queued_transactional_email( $filter, $args );
		} finally {
			remove_filter( 'woocommerce_allow_switching_email_locale', array( $this, 'switch_email_to_queued_locale' ), PHP_INT_MAX );
			remove_filter( 'woocommerce_allow_restoring_email_locale', array( $this, 'restore_email_from_queued_locale' ), PHP_INT_MAX );

			// An email that threw while sending never reached restore_locale().
			while ( array() !== $this->switched_emails ) {
				array_pop( $this->switched_emails );
				$this->restore_runner_locale();
			}
			$this->sending_locale = '';
		}
	}

	/**
	 * Switch an email that is not sent to the customer to the queued locale, from WC_Email::setup_locale().
	 *
	 * Hooked last, so an email whose locale switching was turned off, e.g. by a translation plugin, is left alone.
	 *
	 * @internal
	 *
	 * @param mixed $allow Whether the email may switch its locale.
	 * @param mixed $email The email being set up.
	 * @return mixed The unchanged $allow.
	 */
	public function switch_email_to_queued_locale( $allow, $email ) {
		if ( ! $allow || ! $email instanceof \WC_Email || $email->is_customer_email() ) {
			return $allow;
		}

		if ( switch_to_locale( $this->sending_locale ) ) {
			$this->switched_emails[] = spl_object_id( $email );
			\WC()->load_plugin_textdomain();
		}

		return $allow;
	}

	/**
	 * Restore the runner's locale after an email switched by switch_email_to_queued_locale(), from WC_Email::restore_locale().
	 *
	 * @internal
	 *
	 * @param mixed $allow Whether the email may restore its locale.
	 * @param mixed $email The email being restored.
	 * @return mixed The unchanged $allow.
	 */
	public function restore_email_from_queued_locale( $allow, $email ) {
		if ( is_object( $email ) && end( $this->switched_emails ) === spl_object_id( $email ) ) {
			array_pop( $this->switched_emails );
			$this->restore_runner_locale();
		}

		return $allow;
	}

	/**
	 * Undo one switch to the queued locale and reload WooCommerce translations for the runner's locale.
	 */
	private function restore_runner_locale(): void {
		restore_previous_locale();
		\WC()->load_plugin_textdomain();
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
