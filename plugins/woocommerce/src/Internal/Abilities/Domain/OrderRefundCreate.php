<?php
/**
 * Order refund create ability class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\ActionableAbility;
use Automattic\WooCommerce\Abilities\ExtensibleAbilities;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\DomainAbilityHelpers;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\OrderAbilityTrait;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\OrderRefundAbilityTrait;
use Automattic\WooCommerce\Internal\RestApi\Refunds\DataUtils;
use Automattic\WooCommerce\Internal\Utilities\DatabaseTransaction;
use Automattic\WooCommerce\Utilities\NumberUtil;

defined( 'ABSPATH' ) || exit;

/**
 * Refunds an order with wc_create_refund(), as the admin screen and the REST API do, after checking that the
 * call does not repeat a recent refund. The `order_refund` fields and validators run on the refund that
 * wc_create_refund() built, before the payment gateway is called. The syncs run after it returns, outside the
 * rollback, because a rollback cannot undo a gateway refund.
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
	 * Key of the wc_create_refund() argument that carries a value unique to the call, which woocommerce_create_refund
	 * passes on with the refund, so a refund that a callback makes during the call is not taken for the call's own.
	 */
	public const CALL_ARG = 'woocommerce_ability_call';

	/**
	 * Seconds after which a lock is stale, longer than a gateway call takes, so a crashed request cannot block the order.
	 */
	private const LOCK_TTL = 5 * MINUTE_IN_SECONDS;

	/**
	 * Ability error code suffixes for the codes of the refund engine, so a new or renamed engine code does not change them.
	 */
	private const ENGINE_ERROR_CODES = array(
		'invalid_refund_request'           => 'invalid_request',
		'order_not_refundable'             => 'order_not_refundable',
		'invalid_refund_amount'            => 'invalid_amount',
		'refund_exceeds_remaining'         => 'exceeds_remaining',
		'missing_line_item_id'             => 'missing_line_item_id',
		'duplicate_line_item'              => 'duplicate_line_item',
		'line_item_not_found'              => 'line_item_not_found',
		'unsupported_item_type'            => 'unsupported_item_type',
		'invalid_line_item'                => 'invalid_line_item',
		'line_item_already_refunded'       => 'line_item_already_refunded',
		'invalid_quantity'                 => 'invalid_quantity',
		'quantity_exceeds_refundable'      => 'quantity_exceeds_refundable',
		'missing_quantity_or_refund_total' => 'missing_quantity_or_refund_total',
		'invalid_refund_total'             => 'invalid_refund_total',
		'refund_total_exceeds_line'        => 'refund_total_exceeds_line',
		'refund_total_exceeds_remaining'   => 'refund_total_exceeds_remaining',
		'duplicate_tax_id'                 => 'duplicate_tax_id',
	);

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
	 * Resource that the ability changes.
	 *
	 * @return string
	 */
	public function get_resource(): string {
		return 'order_refund';
	}

	/**
	 * Load the order to refund.
	 *
	 * @param array $input Ability input.
	 * @return \WC_Order|\WP_Error
	 */
	protected function load( array $input ) {
		return self::get_order_from_input( $input );
	}

	/**
	 * Nothing changes before the refund, because wc_create_refund() builds it. execute_change() writes the
	 * fields to that refund.
	 *
	 * @param \WC_Order $subject Order.
	 * @param array     $input   Ability input.
	 * @return null
	 */
	protected function change( $subject, array $input ) {
		return null;
	}

	/**
	 * The ability output for the refund.
	 *
	 * @param \WC_Order_Refund $subject Refund.
	 * @return array|\WP_Error
	 */
	protected function prepare_response( $subject ) {
		$formatted = self::format_refund_for_response( $subject );

		return is_wp_error( $formatted ) ? $formatted : array( 'refund' => $formatted );
	}

	/**
	 * Refund the order while holding its lock, so a second call cannot pass the duplicate and amount checks
	 * before the first refund is saved. It replaces the steps of ActionableAbility, because wc_create_refund()
	 * builds the refund, saves it and calls the payment gateway in one call.
	 *
	 * @internal
	 *
	 * @param mixed $input Ability input.
	 * @return array|\WP_Error
	 */
	public function execute_change( $input = null ) {
		$input = is_array( $input ) ? $input : array();
		if ( ! isset( $input['amount'] ) && ! isset( $input['line_items'] ) ) {
			return new \WP_Error(
				'woocommerce_ability_order_refund_missing_amount',
				__( 'No amount or lines to refund were sent, so no refund was created. Send amount, line_items, or both.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$updates = ExtensibleAbilities::is_enabled() ? $this->prepare_fields( $input['extensions'] ?? array(), $this->get_resource() ) : array();
		if ( is_wp_error( $updates ) ) {
			return $updates;
		}

		$order_id = $input['id'];
		$lock     = self::lock_order( $order_id );
		if ( false === $lock ) {
			return self::lock_failed_error();
		}
		if ( null === $lock ) {
			return new \WP_Error(
				'woocommerce_ability_order_refund_in_progress',
				__( 'Another refund of this order is in progress. List the refunds of the order with woocommerce/order-refunds-query before you refund it again.', 'woocommerce' ),
				array( 'status' => 409 )
			);
		}

		try {
			$order = $this->load( $input );
			if ( is_wp_error( $order ) ) {
				return $order;
			}

			return $this->refund( $order, $input, $lock, $updates );
		} finally {
			self::unlock_order( $order_id, $lock );
		}
	}

	/**
	 * Check the call against recent refunds and the gateway, then create the refund.
	 *
	 * @param \WC_Order $order   Order.
	 * @param array     $input   Ability input.
	 * @param string    $lock    Lock value of the call.
	 * @param array     $updates Extension field values from prepare_fields().
	 * @return array|\WP_Error
	 */
	private function refund( \WC_Order $order, array $input, string $lock, array $updates ) {
		$decimals       = wc_get_price_decimals();
		$refund_payment = $input['refund_payment'] ?? false;

		$allowed_id    = $input['allow_duplicate_of'] ?? 0;
		$allowed_found = false;
		$recent        = self::find_recent_refunds( $order );
		$fingerprint   = self::fingerprint( $order, $input, $decimals );
		$duplicate     = self::find_duplicate( $recent, self::FINGERPRINT_META_KEY, $fingerprint, $allowed_id, $allowed_found );
		if ( null !== $duplicate && self::is_unsettled( $duplicate ) ) {
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
			return null !== $duplicate ? self::duplicate_error( $duplicate ) : self::refund_error( $prepared );
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
		$duplicate            = self::blocking_refund( $duplicate, self::find_duplicate( $recent, self::RESOLVED_FINGERPRINT_META_KEY, $resolved_fingerprint, $allowed_id, $allowed_found ) );
		if ( null !== $duplicate ) {
			return self::duplicate_error( $duplicate );
		}

		if ( $allowed_id > 0 && ! $allowed_found && ! self::repeats_older_refund( $order, $allowed_id, $fingerprint, $resolved_fingerprint ) ) {
			return new \WP_Error(
				'woocommerce_ability_order_refund_duplicate_mismatch',
				sprintf(
					/* translators: %d: refund ID. */
					__( 'Refund %d is not a refund of this order with the same amount and lines, so no refund was created. Send allow_duplicate_of only with the refund ID that a duplicate error returned, and the same input as that call.', 'woocommerce' ),
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

		$order_id = $order->get_id();
		$tagged   = null;
		$saved    = false;
		$paid     = false;
		$refusal  = null;
		$enabled  = ExtensibleAbilities::is_enabled();

		/**
		 * The refund of the order that saved first during the call and has not reached woocommerce_create_refund as
		 * another call's, so the call's own until then. $watch_paid sets it, which PHPStan does not follow into $tag.
		 *
		 * @var \WC_Order_Refund|null $candidate
		 */
		$candidate = null;
		$marker    = wp_generate_uuid4();
		$reason    = isset( $input['reason'] ) ? sanitize_textarea_field( $input['reason'] ) : null;

		// First, so the meta and fields are on the refund before callbacks that move money see it, and a lost lock or a validator declines it before they run.
		$tag = function ( $refund, $args ) use ( $order_id, $lock, $marker, $fingerprint, $resolved_fingerprint, $refund_payment, $enabled, $updates, &$candidate, &$tagged, &$refusal ): void {
			if ( ! $refund instanceof \WC_Order_Refund || $refund->get_parent_id( 'edit' ) !== $order_id ) {
				return;
			}
			if ( ! is_array( $args ) || ( $args[ self::CALL_ARG ] ?? null ) !== $marker ) {
				if ( $refund === $candidate ) {
					$candidate = null;
				}
				return;
			}
			if ( null !== $tagged ) {
				return;
			}
			$tagged = $refund;
			$holds  = self::holds_lock( $order_id, $lock );
			if ( true !== $holds ) {
				$refusal = null === $holds ? self::lock_failed_error() : self::lock_lost_error();
				throw new \Exception( 'The refund lock of the order could not be confirmed.' );
			}
			self::add_fingerprints( $refund, $fingerprint, $resolved_fingerprint, $refund_payment );
			$refusal = $enabled ? $this->write_fields( $refund, $updates ) : null;
			if ( null !== $refusal ) {
				throw new \Exception( 'An extension refused the refund.' );
			}
		};
		// wc_create_refund() sets the paid flag only after the gateway refunded, so a save that carries it means the money moved.
		$watch_paid = static function ( $refund ) use ( $order_id, &$candidate, &$tagged, &$paid ): void {
			if ( null === $candidate && $refund instanceof \WC_Order_Refund && 0 === $refund->get_id() && $refund->get_parent_id( 'edit' ) === $order_id ) {
				$candidate = $refund;
			}
			$paid = $paid || ( null !== $tagged && $refund === $tagged && $refund->get_refunded_payment() );
		};
		$watch_save = static function ( $refund ) use ( &$tagged, &$saved ): void {
			$saved = $saved || ( null !== $tagged && $refund === $tagged );
		};

		add_action( 'woocommerce_create_refund', $tag, PHP_INT_MIN, 2 );
		add_action( 'woocommerce_before_order_refund_object_save', $watch_paid, PHP_INT_MIN );
		add_action( 'woocommerce_after_order_refund_object_save', $watch_save, PHP_INT_MIN );
		/**
		 * Whether a callback threw an Error. Set in the catch, which PHPStan takes for dead.
		 *
		 * @var bool $crashed
		 */
		$crashed = false;
		try {
			$result = wc_create_refund(
				array(
					'amount'         => $amount,
					'reason'         => $reason,
					'order_id'       => $order_id,
					'line_items'     => $prepared['line_items'],
					'refund_payment' => $refund_payment,
					'restock_items'  => $input['restock_items'] ?? false,
					self::CALL_ARG   => $marker,
				)
			);
		} catch ( \Error $e ) { // @phpstan-ignore catch.neverThrown (Callbacks that wc_create_refund() runs can throw an Error, which its @throws does not list.)
			// wc_create_refund() catches only exceptions, so delete the half-made refund as it would. It saves the refund before woocommerce_create_refund, so the refund can exist untagged.
			// A paid refund stays as the record of the money, so a retry finds it, and an untagged one was paid by another call.
			$crashed = true;
			$result  = new \WP_Error( 'error', $e->getMessage() );
			$made    = $tagged ?? $candidate;
			if ( null !== $made && $made->get_id() > 0 && ! $made->get_refunded_payment( 'edit' ) ) {
				$made->delete( true );
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

			$objects  = array( array( $result, $updates, $this->get_resource() ) );
			$response = $enabled ? DatabaseTransaction::run( fn() => $this->sync( $objects, $input ) ) : null;
			if ( is_wp_error( $response ) ) {
				self::forget_rolled_back_objects( $objects );
			} else {
				$response = $this->prepare_response( $result );
			}
			if ( is_wp_error( $response ) ) {
				return ExtensibleAbilities::saved_write_error( $response, $this->get_resource(), $result->get_id(), array( 'refunded_payment' => $paid ) );
			}

			return $response;
		}

		$made = $tagged ?? $candidate;
		if ( null !== $made && $made->get_id() > 0 && wc_get_order( $made->get_id() ) instanceof \WC_Order_Refund ) {
			if ( null === $tagged && $made->get_refunded_payment( 'edit' ) ) {
				return self::unconfirmed_refund_error( $made->get_id(), $result );
			}
			return self::kept_refund_error( $made->get_id(), $result, $paid );
		}

		if ( $crashed && null === $tagged ) {
			return new \WP_Error(
				'woocommerce_ability_order_refund_failed',
				/* translators: %s: error message. */
				sprintf( __( 'The refund failed before it was sent to the payment gateway, so no refund was created and no money was returned: %s The same error will likely stop a retry, so ask the merchant to check it before you refund again.', 'woocommerce' ), $result->get_error_message() ),
				array( 'status' => 500 )
			);
		}

		return self::create_error( $result, $order_id, $refusal, $saved || $crashed, $paid, $refund_payment && ! $crashed );
	}

	/**
	 * Write the extension fields to the refund that wc_create_refund() built, and run the `order_refund`
	 * validators on it. The refund is not sent to the payment gateway yet.
	 *
	 * @param \WC_Order_Refund $refund  Refund.
	 * @param array            $updates Extension field values from prepare_fields().
	 * @return \WP_Error|null Every refusal in one error, or null when none refused.
	 */
	private function write_fields( \WC_Order_Refund $refund, array $updates ): ?\WP_Error {
		$refused = new \WP_Error();
		$this->apply_fields( $refund, $updates, $refused );
		$this->validate(
			$refund,
			$this->get_resource(),
			array(
				'primary'           => $refund,
				'deleting'          => false,
				'objects'           => array( $refund ),
				'objects_to_delete' => array(),
			),
			$refused
		);

		return $refused->has_errors() ? self::join_refusals( $refused ) : null;
	}

	/**
	 * Mark a refund with the fingerprints of the call, and as asked to refund the payment when it was. Not saved.
	 *
	 * @param \WC_Order_Refund $refund               Refund.
	 * @param string           $fingerprint          Fingerprint of the call input.
	 * @param string           $resolved_fingerprint Fingerprint of the amount and lines the call resolved to.
	 * @param bool             $refund_payment       Whether the call asked the gateway to refund the payment.
	 */
	private static function add_fingerprints( \WC_Order_Refund $refund, string $fingerprint, string $resolved_fingerprint, bool $refund_payment ): void {
		$refund->update_meta_data( self::FINGERPRINT_META_KEY, $fingerprint );
		$refund->update_meta_data( self::RESOLVED_FINGERPRINT_META_KEY, $resolved_fingerprint );
		if ( $refund_payment ) {
			$refund->update_meta_data( self::PAYMENT_REQUESTED_META_KEY, 'yes' );
		}
	}

	/**
	 * The error of a call that failed before its refund reached the payment gateway, while a paid refund of the order
	 * that it cannot tell for its own was saved during the call. That refund is left as it is.
	 *
	 * @param int   $refund_id ID of the paid refund.
	 * @param mixed $result    Result of wc_create_refund().
	 * @return \WP_Error
	 */
	private static function unconfirmed_refund_error( int $refund_id, $result ): \WP_Error {
		return new \WP_Error(
			'woocommerce_ability_order_refund_failed',
			sprintf(
				/* translators: 1: error message, 2: refund ID. */
				__( 'The refund failed before it was sent to the payment gateway: %1$s Refund %2$d of this order, which the payment gateway refunded, was saved during the call, but the call could not confirm that it made it. Check the refunds of the order with the merchant before you retry.', 'woocommerce' ),
				is_wp_error( $result ) ? $result->get_error_message() : __( 'The refund could not be created.', 'woocommerce' ),
				$refund_id
			),
			array(
				'status'    => 500,
				'refund_id' => $refund_id,
			)
		);
	}

	/**
	 * The error of a failed call whose refund is still on the order, because an extension stopped its deletion.
	 *
	 * @param int   $refund_id Refund ID.
	 * @param mixed $result    Result of wc_create_refund().
	 * @param bool  $paid      Whether the gateway refunded the payment.
	 * @return \WP_Error
	 */
	private static function kept_refund_error( int $refund_id, $result, bool $paid ): \WP_Error {
		/* translators: 1: refund ID, 2: error message. */
		$message = __( 'Refund %1$d is still on the order, though a later step failed and WooCommerce could not delete it: %2$s Do not refund again. Check the order, and the payment with the merchant, instead.', 'woocommerce' );
		if ( $paid ) {
			/* translators: 1: refund ID, 2: error message. */
			$message = __( 'Refund %1$d is still on the order and the payment gateway refunded the payment, though a later step failed and WooCommerce could not delete the refund: %2$s Do not refund again. Check the order instead.', 'woocommerce' );
		}

		return new \WP_Error(
			'woocommerce_ability_order_refund_incomplete',
			sprintf( $message, $refund_id, is_wp_error( $result ) ? $result->get_error_message() : __( 'The refund could not be created.', 'woocommerce' ) ),
			array(
				'status'           => 500,
				'refund_id'        => $refund_id,
				'refunded_payment' => $paid,
			)
		);
	}

	/**
	 * The error of a wc_create_refund() call that failed. It deletes the refund on any failure, also after the
	 * gateway or a refund callback moved the money, so the error says when a retry could refund twice.
	 *
	 * @param mixed          $result        Result of wc_create_refund().
	 * @param int            $order_id       Order ID.
	 * @param \WP_Error|null $refusal        Error of the lock check or the extensions that declined the refund, if they did.
	 * @param bool           $saved          Whether the refund was saved after woocommerce_create_refund, or a callback crashed.
	 * @param bool           $paid           Whether the gateway refunded the payment.
	 * @param bool           $refund_payment Whether the call asked the gateway to refund the payment.
	 * @return \WP_Error
	 */
	private static function create_error( $result, int $order_id, ?\WP_Error $refusal, bool $saved, bool $paid, bool $refund_payment ): \WP_Error {
		$reason = is_wp_error( $result ) ? $result->get_error_message() : __( 'The refund could not be created.', 'woocommerce' );

		if ( null !== $refusal ) {
			return $refusal;
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
	 * The newest refund that blocks the call. The refund that allow_duplicate_of names also lets through the older
	 * ones it repeats, so each repeat only needs the ID of the last one, but never a newer refund, which a retry of
	 * the call would have made, or one kept without its payment refunded.
	 *
	 * @param \WC_Order_Refund[] $recent        Refunds from find_recent_refunds(), newest first.
	 * @param string             $meta_key      Meta key of the fingerprint.
	 * @param string             $fingerprint   Fingerprint of the call.
	 * @param int                $allowed_id    Refund ID from allow_duplicate_of, or 0.
	 * @param bool               $allowed_found Set to true when the refund that allow_duplicate_of names matched.
	 * @return \WC_Order_Refund|null
	 */
	private static function find_duplicate( array $recent, string $meta_key, string $fingerprint, int $allowed_id, bool &$allowed_found ): ?\WC_Order_Refund {
		foreach ( $recent as $refund ) {
			if ( $refund->get_meta( $meta_key ) !== $fingerprint ) {
				continue;
			}
			if ( $refund->get_id() > $allowed_id || self::is_unsettled( $refund ) ) {
				return $refund;
			}

			if ( $refund->get_id() === $allowed_id ) {
				$allowed_found = true;
			}
		}

		return null;
	}

	/**
	 * The one of two duplicates for the error to name: one without its payment refunded, since it stops every
	 * retry, else the newer one, since allow_duplicate_of with its ID lets the older one through too.
	 *
	 * @param \WC_Order_Refund|null $first  Duplicate by the input fingerprint.
	 * @param \WC_Order_Refund|null $second Duplicate by the resolved fingerprint.
	 * @return \WC_Order_Refund|null
	 */
	private static function blocking_refund( ?\WC_Order_Refund $first, ?\WC_Order_Refund $second ): ?\WC_Order_Refund {
		if ( null === $first || null === $second ) {
			return $first ?? $second;
		}
		if ( self::is_unsettled( $second ) ) {
			return $second;
		}

		return $second->get_id() > $first->get_id() ? $second : $first;
	}

	/**
	 * Whether allow_duplicate_of names a settled refund of the order, older than the duplicate window, that the
	 * call repeats. A refund past the window no longer blocks the call, so naming it only confirms the repeat.
	 *
	 * @param \WC_Order $order                Order.
	 * @param int       $allowed_id           Refund ID from allow_duplicate_of.
	 * @param string    $fingerprint          Fingerprint of the call.
	 * @param string    $resolved_fingerprint Fingerprint of the amount and lines the call resolved to.
	 * @return bool
	 */
	private static function repeats_older_refund( \WC_Order $order, int $allowed_id, string $fingerprint, string $resolved_fingerprint ): bool {
		$refund = wc_get_order( $allowed_id );

		return $refund instanceof \WC_Order_Refund
			&& $refund->get_parent_id() === $order->get_id()
			&& ! self::is_unsettled( $refund )
			&& ( $refund->get_meta( self::FINGERPRINT_META_KEY ) === $fingerprint || $refund->get_meta( self::RESOLVED_FINGERPRINT_META_KEY ) === $resolved_fingerprint );
	}

	/**
	 * Refunds of the order, newest first, created within the duplicate window or, at any age, kept without their
	 * payment refunded, since a retry would then record the refund twice.
	 *
	 * @param \WC_Order $order Order.
	 * @return \WC_Order_Refund[]
	 */
	private static function find_recent_refunds( \WC_Order $order ): array {
		$refunds = wc_get_orders(
			array(
				'type'    => 'shop_order_refund',
				'parent'  => $order->get_id(),
				'limit'   => -1,
				'orderby' => 'ID',
				'order'   => 'DESC',
			)
		);

		$found = array();
		foreach ( is_array( $refunds ) ? $refunds : array() as $refund ) {
			if ( ! $refund instanceof \WC_Order_Refund ) {
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
				__( 'Refund %1$d already refunded the same amount and lines of this order %2$s ago, so no new refund was created. If you sent this call again after an error or a timeout, the first call went through: tell the merchant about refund %1$d and do not refund again. Only if the merchant then asks for a second, identical refund, send the same input with allow_duplicate_of set to %1$d.', 'woocommerce' ),
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
	 * @return bool|null Null on a database error.
	 */
	private static function holds_lock( int $order_id, string $lock ): ?bool {
		global $wpdb;

		$value = $wpdb->get_var(
			$wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", self::LOCK_OPTION_PREFIX . $order_id )
		);
		if ( null === $value && '' !== $wpdb->last_error ) {
			return null;
		}

		return $value === $lock;
	}

	/**
	 * The error when the order lock cannot be taken or checked. The refund is declined before the gateway runs.
	 *
	 * @return \WP_Error
	 */
	private static function lock_failed_error(): \WP_Error {
		return new \WP_Error(
			'woocommerce_ability_order_refund_lock_failed',
			__( 'The order could not be locked for the refund because of a database error, so no refund was created. It is safe to try again.', 'woocommerce' ),
			array( 'status' => 500 )
		);
	}

	/**
	 * The error when another call took over the order lock as stale while this call was refunding.
	 *
	 * @return \WP_Error
	 */
	private static function lock_lost_error(): \WP_Error {
		return new \WP_Error(
			'woocommerce_ability_order_refund_lock_lost',
			__( 'This refund took too long, and another refund of the order may have started, so no refund was created. List the refunds of the order with woocommerce/order-refunds-query before you refund it again.', 'woocommerce' ),
			array( 'status' => 409 )
		);
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
	 * Name an error of the refund engine as an ability error. The message and data stay. A code missing from
	 * ENGINE_ERROR_CODES becomes `invalid_request`, with the engine code in the data, so the ability codes stay fixed.
	 *
	 * @param \WP_Error $error Error of DataUtils::prepare_refund_creation_or_error().
	 * @return \WP_Error
	 */
	private static function refund_error( \WP_Error $error ): \WP_Error {
		$code = (string) $error->get_error_code();
		$data = $error->get_error_data();
		$data = is_array( $data ) ? $data : array( 'status' => 400 );
		if ( ! isset( self::ENGINE_ERROR_CODES[ $code ] ) ) {
			$data['engine_code'] = $code;
		}

		return new \WP_Error(
			'woocommerce_ability_order_refund_' . ( self::ENGINE_ERROR_CODES[ $code ] ?? 'invalid_request' ),
			$error->get_error_message(),
			$data
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
					'description' => __( 'ID of the refund that a duplicate error returned. Send it, with the same input, only when the merchant asked for a second refund after being told about that one. Never send it to retry a failed or timed-out call.', 'woocommerce' ),
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
