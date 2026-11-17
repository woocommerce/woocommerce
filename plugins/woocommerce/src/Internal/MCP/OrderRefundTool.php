<?php
/**
 * OrderRefundTool class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\MCP;

use Automattic\WooCommerce\Internal\Abilities\Domain\OrderRefundCreate;
use WP\MCP\Domain\Tools\McpInputRequired;
use WP\MCP\Domain\Tools\McpTool;
use WP\MCP\Domain\Tools\McpToolCallContext;

defined( 'ABSPATH' ) || exit;

/**
 * MCP tool of the MCP Adapter's default server that refunds an order through the order-refund-create ability.
 * When the client supports form elicitation, the merchant first approves the refund that the ability's dry run
 * describes, and only that refund is created. Other clients refund on the first call, behind their own approval
 * of destructive tools.
 *
 * @since 11.4.0
 */
class OrderRefundTool {

	/**
	 * MCP tool name.
	 */
	public const TOOL_NAME = 'woocommerce-order-refund';

	/**
	 * Key of the approval question in the elicitation request and in the client's answers.
	 */
	private const QUESTION_KEY = 'approve_refund';

	/**
	 * Transient of a pending approval, followed by the request state that names it.
	 */
	private const APPROVAL_TRANSIENT_PREFIX = 'wc_order_refund_approval_';

	/**
	 * Seconds the merchant has to approve a refund.
	 */
	private const APPROVAL_TTL = 10 * MINUTE_IN_SECONDS;

	/**
	 * Whether the loaded MCP Adapter can ask the client for input, from version 0.7.0.
	 *
	 * @return bool
	 *
	 * @since 11.4.0
	 */
	public static function is_available(): bool {
		return class_exists( McpTool::class ) && class_exists( McpInputRequired::class ) && class_exists( McpToolCallContext::class );
	}

	/**
	 * Add the tool to the default server. The refund ability is then not MCP-public, so this tool is the only
	 * way to refund over MCP there.
	 *
	 * @internal
	 *
	 * @param mixed $config Configuration of the default server.
	 * @return mixed
	 *
	 * @since 11.4.0
	 */
	public static function add_to_default_server( $config ) {
		if ( ! is_array( $config ) || ! isset( $config['tools'] ) || ! is_array( $config['tools'] ) || ! self::is_available() ) {
			return $config;
		}

		$tool = self::create();
		if ( $tool instanceof McpTool ) {
			$config['tools'][] = $tool;
		}

		return $config;
	}

	/**
	 * Create the tool from the input schema of the refund ability. Its dry_run is left out, as the tool previews the
	 * refund itself, but a caller that still sends it gets only the preview.
	 *
	 * @return McpTool|\WP_Error|null Null when the refund ability is not registered.
	 *
	 * @since 11.4.0
	 */
	public static function create() {
		$ability = wp_get_ability( OrderRefundCreate::ABILITY_NAME );
		if ( null === $ability ) {
			return null;
		}

		$input_schema = $ability->get_input_schema();
		unset( $input_schema['properties']['dry_run'] );

		return McpTool::fromArray(
			array(
				'name'        => self::TOOL_NAME,
				'title'       => __( 'Refund order', 'woocommerce' ),
				'description' => __( 'Refund an amount or lines of an order. With refund_payment, the payment gateway sends the money back; without it, the refund is only recorded and the money is returned outside WooCommerce. When the client can show forms, the merchant approves the refund before it is made.', 'woocommerce' ),
				'inputSchema' => $input_schema,
				'annotations' => array(
					'readOnlyHint'    => false,
					'destructiveHint' => true,
					'idempotentHint'  => false,
					'openWorldHint'   => true,
				),
				'permission'  => array( self::class, 'check_permission' ),
				'handler'     => array( self::class, 'handle' ),
			)
		);
	}

	/**
	 * Check the input and the permission of the refund ability, on every attempt.
	 *
	 * @internal
	 *
	 * @param mixed $args Tool arguments.
	 * @return bool|\WP_Error
	 *
	 * @since 11.4.0
	 */
	public static function check_permission( $args ) {
		$ability = wp_get_ability( OrderRefundCreate::ABILITY_NAME );
		if ( null === $ability ) {
			return self::ability_missing();
		}

		$input = self::get_refund_input( $args );
		$valid = $ability->validate_input( $input );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}

		return $ability->check_permissions( $input );
	}

	/**
	 * Ask the merchant to approve the refund, then create it. Each attempt runs from the start: the first returns
	 * the question, and the client's retry with the answer creates the refund.
	 *
	 * @internal
	 *
	 * @param mixed                   $args    Tool arguments.
	 * @param McpToolCallContext|null $context Call context, null outside the adapter.
	 * @return mixed|\WP_Error|McpInputRequired
	 *
	 * @since 11.4.0
	 */
	public static function handle( $args, ?McpToolCallContext $context = null ) {
		$ability = wp_get_ability( OrderRefundCreate::ABILITY_NAME );
		if ( null === $ability ) {
			return self::ability_missing();
		}

		$input = self::get_refund_input( $args );
		if ( true === ( $args['dry_run'] ?? null ) ) {
			return $ability->execute( array_merge( $input, array( 'dry_run' => true ) ) );
		}

		if ( null === $context || ! $context->client_supports_elicitation() ) {
			return $ability->execute( $input );
		}

		$state = $context->request_state();
		if ( null === $state ) {
			if ( $context->is_continuation() ) {
				return self::approval_unavailable();
			}

			return self::ask( $ability, $input, null );
		}

		if ( 1 !== preg_match( '/\A[a-f0-9]{32}\z/', $state ) || get_transient( self::APPROVAL_TRANSIENT_PREFIX . $state ) !== self::get_binding( $input ) ) {
			return self::approval_unavailable();
		}

		$answer = $context->input_responses()->{self::QUESTION_KEY} ?? null;
		if ( ! $answer instanceof \stdClass ) {
			return self::ask( $ability, $input, $state );
		}

		$approved = $answer->content->approve ?? null;
		if ( 'accept' === ( $answer->action ?? null ) && ! is_bool( $approved ) ) {
			return new \WP_Error(
				'woocommerce_mcp_order_refund_invalid_answer',
				__( 'The answer to the refund approval must say whether the merchant approves it.', 'woocommerce' )
			);
		}

		// Only the attempt that removes the approval may refund, so two retries with one approval cannot both refund.
		if ( ! delete_transient( self::APPROVAL_TRANSIENT_PREFIX . $state ) ) {
			return self::approval_unavailable();
		}

		if ( 'accept' !== ( $answer->action ?? null ) || true !== $approved ) {
			return array(
				'refunded' => false,
				'message'  => __( 'The merchant did not approve the refund, so nothing was refunded.', 'woocommerce' ),
			);
		}

		return $ability->execute( $input );
	}

	/**
	 * Preview the refund with the ability's dry run, and return the question that describes it. The first attempt
	 * stores the approval that a retry must name.
	 *
	 * @param \WP_Ability $ability Refund ability.
	 * @param array       $input   Refund input.
	 * @param string|null $state   Request state of a stored approval, or null on the first attempt.
	 * @return McpInputRequired|\WP_Error
	 */
	private static function ask( \WP_Ability $ability, array $input, ?string $state ) {
		$preview = $ability->execute( array_merge( $input, array( 'dry_run' => true ) ) );
		if ( is_wp_error( $preview ) ) {
			return $preview;
		}
		if ( ! is_array( $preview ) ) {
			return new \WP_Error( 'woocommerce_mcp_order_refund_preview_failed', __( 'The refund could not be previewed.', 'woocommerce' ) );
		}

		if ( null === $state ) {
			$state = bin2hex( random_bytes( 16 ) );
			if ( ! set_transient( self::APPROVAL_TRANSIENT_PREFIX . $state, self::get_binding( $input ), self::APPROVAL_TTL ) ) {
				return new \WP_Error( 'woocommerce_mcp_order_refund_approval_not_saved', __( 'The refund approval could not be saved.', 'woocommerce' ) );
			}
		}

		return new McpInputRequired(
			array(
				self::QUESTION_KEY => array(
					'method' => 'elicitation/create',
					'params' => array(
						'message'         => self::describe( $preview, $input ),
						'requestedSchema' => array(
							'type'       => 'object',
							'properties' => array(
								'approve' => array(
									'type'  => 'boolean',
									'title' => __( 'Approve the refund', 'woocommerce' ),
								),
							),
							'required'   => array( 'approve' ),
						),
					),
				),
			),
			$state
		);
	}

	/**
	 * Describe the previewed refund for the merchant: amount, how the money goes back, lines, restock and reason.
	 *
	 * @param array $preview Dry-run output of the refund ability.
	 * @param array $input   Refund input.
	 * @return string
	 */
	private static function describe( array $preview, array $input ): string {
		$refund   = $preview['refund'] ?? array();
		$order    = wc_get_order( $refund['order_id'] ?? 0 );
		$currency = $order instanceof \WC_Order ? $order->get_currency() : '';
		$number   = $order instanceof \WC_Order ? $order->get_order_number() : (string) ( $refund['order_id'] ?? '' );

		$lines = array(
			/* translators: 1: amount with currency code, such as "12.50 USD", 2: order number. */
			sprintf( __( 'Refund %1$s of order %2$s?', 'woocommerce' ), trim( wc_format_decimal( $refund['amount'] ?? 0, wc_get_price_decimals() ) . ' ' . $currency ), $number ),
		);

		if ( $input['refund_payment'] ?? false ) {
			$lines[] = sprintf(
				/* translators: %s: payment method title, such as "Credit card". */
				__( 'The payment gateway (%s) sends the money back to the customer.', 'woocommerce' ),
				$order instanceof \WC_Order ? $order->get_payment_method_title() : ''
			);
		} else {
			$lines[] = __( 'The refund is only recorded. Return the money to the customer outside WooCommerce.', 'woocommerce' );
		}

		foreach ( $refund['line_items'] ?? array() as $line ) {
			/* translators: 1: line name, 2: refunded quantity, 3: refunded amount. */
			$lines[] = sprintf( __( '- %1$s × %2$s: %3$s', 'woocommerce' ), $line['name'] ?? '', $line['quantity'] ?? 0, wc_format_decimal( $line['total'] ?? 0, wc_get_price_decimals() ) );
		}

		if ( $input['restock_items'] ?? false ) {
			$lines[] = __( 'The refunded items go back into stock.', 'woocommerce' );
		}

		if ( isset( $refund['reason'] ) ) {
			/* translators: %s: refund reason. */
			$lines[] = sprintf( __( 'Reason: %s', 'woocommerce' ), $refund['reason'] );
		}

		return implode( "\n", $lines );
	}

	/**
	 * The refund input of the tool arguments, without dry_run, which only the tool sets.
	 *
	 * @param mixed $args Tool arguments.
	 * @return array
	 */
	private static function get_refund_input( $args ): array {
		$input = is_array( $args ) ? $args : array();
		unset( $input['dry_run'] );
		ksort( $input );

		return $input;
	}

	/**
	 * What an approval is for: the user, the site and the exact refund input.
	 *
	 * @param array $input Refund input.
	 * @return array
	 */
	private static function get_binding( array $input ): array {
		return array(
			'user'  => get_current_user_id(),
			'blog'  => get_current_blog_id(),
			'input' => hash( 'sha256', (string) wp_json_encode( $input ) ),
		);
	}

	/**
	 * The error of a retry whose approval is unknown, expired, used, or for another refund.
	 *
	 * @return \WP_Error
	 */
	private static function approval_unavailable(): \WP_Error {
		return new \WP_Error(
			'woocommerce_mcp_order_refund_approval_unavailable',
			__( 'This refund approval expired, was already used, or is for another refund. List the refunds of the order with woocommerce/order-refunds-query, then send the refund again to ask for a new approval.', 'woocommerce' )
		);
	}

	/**
	 * The error when the refund ability is not registered.
	 *
	 * @return \WP_Error
	 */
	private static function ability_missing(): \WP_Error {
		return new \WP_Error( 'woocommerce_mcp_order_refund_unavailable', __( 'Refunds are not available on this store.', 'woocommerce' ) );
	}
}
