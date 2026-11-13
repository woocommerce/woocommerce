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
	 * What the order could refund before this refund, read by change().
	 *
	 * @var float
	 */
	private float $refundable = 0.0;

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
				'Refund an amount or lines of an order. With refund_payment, the payment gateway sends the money back; without it, the refund is only recorded and the money is returned outside WooCommerce. Send dry_run first to read the refundable amount and whether the gateway can refund, without changing anything.',
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
		$order            = $this->order;
		$this->refundable = (float) $order->get_remaining_refund_amount();
		$prepared         = wc_get_container()->get( DataUtils::class )->prepare_refund_creation_or_error(
			$order,
			$input['line_items'] ?? array(),
			isset( $input['amount'] ),
			isset( $input['amount'] ) ? wc_format_decimal( $input['amount'] ) : null,
			'woocommerce-abilities'
		);
		if ( is_wp_error( $prepared ) ) {
			return self::refund_error( $prepared );
		}

		$refund_payment = $input['refund_payment'] ?? false;
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
				'amount'         => $prepared['amount'],
				'reason'         => isset( $input['reason'] ) ? sanitize_textarea_field( $input['reason'] ) : null,
				'line_items'     => $prepared['line_items'],
				'refund_payment' => $refund_payment,
				'restock_items'  => $input['restock_items'] ?? false,
			)
		);

		$staged = $this->creator->stage( $subject );

		return is_wp_error( $staged ) ? self::refund_error( $staged ) : null;
	}

	/**
	 * Fire woocommerce_create_refund only now, after the validators and never in a dry run, as some callbacks
	 * refund the payment through their own gateway there.
	 *
	 * @param \WC_Order_Refund $subject Staged refund.
	 * @return null|\WP_Error
	 */
	protected function save( $subject ) {
		$declined = $this->creator->before_save( $subject );
		if ( is_wp_error( $declined ) ) {
			return self::refund_error( $declined );
		}

		return parent::save( $subject );
	}

	/**
	 * Whether the call only previews the refund.
	 *
	 * @param array $input Ability input.
	 * @return bool
	 */
	protected function is_dry_run( array $input ): bool {
		return $input['dry_run'] ?? false;
	}

	/**
	 * Refund the payment, restock and update the order, after the refund row is committed. A gateway refusal
	 * deletes the refund, so the call fails with nothing saved.
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
		return $this->format_result( $subject, false );
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
	 * The ability output for a dry run, with the refund that would be created.
	 *
	 * @param \WC_Order_Refund $subject Staged refund, not saved.
	 * @param array            $input   Ability input.
	 * @return array|\WP_Error
	 */
	protected function prepare_preview( $subject, array $input ) {
		return $this->format_result( $subject, true );
	}

	/**
	 * Format the refund with the refundable amount and the gateway support that the client checks before a refund.
	 *
	 * @param \WC_Order_Refund $refund  Refund.
	 * @param bool             $dry_run Whether the refund was only staged.
	 * @return array|\WP_Error
	 */
	private function format_result( \WC_Order_Refund $refund, bool $dry_run ) {
		$formatted = self::format_refund_for_response( $refund );
		if ( is_wp_error( $formatted ) ) {
			return $formatted;
		}

		return array(
			'refund'                   => $formatted,
			'refundable_amount'        => self::format_amount_for_response( $this->refundable ),
			'gateway_supports_refunds' => self::gateway_can_refund( $this->order ),
			'dry_run'                  => $dry_run,
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
		$reason = trim( (string) preg_replace( '/(^|_)refund(?=_|$)/', '', (string) $error->get_error_code() ), '_' );
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
					'description' => __( 'Whether the payment gateway sends the money back. Check gateway_supports_refunds with a dry run first.', 'woocommerce' ),
					'default'     => false,
				),
				'restock_items'  => array(
					'type'        => 'boolean',
					'description' => __( 'Whether to return the refunded quantities of the product lines to stock.', 'woocommerce' ),
					'default'     => false,
				),
				'dry_run'        => array(
					'type'        => 'boolean',
					'description' => __( 'Check the refund and return it without saving it or moving money.', 'woocommerce' ),
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
				'refund'                   => self::get_refund_output_schema(),
				'refundable_amount'        => array(
					'type'        => 'string',
					'description' => __( 'Amount the order could refund before this refund.', 'woocommerce' ),
				),
				'gateway_supports_refunds' => array(
					'type'        => 'boolean',
					'description' => __( 'Whether the payment gateway of the order can send the money back with refund_payment.', 'woocommerce' ),
				),
				'dry_run'                  => array(
					'type'        => 'boolean',
					'description' => __( 'True when nothing was saved and no money moved.', 'woocommerce' ),
				),
			),
			'additionalProperties' => false,
		);
	}
}
