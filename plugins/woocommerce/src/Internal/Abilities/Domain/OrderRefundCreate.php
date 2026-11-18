<?php
/**
 * Order refund create ability class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\ActionableAbility;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\DomainAbilityHelpers;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\OrderAbilityTrait;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\OrderRefundAbilityTrait;
use Automattic\WooCommerce\Internal\Orders\RefundCreator;
use Automattic\WooCommerce\Internal\RestApi\Refunds\DataUtils;
use Automattic\WooCommerce\Internal\Utilities\DatabaseTransaction;
use Automattic\WooCommerce\Utilities\NumberUtil;

defined( 'ABSPATH' ) || exit;

/**
 * Refunds an order. The refund is checked and staged in memory, only the refund row is saved in the
 * transaction, and the payment gateway, restock and order status change run after the commit.
 */
class OrderRefundCreate extends ActionableAbility {

	use DomainAbilityHelpers;
	use OrderAbilityTrait;
	use OrderRefundAbilityTrait;

	/**
	 * Ability name.
	 */
	public const ABILITY_NAME = 'woocommerce/order-refund-create';

	/**
	 * Seconds for which a refund blocks the same refund of the order, so a call retried after a timeout cannot refund twice.
	 */
	private const DUPLICATE_WINDOW = 10 * MINUTE_IN_SECONDS;

	/**
	 * Refund meta with the fingerprint of the call that created the refund.
	 */
	private const FINGERPRINT_META_KEY = '_woocommerce_ability_refund_fingerprint';

	/**
	 * Option that locks the refunds of one order, followed by the order ID.
	 */
	private const LOCK_OPTION_PREFIX = 'woocommerce_ability_order_refund_lock_';

	/**
	 * Seconds after which a lock is stale, longer than a gateway call takes, so a crashed request cannot block the order.
	 */
	private const LOCK_TTL = 5 * MINUTE_IN_SECONDS;

	/**
	 * Order of the current call, set by load().
	 *
	 * @var \WC_Order
	 */
	private \WC_Order $order;

	/**
	 * Creator of the current call, set by change().
	 *
	 * @var RefundCreator
	 */
	private RefundCreator $creator;

	/**
	 * Lock value of the current call, set by execute_change().
	 *
	 * @var string|null
	 */
	private ?string $lock = null;

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
			'permission_callback' => array( __CLASS__, 'can_edit_order' ),
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
	 * Resource that the ability changes.
	 *
	 * @return string
	 */
	public function get_resource(): string {
		return 'order_refund';
	}

	/**
	 * Load the order, and return the new refund that change() fills.
	 *
	 * @param array $input Ability input.
	 * @return \WC_Order_Refund|\WP_Error
	 */
	public function load( array $input ) {
		$order = self::get_order_from_input( $input );
		if ( is_wp_error( $order ) ) {
			return $order;
		}

		$this->order = $order;

		return new \WC_Order_Refund();
	}

	/**
	 * Check the refund against the order and the gateway, then stage it in memory.
	 *
	 * @param \WC_Order_Refund $subject New refund.
	 * @param array            $input   Ability input.
	 * @return null|\WP_Error
	 */
	public function change( $subject, array $input ) {
		$order          = $this->order;
		$decimals       = wc_get_price_decimals();
		$refund_payment = $input['refund_payment'] ?? false;

		$fingerprint = self::fingerprint( $order, $input, $decimals );
		$duplicate   = self::find_recent_refund( $order, $fingerprint );
		if ( null !== $duplicate ) {
			return self::duplicate_error( $duplicate );
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

		$amount = NumberUtil::round( (float) $prepared['amount'], $decimals );
		if ( $refund_payment && ! self::gateway_can_refund( $order ) ) {
			return new \WP_Error(
				'woocommerce_ability_order_refund_gateway_unsupported',
				__( 'The payment gateway of this order cannot refund it automatically. Send refund_payment as false to record a manual refund, and return the money outside WooCommerce.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$this->creator = new RefundCreator(
			$order,
			array(
				'amount'         => $amount,
				'reason'         => isset( $input['reason'] ) ? sanitize_textarea_field( $input['reason'] ) : null,
				'line_items'     => $prepared['line_items'],
				'refund_payment' => $refund_payment,
				'restock_items'  => $input['restock_items'] ?? false,
			)
		);

		$staged = $this->creator->stage( $subject );
		if ( is_wp_error( $staged ) ) {
			return self::refund_error( $staged );
		}

		$subject->update_meta_data( self::FINGERPRINT_META_KEY, $fingerprint );

		return null;
	}

	/**
	 * Lock the refunds of the order for the call, so a second call cannot pass the duplicate and amount checks
	 * before the first refund is saved. The state of an outer call, such as a hook that refunds another order
	 * through this ability, is restored at the end.
	 *
	 * @internal
	 *
	 * @param mixed $input Ability input.
	 * @return mixed|\WP_Error
	 */
	public function execute_change( $input = null ) {
		if ( ! is_array( $input ) ) {
			return parent::execute_change( $input );
		}

		if ( DatabaseTransaction::is_caller_open() ) {
			return new \WP_Error(
				'woocommerce_ability_order_refund_in_transaction',
				__( 'An order cannot be refunded inside an open database transaction, as a rollback would lose the refund after the payment was returned. Call this ability outside the transaction.', 'woocommerce' ),
				array( 'status' => 500 )
			);
		}

		$outer_order   = $this->order ?? null;
		$outer_creator = $this->creator ?? null;
		$outer_lock    = $this->lock;

		$order_id = $input['id'];
		$lock     = self::lock_order( $order_id );
		if ( null === $lock ) {
			return new \WP_Error(
				'woocommerce_ability_order_refund_in_progress',
				__( 'Another refund of this order is in progress. List the refunds of the order with woocommerce/order-refunds-query before you refund it again.', 'woocommerce' ),
				array( 'status' => 409 )
			);
		}
		$this->lock = $lock;

		try {
			return parent::execute_change( $input );
		} finally {
			self::unlock_order( $order_id, $lock );
			$this->lock = $outer_lock;
			if ( null !== $outer_order ) {
				$this->order = $outer_order;
			}
			if ( null !== $outer_creator ) {
				$this->creator = $outer_creator;
			}
		}
	}

	/**
	 * Fire woocommerce_create_refund only now, after the validators, as some callbacks refund the payment
	 * through their own gateway there.
	 *
	 * @param \WC_Order_Refund $subject Staged refund.
	 * @return null|\WP_Error
	 */
	protected function save( $subject ) {
		if ( ! $this->holds_lock() ) {
			return new \WP_Error(
				'woocommerce_ability_order_refund_lock_lost',
				__( 'This refund took too long, and another refund of the order may have started, so no refund was created. List the refunds of the order with woocommerce/order-refunds-query before you refund it again.', 'woocommerce' ),
				array( 'status' => 409 )
			);
		}

		$declined = $this->creator->before_save( $subject );
		if ( is_wp_error( $declined ) ) {
			return self::refund_error( $declined );
		}

		return parent::save( $subject );
	}

	/**
	 * Refund the payment, restock and update the order, after the refund row is committed. A gateway refusal
	 * deletes the refund, so the call fails with nothing saved. A later failure keeps the refund, and its error
	 * tells the client not to refund again.
	 *
	 * @param \WC_Order_Refund $subject Saved refund.
	 * @param array            $input   Ability input.
	 * @return null|\WP_Error
	 */
	protected function after_commit( $subject, array $input ): ?\WP_Error {
		$finished = $this->creator->finish( $subject );
		if ( ! is_wp_error( $finished ) ) {
			return null;
		}

		if ( 'refund_incomplete' === $finished->get_error_code() ) {
			$message = $subject->get_refunded_payment()
				/* translators: 1: refund ID, 2: error message. */
				? __( 'The payment gateway refunded the payment and refund %1$d was created, but a later step failed, so the order may not show the refund in full: %2$s Do not refund again. Check the order instead.', 'woocommerce' )
				/* translators: 1: refund ID, 2: error message. */
				: __( 'Refund %1$d was created, but a later step failed, so the order may not show the refund in full: %2$s Do not refund again. Check the order instead.', 'woocommerce' );

			return new \WP_Error(
				'woocommerce_ability_order_refund_incomplete',
				sprintf( $message, $subject->get_id(), $finished->get_error_message() ),
				array(
					'status'           => 500,
					'refund_id'        => $subject->get_id(),
					'refunded_payment' => $subject->get_refunded_payment(),
				)
			);
		}

		return new \WP_Error(
			'woocommerce_ability_order_refund_gateway_failed',
			sprintf(
				/* translators: %s: error message of the payment gateway. */
				__( 'The payment gateway did not refund the payment, so no refund was created: %s', 'woocommerce' ),
				$finished->get_error_message()
			),
			array( 'status' => 502 )
		);
	}

	/**
	 * The ability output for the saved refund.
	 *
	 * @param \WC_Order_Refund $subject Saved refund.
	 * @return array|\WP_Error
	 */
	public function prepare_response( $subject ) {
		$formatted = self::format_refund_for_response( $subject );
		if ( is_wp_error( $formatted ) ) {
			return $formatted;
		}

		return array( 'refund' => $formatted );
	}

	/**
	 * Refunds are not editable, so a field of an extension cannot be written on one.
	 *
	 * @return bool
	 */
	protected function writes_extension_fields(): bool {
		return false;
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
	 * A refund of the order, created within the duplicate window by a call with the same fingerprint.
	 *
	 * @param \WC_Order $order       Order.
	 * @param string    $fingerprint Fingerprint of the call.
	 * @return \WC_Order_Refund|null
	 */
	private static function find_recent_refund( \WC_Order $order, string $fingerprint ): ?\WC_Order_Refund {
		$refund_ids = wc_get_orders(
			array(
				'type'         => 'shop_order_refund',
				'parent'       => $order->get_id(),
				'date_created' => '>' . ( time() - self::DUPLICATE_WINDOW ),
				'limit'        => -1,
				'return'       => 'ids',
			)
		);

		foreach ( is_array( $refund_ids ) ? $refund_ids : array() as $refund_id ) {
			$refund = wc_get_order( $refund_id );
			if ( $refund instanceof \WC_Order_Refund && $refund->get_meta( self::FINGERPRINT_META_KEY ) === $fingerprint ) {
				return $refund;
			}
		}

		return null;
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

		return new \WP_Error(
			'woocommerce_ability_order_refund_duplicate',
			sprintf(
				/* translators: 1: refund ID, 2: time since the refund, such as "2 mins", 3: time until the same refund is allowed again. */
				__( 'Refund %1$d refunded the same amount and lines of this order %2$s ago, so no new refund was created. The same refund can be created again in %3$s.', 'woocommerce' ),
				$refund->get_id(),
				human_time_diff( $created ),
				human_time_diff( time(), $created + self::DUPLICATE_WINDOW )
			),
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
	 * @return string|null The lock value to release it with, or null when another call holds the lock.
	 */
	private static function lock_order( int $order_id ): ?string {
		global $wpdb;

		$name    = self::LOCK_OPTION_PREFIX . $order_id;
		$expires = time() + self::LOCK_TTL;

		// INSERT IGNORE affects no rows while another call holds the lock, so contention is not logged as a DB error.
		$locked = $wpdb->query(
			$wpdb->prepare( "INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %d, 'no')", $name, $expires )
		);
		if ( ! $locked ) {
			$locked = $wpdb->query(
				$wpdb->prepare( "UPDATE {$wpdb->options} SET option_value = %d WHERE option_name = %s AND option_value < %d", $expires, $name, time() )
			);
		}

		return $locked ? (string) $expires : null;
	}

	/**
	 * Whether the lock row of the order still holds the value of this call. The locking read sees a takeover that
	 * committed after the transaction began, and makes a later takeover wait until this refund commits.
	 *
	 * @return bool
	 */
	private function holds_lock(): bool {
		global $wpdb;

		if ( null === $this->lock ) {
			return false;
		}

		$value = $wpdb->get_var(
			$wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s FOR UPDATE", self::LOCK_OPTION_PREFIX . $this->order->get_id() )
		);

		return $value === $this->lock;
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
				'id'             => array(
					'type'        => 'integer',
					'description' => __( 'Order ID.', 'woocommerce' ),
					'minimum'     => 1,
				),
				'amount'         => array(
					'type'        => 'string',
					'pattern'     => '^[0-9]+(\.[0-9]+)?$',
					'description' => __( 'Amount to refund, including tax, such as 12.50. Without line_items, it refunds this amount of the order. With line_items, it defaults to their total and cannot be lower.', 'woocommerce' ),
				),
				'line_items'     => array(
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
				'reason'         => array( 'type' => 'string' ),
				'refund_payment' => array(
					'type'        => 'boolean',
					'description' => __( 'Whether the payment gateway sends the money back.', 'woocommerce' ),
					'default'     => false,
				),
				'restock_items'  => array(
					'type'        => 'boolean',
					'description' => __( 'Whether to return the refunded quantities of the product lines to stock.', 'woocommerce' ),
					'default'     => false,
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
