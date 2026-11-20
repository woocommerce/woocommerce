<?php
/**
 * Order refund create ability class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\OrderAbilityTrait;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\OrderRefundAbilityTrait;
use Automattic\WooCommerce\Internal\RestApi\Refunds\DataUtils;
use Automattic\WooCommerce\Utilities\NumberUtil;

defined( 'ABSPATH' ) || exit;

/**
 * Refunds an order with wc_create_refund(), as the admin screen and the REST API do, after checking that the
 * call does not repeat a recent refund.
 */
class OrderRefundCreate extends AbstractDomainAbility implements AbilityDefinition {

	use OrderAbilityTrait;
	use OrderRefundAbilityTrait;

	/**
	 * Seconds for which a refund blocks the same refund of the order, so a call retried after a timeout cannot refund twice.
	 */
	private const DUPLICATE_WINDOW = 10 * MINUTE_IN_SECONDS;

	/**
	 * Refund meta with the fingerprint of the call that created the refund.
	 */
	private const FINGERPRINT_META_KEY = '_woocommerce_ability_refund_fingerprint';

	/**
	 * Refund meta with the fingerprint of the amount and lines the call resolved to, so the same refund sent in another form matches.
	 */
	private const RESOLVED_FINGERPRINT_META_KEY = '_woocommerce_ability_refund_resolved_fingerprint';

	/**
	 * Refund meta set when the call asked the gateway to refund the payment.
	 */
	private const PAYMENT_REQUESTED_META_KEY = '_woocommerce_ability_refund_payment_requested';

	/**
	 * Option that locks the refunds of one order, followed by the order ID.
	 */
	private const LOCK_OPTION_PREFIX = 'woocommerce_ability_order_refund_lock_';

	/**
	 * Seconds after which a lock is stale, longer than a gateway call takes, so a crashed request cannot block the order.
	 */
	private const LOCK_TTL = 5 * MINUTE_IN_SECONDS;

	/**
	 * Get the ability name.
	 *
	 * @return string
	 *
	 * @since 11.4.0
	 */
	public static function get_name(): string {
		return 'woocommerce/order-refund-create';
	}

	/**
	 * Get the ability registration arguments.
	 *
	 * @return array
	 *
	 * @since 11.4.0
	 */
	public static function get_registration_args(): array {
		return array(
			'label'               => __( 'Refund order', 'woocommerce' ),
			'description'         => __(
				'Refund an amount or lines of an order. With refund_payment, the payment gateway sends the money back; without it, the refund is only recorded and the money is returned outside WooCommerce.',
				'woocommerce'
			),
			'category'            => 'woocommerce',
			'input_schema'        => self::build_input_schema(),
			'output_schema'       => self::build_output_schema(),
			'execute_callback'    => array( __CLASS__, 'execute' ),
			'permission_callback' => array( __CLASS__, 'can_refund_order' ),
			'meta'                => array(
				'show_in_rest' => true,
				'mcp'          => array(
					'public' => true,
					'type'   => 'tool',
				),
				'annotations'  => array(
					'readonly'    => false,
					'idempotent'  => false,
					'destructive' => true,
				),
			),
		);
	}

	/**
	 * Refund the order while holding its lock, so a second call cannot pass the duplicate and amount checks
	 * before the first refund is saved.
	 *
	 * @param array $input Ability input.
	 * @return array|\WP_Error
	 *
	 * @since 11.4.0
	 */
	public static function execute( array $input ) {
		$order_id = $input['id'];
		$lock     = self::lock_order( $order_id );
		if ( false === $lock ) {
			return new \WP_Error(
				'woocommerce_ability_order_refund_lock_failed',
				__( 'The order could not be locked for the refund because of a database error, so no refund was created. It is safe to try again.', 'woocommerce' ),
				array( 'status' => 500 )
			);
		}
		if ( null === $lock ) {
			return new \WP_Error(
				'woocommerce_ability_order_refund_in_progress',
				__( 'Another refund of this order is in progress. List the refunds of the order with woocommerce/order-refunds-query before you refund it again.', 'woocommerce' ),
				array( 'status' => 409 )
			);
		}

		try {
			$order = self::get_order_from_input( $input );
			if ( is_wp_error( $order ) ) {
				return $order;
			}

			return self::refund( $order, $input, $lock );
		} finally {
			self::unlock_order( $order_id, $lock );
		}
	}

	/**
	 * Check the call against recent refunds and the gateway, then create the refund.
	 *
	 * @param \WC_Order $order Order.
	 * @param array     $input Ability input.
	 * @param string    $lock  Lock value of the call.
	 * @return array|\WP_Error
	 */
	private static function refund( \WC_Order $order, array $input, string $lock ) {
		$decimals       = wc_get_price_decimals();
		$refund_payment = $input['refund_payment'] ?? false;

		$allowed_id    = $input['allow_duplicate_of'] ?? 0;
		$allowed_found = false;
		$fingerprint   = self::fingerprint( $order, $input, $decimals );
		$duplicate     = self::check_duplicate( $order, self::FINGERPRINT_META_KEY, $fingerprint, $allowed_id, $allowed_found );
		if ( null !== $duplicate ) {
			return $duplicate;
		}

		$prepared = wc_get_container()->get( DataUtils::class )->prepare_refund_creation_or_error(
			$order,
			$input['line_items'] ?? array(),
			isset( $input['amount'] ),
			isset( $input['amount'] ) ? NumberUtil::round( (float) $input['amount'], $decimals ) : null,
			'woocommerce-abilities'
		);
		if ( is_wp_error( $prepared ) ) {
			return self::refund_error( $prepared );
		}

		$amount               = NumberUtil::round( (float) $prepared['amount'], $decimals );
		$resolved_fingerprint = self::fingerprint(
			$order,
			array(
				'amount'         => $amount,
				'line_items'     => array_map(
					static fn( array $line ): array => array(
						'line_item_id' => $line['line_item_id'],
						'refund_total' => $line['refund_total'] ?? null,
					),
					$prepared['schema_line_items']
				),
				'refund_payment' => $refund_payment,
			),
			$decimals
		);
		$duplicate            = self::check_duplicate( $order, self::RESOLVED_FINGERPRINT_META_KEY, $resolved_fingerprint, $allowed_id, $allowed_found );
		if ( null !== $duplicate ) {
			return $duplicate;
		}

		if ( $allowed_id > 0 && ! $allowed_found ) {
			return new \WP_Error(
				'woocommerce_ability_order_refund_duplicate_mismatch',
				sprintf(
					/* translators: %d: refund ID. */
					__( 'Refund %d is not a recent refund of this order with the same amount and lines, so no refund was created. Send allow_duplicate_of only with the refund ID that a duplicate error returned, and the same input as that call.', 'woocommerce' ),
					$allowed_id
				),
				array( 'status' => 400 )
			);
		}

		if ( $refund_payment && ! self::gateway_can_refund( $order ) ) {
			return new \WP_Error(
				'woocommerce_ability_order_refund_gateway_unsupported',
				__( 'The payment gateway of this order cannot refund it automatically. Send refund_payment as false to record a manual refund, and return the money outside WooCommerce.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$order_id  = $order->get_id();
		$tagged    = null;
		$saved     = false;
		$paid      = false;
		$lock_lost = false;

		// First, so the meta is on the refund before callbacks that move money see it, and a lost lock declines it before they run.
		$tag = static function ( $refund ) use ( $order_id, $lock, $fingerprint, $resolved_fingerprint, $refund_payment, &$tagged, &$lock_lost ): void {
			if ( null !== $tagged || ! $refund instanceof \WC_Order_Refund || $refund->get_parent_id() !== $order_id ) {
				return;
			}
			$tagged = $refund;
			if ( ! self::holds_lock( $order_id, $lock ) ) {
				$lock_lost = true;
				throw new \Exception( 'The refund lock of the order was taken over.' );
			}
			$refund->update_meta_data( self::FINGERPRINT_META_KEY, $fingerprint );
			$refund->update_meta_data( self::RESOLVED_FINGERPRINT_META_KEY, $resolved_fingerprint );
			if ( $refund_payment ) {
				$refund->update_meta_data( self::PAYMENT_REQUESTED_META_KEY, 'yes' );
			}
		};
		// wc_create_refund() sets the paid flag only after the gateway refunded, so a save that carries it means the money moved.
		$watch_paid = static function ( $refund ) use ( &$tagged, &$paid ): void {
			$paid = $paid || ( null !== $tagged && $refund === $tagged && $refund->get_refunded_payment() );
		};
		$watch_save = static function ( $refund ) use ( &$tagged, &$saved ): void {
			$saved = $saved || ( null !== $tagged && $refund === $tagged );
		};

		add_action( 'woocommerce_create_refund', $tag, PHP_INT_MIN );
		add_action( 'woocommerce_before_order_refund_object_save', $watch_paid, PHP_INT_MIN );
		add_action( 'woocommerce_after_order_refund_object_save', $watch_save, PHP_INT_MIN );
		$crashed = false;
		try {
			$result = wc_create_refund(
				array(
					'amount'         => $amount,
					'reason'         => isset( $input['reason'] ) ? sanitize_textarea_field( $input['reason'] ) : null,
					'order_id'       => $order_id,
					'line_items'     => $prepared['line_items'],
					'refund_payment' => $refund_payment,
					'restock_items'  => $input['restock_items'] ?? false,
				)
			);
		} catch ( \Error $e ) {
			// wc_create_refund() catches only exceptions, so delete the half-made refund as it would. A callback that crashed may have moved money.
			$crashed = true;
			$result  = new \WP_Error( 'error', $e->getMessage() );
			if ( null !== $tagged && $tagged->get_id() > 0 ) {
				$tagged->delete( true );
			}
		} finally {
			remove_action( 'woocommerce_create_refund', $tag, PHP_INT_MIN );
			remove_action( 'woocommerce_before_order_refund_object_save', $watch_paid, PHP_INT_MIN );
			remove_action( 'woocommerce_after_order_refund_object_save', $watch_save, PHP_INT_MIN );
		}

		if ( $result instanceof \WC_Order_Refund ) {
			// An order save stores its exception instead of throwing, so wc_create_refund() goes on after a failed save.
			$save_error = $result->get_last_save_error();
			if ( null !== $save_error ) {
				/* translators: 1: refund ID, 2: error message. */
				$message = __( 'Refund %1$d was created, but it could not be saved in full: %2$s Do not refund again. Check the order instead.', 'woocommerce' );
				if ( $paid ) {
					/* translators: 1: refund ID, 2: error message. */
					$message = __( 'The payment gateway refunded the payment and refund %1$d was created, but it could not be saved in full: %2$s Do not refund again. Check the order instead.', 'woocommerce' );
				}

				return new \WP_Error(
					'woocommerce_ability_order_refund_incomplete',
					sprintf( $message, $result->get_id(), $save_error->getMessage() ),
					array(
						'status'           => 500,
						'refund_id'        => $result->get_id(),
						'refunded_payment' => $paid,
					)
				);
			}

			$formatted = self::format_refund_for_response( $result );

			return is_wp_error( $formatted ) ? $formatted : array( 'refund' => $formatted );
		}

		return self::create_error( $result, $order_id, $lock_lost, $saved || $crashed, $paid, $refund_payment && ! $crashed );
	}

	/**
	 * The error of a wc_create_refund() call that failed. It deletes the refund on any failure, also after the
	 * gateway or a refund callback moved the money, so the error says when a retry could refund twice.
	 *
	 * @param mixed $result         Result of wc_create_refund().
	 * @param int   $order_id       Order ID.
	 * @param bool  $lock_lost      Whether the call declined the refund because another call took its lock over.
	 * @param bool  $saved          Whether the refund was saved after woocommerce_create_refund, or a callback crashed.
	 * @param bool  $paid           Whether the gateway refunded the payment.
	 * @param bool  $refund_payment Whether the call asked the gateway to refund the payment.
	 * @return \WP_Error
	 */
	private static function create_error( $result, int $order_id, bool $lock_lost, bool $saved, bool $paid, bool $refund_payment ): \WP_Error {
		$reason = is_wp_error( $result ) ? $result->get_error_message() : __( 'The refund could not be created.', 'woocommerce' );

		if ( $lock_lost ) {
			return new \WP_Error(
				'woocommerce_ability_order_refund_lock_lost',
				__( 'This refund took too long, and another refund of the order may have started, so no refund was created. List the refunds of the order with woocommerce/order-refunds-query before you refund it again.', 'woocommerce' ),
				array( 'status' => 409 )
			);
		}

		if ( ! $saved ) {
			return new \WP_Error( 'woocommerce_ability_order_refund_declined', $reason, array( 'status' => 400 ) );
		}

		if ( $refund_payment && ! $paid ) {
			return new \WP_Error(
				'woocommerce_ability_order_refund_gateway_failed',
				/* translators: %s: error message of the payment gateway. */
				sprintf( __( 'The payment gateway did not refund the payment, so no refund was created: %s', 'woocommerce' ), $reason ),
				array( 'status' => 502 )
			);
		}

		wc_get_logger()->error(
			sprintf( 'A refund of order %1$d was deleted after it was saved: %2$s', $order_id, $reason ),
			array( 'source' => 'woocommerce-refunds' )
		);

		/* translators: %s: error message. */
		$message = __( 'The refund failed after extensions ran their refund callbacks, so it may not have been recorded, and an extension may already have refunded the payment: %s Do not refund again. Check with the merchant and the payment gateway whether the money was returned, and record the refund manually if it was.', 'woocommerce' );
		if ( $paid ) {
			/* translators: %s: error message. */
			$message = __( 'The payment gateway refunded the payment, but a later step failed and WooCommerce deleted the refund, so the order does not show it: %s Do not refund again. Record the refund manually.', 'woocommerce' );
		}

		return new \WP_Error(
			'woocommerce_ability_order_refund_lost',
			sprintf( $message, $reason ),
			array(
				'status'   => 500,
				'order_id' => $order_id,
			)
		);
	}

	/**
	 * Hash of the call input with the lines sorted by ID, taken before the refund is checked, so a retry of a
	 * refund that used up the order still matches it.
	 *
	 * @param \WC_Order $order    Order.
	 * @param array     $input    Ability input.
	 * @param int       $decimals Price decimals.
	 * @return string
	 */
	private static function fingerprint( \WC_Order $order, array $input, int $decimals ): string {
		$lines = array();
		foreach ( $input['line_items'] ?? array() as $line ) {
			$lines[] = array(
				(int) $line['line_item_id'],
				isset( $line['quantity'] ) ? (int) $line['quantity'] : null,
				isset( $line['refund_total'] ) ? wc_format_decimal( NumberUtil::round( (float) $line['refund_total'], $decimals ), $decimals ) : null,
			);
		}
		sort( $lines );

		return md5(
			(string) wp_json_encode(
				array(
					$order->get_id(),
					isset( $input['amount'] ) ? wc_format_decimal( NumberUtil::round( (float) $input['amount'], $decimals ), $decimals ) : null,
					$lines,
					(bool) ( $input['refund_payment'] ?? false ),
					get_current_user_id(),
				)
			)
		);
	}

	/**
	 * The duplicate error for the newest refund that blocks the call. The refund that allow_duplicate_of names
	 * also lets through the older ones it repeats, so each repeat only needs the ID of the last one, but never a
	 * newer refund, which a retry of the call would have made, or one kept without its payment refunded.
	 *
	 * @param \WC_Order $order         Order.
	 * @param string    $meta_key      Meta key of the fingerprint.
	 * @param string    $fingerprint   Fingerprint of the call.
	 * @param int       $allowed_id    Refund ID from allow_duplicate_of, or 0.
	 * @param bool      $allowed_found Set to true when the refund that allow_duplicate_of names matched.
	 * @return \WP_Error|null
	 */
	private static function check_duplicate( \WC_Order $order, string $meta_key, string $fingerprint, int $allowed_id, bool &$allowed_found ): ?\WP_Error {
		foreach ( self::find_recent_refunds( $order, $meta_key, $fingerprint ) as $refund ) {
			if ( $refund->get_id() > $allowed_id || self::is_unsettled( $refund ) ) {
				return self::duplicate_error( $refund );
			}

			if ( $refund->get_id() === $allowed_id ) {
				$allowed_found = true;
			}
		}

		return null;
	}

	/**
	 * Refunds of the order with the same fingerprint, newest first, created within the duplicate window or, at any
	 * age, kept without their payment refunded, since a retry would then record the refund twice.
	 *
	 * @param \WC_Order $order       Order.
	 * @param string    $meta_key    Meta key of the fingerprint.
	 * @param string    $fingerprint Fingerprint of the call.
	 * @return \WC_Order_Refund[]
	 */
	private static function find_recent_refunds( \WC_Order $order, string $meta_key, string $fingerprint ): array {
		$refund_ids = wc_get_orders(
			array(
				'type'    => 'shop_order_refund',
				'parent'  => $order->get_id(),
				'limit'   => -1,
				'orderby' => 'ID',
				'order'   => 'DESC',
				'return'  => 'ids',
			)
		);

		$found = array();
		foreach ( is_array( $refund_ids ) ? $refund_ids : array() as $refund_id ) {
			$refund = wc_get_order( $refund_id );
			if ( ! $refund instanceof \WC_Order_Refund || $refund->get_meta( $meta_key ) !== $fingerprint ) {
				continue;
			}
			$created = $refund->get_date_created();
			if ( self::is_unsettled( $refund ) || ( $created && $created->getTimestamp() > time() - self::DUPLICATE_WINDOW ) ) {
				$found[] = $refund;
			}
		}

		return $found;
	}

	/**
	 * Whether the call that created the refund asked for the payment to be refunded and the refund is not marked
	 * as paid. A failed call deletes its refund, so only a refund whose paid flag failed to save matches.
	 *
	 * @param \WC_Order_Refund $refund Refund.
	 * @return bool
	 */
	private static function is_unsettled( \WC_Order_Refund $refund ): bool {
		return 'yes' === $refund->get_meta( self::PAYMENT_REQUESTED_META_KEY ) && ! $refund->get_refunded_payment();
	}

	/**
	 * The refusal of a refund that repeats a recent one, with that refund, so a client that retried learns it was made.
	 *
	 * @param \WC_Order_Refund $refund Refund that the call repeats.
	 * @return \WP_Error
	 */
	private static function duplicate_error( \WC_Order_Refund $refund ): \WP_Error {
		$created   = $refund->get_date_created();
		$created   = $created ? $created->getTimestamp() : time();
		$formatted = self::format_refund_for_response( $refund );

		if ( self::is_unsettled( $refund ) ) {
			$message = sprintf(
				/* translators: 1: refund ID, 2: time since the refund, such as "2 mins". */
				__( 'Refund %1$d recorded the same refund of this order %2$s ago, so no new refund was created. The payment gateway was asked to refund the payment, but its result was not saved, so it may have refunded the payment. Do not refund again. Check the payment with the merchant and the payment gateway.', 'woocommerce' ),
				$refund->get_id(),
				human_time_diff( $created )
			);
		} else {
			$message = sprintf(
				/* translators: 1: refund ID, 2: time since the refund, such as "2 mins". */
				__( 'Refund %1$d refunded the same amount and lines of this order %2$s ago, so no new refund was created. If you mean to make the same refund again, send the same input with allow_duplicate_of set to %1$d.', 'woocommerce' ),
				$refund->get_id(),
				human_time_diff( $created )
			);
		}

		return new \WP_Error(
			'woocommerce_ability_order_refund_duplicate',
			$message,
			array(
				'status' => 409,
				'refund' => is_wp_error( $formatted ) ? array( 'id' => $refund->get_id() ) : $formatted,
			)
		);
	}

	/**
	 * Lock the refunds of an order with an option row, as WC_Install::create_lock() does, taking over a stale lock.
	 *
	 * @param int $order_id Order ID.
	 * @return string|false|null The lock value to release it with, null when another call holds the lock, or false on a database error.
	 */
	private static function lock_order( int $order_id ) {
		global $wpdb;

		$name    = self::LOCK_OPTION_PREFIX . $order_id;
		$expires = time() + self::LOCK_TTL;

		// INSERT IGNORE affects no rows while another call holds the lock, so contention is not logged as a DB error.
		$locked = $wpdb->query(
			$wpdb->prepare( "INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %d, 'no')", $name, $expires )
		);
		if ( 0 === $locked ) {
			$locked = $wpdb->query(
				$wpdb->prepare( "UPDATE {$wpdb->options} SET option_value = %d WHERE option_name = %s AND option_value < %d", $expires, $name, time() )
			);
		}
		if ( false === $locked ) {
			return false;
		}

		return $locked ? (string) $expires : null;
	}

	/**
	 * Whether the lock row of the order still holds the value of this call.
	 *
	 * @param int    $order_id Order ID.
	 * @param string $lock     Value that lock_order() returned.
	 * @return bool
	 */
	private static function holds_lock( int $order_id, string $lock ): bool {
		global $wpdb;

		return $wpdb->get_var(
			$wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", self::LOCK_OPTION_PREFIX . $order_id )
		) === $lock;
	}

	/**
	 * Release the lock of an order, unless another call took it over as stale.
	 *
	 * @param int    $order_id Order ID.
	 * @param string $lock     Value that lock_order() returned.
	 */
	private static function unlock_order( int $order_id, string $lock ): void {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s", self::LOCK_OPTION_PREFIX . $order_id, $lock )
		);
	}

	/**
	 * Whether the payment gateway of the order can send the money back.
	 *
	 * @param \WC_Order $order Order.
	 * @return bool
	 */
	private static function gateway_can_refund( \WC_Order $order ): bool {
		$gateway = wc_get_payment_gateway_by_order( $order );

		return $gateway instanceof \WC_Payment_Gateway && $gateway->can_refund_order( $order );
	}

	/**
	 * Name an error of the refund engine as an ability error, such as `refund_exceeds_remaining` as
	 * `woocommerce_ability_order_refund_exceeds_remaining`. The message and data stay.
	 *
	 * @param \WP_Error $error Error with an unprefixed code.
	 * @return \WP_Error
	 */
	private static function refund_error( \WP_Error $error ): \WP_Error {
		$reason = implode( '_', array_diff( explode( '_', (string) $error->get_error_code() ), array( 'refund' ) ) );
		$data   = $error->get_error_data();

		return new \WP_Error(
			'woocommerce_ability_order_refund_' . $reason,
			$error->get_error_message(),
			is_array( $data ) ? $data : array( 'status' => 400 )
		);
	}

	/**
	 * Get the ability input schema.
	 *
	 * @return array
	 */
	private static function build_input_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'id'                 => array(
					'type'        => 'integer',
					'description' => __( 'Order ID.', 'woocommerce' ),
					'minimum'     => 1,
				),
				'amount'             => array(
					'type'        => 'string',
					'pattern'     => '^[0-9]+(\.[0-9]+)?$',
					'description' => __( 'Amount to refund, including tax, such as 12.50. Without line_items, it refunds this amount of the order. With line_items, it defaults to their total and cannot be lower.', 'woocommerce' ),
				),
				'line_items'         => array(
					'type'        => 'array',
					'description' => __( 'Order lines to refund. Send quantity to refund whole units, or refund_total for a tax-inclusive amount of the line.', 'woocommerce' ),
					'minItems'    => 1,
					'items'       => array(
						'type'                 => 'object',
						'properties'           => array(
							'line_item_id' => array(
								'type'        => 'integer',
								'description' => __( 'ID of the order line, from the woocommerce/orders-query ability with include_line_items and include_lines.', 'woocommerce' ),
								'minimum'     => 1,
							),
							'quantity'     => array(
								'type'    => 'integer',
								'minimum' => 1,
							),
							'refund_total' => array(
								'type'        => 'number',
								'description' => __( 'Tax-inclusive amount to refund for the line. Negative only for a discount or credit line.', 'woocommerce' ),
							),
						),
						'required'             => array( 'line_item_id' ),
						'additionalProperties' => false,
					),
				),
				'reason'             => array( 'type' => 'string' ),
				'refund_payment'     => array(
					'type'        => 'boolean',
					'description' => __( 'Whether the payment gateway sends the money back.', 'woocommerce' ),
					'default'     => false,
				),
				'restock_items'      => array(
					'type'        => 'boolean',
					'description' => __( 'Whether to return the refunded quantities of the product lines to stock.', 'woocommerce' ),
					'default'     => false,
				),
				'allow_duplicate_of' => array(
					'type'        => 'integer',
					'description' => __( 'ID of the refund that a duplicate error returned. Send it, with the same amount and lines, only when you mean to make that refund again.', 'woocommerce' ),
					'minimum'     => 1,
				),
			),
			'required'             => array( 'id' ),
			'additionalProperties' => false,
		);
	}

	/**
	 * Get the ability output schema.
	 *
	 * @return array
	 */
	private static function build_output_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'refund' => self::get_refund_output_schema(),
			),
			'additionalProperties' => false,
		);
	}
}
