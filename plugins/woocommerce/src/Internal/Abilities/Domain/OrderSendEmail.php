<?php
/**
 * Order send email ability definition file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;
use Automattic\WooCommerce\Abilities\ExtensibleAbilities;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\OrderAbilityTrait;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the WooCommerce order send email ability.
 */
class OrderSendEmail extends AbstractDomainAbility implements AbilityDefinition {

	use OrderAbilityTrait;

	/**
	 * Get the emails that the ability can send: Core's emails, then the order
	 * emails that extensions register with ExtensibleAbilities::register_order_email().
	 *
	 * @return array<string, array{email_class: string, description: string, statuses: array<int, string>, trigger_callback?: callable|null}>
	 */
	private static function get_emails(): array {
		$core_emails = array(
			'invoice'    => array(
				'email_class' => 'WC_Email_Customer_Invoice',
				'description' => __( 'The order details, with a payment link while the order is pending.', 'woocommerce' ),
				'statuses'    => array(),
			),
			'processing' => array(
				'email_class' => 'WC_Email_Customer_Processing_Order',
				'description' => __( 'Tells the customer that the order is being processed.', 'woocommerce' ),
				'statuses'    => array( OrderStatus::PROCESSING ),
			),
			'completed'  => array(
				'email_class' => 'WC_Email_Customer_Completed_Order',
				'description' => __( 'Tells the customer that the order is complete.', 'woocommerce' ),
				'statuses'    => array( OrderStatus::COMPLETED ),
			),
		);

		return array_merge( $core_emails, ExtensibleAbilities::get_order_emails() );
	}

	/**
	 * Get the ability name.
	 *
	 * @return string
	 *
	 * @since 11.4.0
	 */
	public static function get_name(): string {
		return 'woocommerce/order-send-email';
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
			'label'               => __( 'Send order email', 'woocommerce' ),
			'description'         => __(
				'Send an order email again. A customer email goes to the billing email of the order, and a store email goes to the recipients set in the email settings.',
				'woocommerce'
			),
			'category'            => 'woocommerce',
			'input_schema'        => self::get_input_schema(),
			'output_schema'       => self::get_output_schema(),
			'execute_callback'    => array( __CLASS__, 'execute' ),
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
					'destructive' => false,
				),
			),
		);
	}

	/**
	 * Send the order email. EmailLogger notes the email on the order.
	 *
	 * @param array $input Ability input.
	 * @return array|\WP_Error
	 *
	 * @since 11.4.0
	 */
	public static function execute( array $input ) {
		$order = self::get_order_from_input( $input );

		if ( is_wp_error( $order ) ) {
			return $order;
		}

		$email_name = $input['email'];
		$email_args = self::get_emails()[ $email_name ];
		$refusal    = self::get_status_refusal( $order, $email_name, $email_args['statuses'] );

		if ( is_wp_error( $refusal ) ) {
			return $refusal;
		}

		WC()->payment_gateways();
		WC()->shipping();

		$email = WC()->mailer()->get_emails()[ $email_args['email_class'] ] ?? null;

		if ( ! $email instanceof \WC_Email || ( ! $email->is_manual() && ! $email->is_enabled() ) ) {
			return new \WP_Error(
				'woocommerce_ability_order_email_disabled',
				/* translators: %s: email name. */
				sprintf( __( 'The %s email is turned off on this store. Ask the store owner to turn it on in WooCommerce > Settings > Emails.', 'woocommerce' ), $email_name ),
				array( 'status' => 400 )
			);
		}

		if ( $email->is_customer_email() && ! is_email( $order->get_billing_email() ) ) {
			return new \WP_Error(
				'woocommerce_ability_order_email_no_recipient',
				__( 'The order has no valid billing email. Set billing.email with the woocommerce/order-update ability, then send the email again.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$sent    = false;
		$capture = static function ( $result, $email_id ) use ( &$sent, $email ): void {
			if ( $email_id === $email->id ) {
				$sent = (bool) $result;
			}
		};

		// phpcs:disable WooCommerce.Commenting.CommentHooks.MissingSinceComment
		/** This action is documented in includes/admin/meta-boxes/class-wc-meta-box-order-actions.php */
		do_action( 'woocommerce_before_resend_order_emails', $order, $email->id );

		$failed = false;
		add_action( 'woocommerce_email_sent', $capture, 10, 2 );
		try {
			if ( isset( $email_args['trigger_callback'] ) ) {
				call_user_func( $email_args['trigger_callback'], $email, $order );
			} elseif ( method_exists( $email, 'trigger' ) ) {
				$email->trigger( $order->get_id(), $order );
			}
		} catch ( \Throwable $e ) {
			$failed = true;
			wc_get_logger()->error( sprintf( 'Sending the %1$s email of order %2$d failed: %3$s', $email_name, $order->get_id(), $e->getMessage() ), array( 'source' => 'extensible-abilities' ) );
		} finally {
			remove_action( 'woocommerce_email_sent', $capture, 10 );
		}

		if ( ! $sent && $failed ) {
			return new \WP_Error(
				'woocommerce_ability_order_email_send_failed',
				/* translators: %s: email name. */
				sprintf( __( 'The %s email failed with an error before the site sent it. Ask the store owner to check the extensible-abilities log in WooCommerce > Status > Logs.', 'woocommerce' ), $email_name ),
				array( 'status' => 500 )
			);
		}

		if ( ! $sent ) {
			return new \WP_Error(
				'woocommerce_ability_order_email_send_failed',
				/* translators: %s: email name. */
				sprintf( __( 'The site could not send the %s email. Ask the store owner to check the mail setup of the site.', 'woocommerce' ), $email_name ),
				array( 'status' => 500 )
			);
		}

		/** This action is documented in includes/admin/meta-boxes/class-wc-meta-box-order-actions.php */
		do_action( 'woocommerce_after_resend_order_email', $order, $email->id );
		// phpcs:enable WooCommerce.Commenting.CommentHooks.MissingSinceComment

		return array(
			'order_id' => $order->get_id(),
			'email'    => $email_name,
		);
	}

	/**
	 * Refuse an email when the order does not have one of the statuses of the
	 * email. The email would say something about the order that is not true.
	 *
	 * @param \WC_Order          $order      Order.
	 * @param string             $email_name Email name from the input.
	 * @param array<int, string> $statuses   Statuses of the email. Empty for any status.
	 * @return null|\WP_Error
	 */
	private static function get_status_refusal( \WC_Order $order, string $email_name, array $statuses ): ?\WP_Error {
		if ( empty( $statuses ) || $order->has_status( $statuses ) ) {
			return null;
		}

		return new \WP_Error(
			'woocommerce_ability_order_email_status_mismatch',
			sprintf(
				/* translators: 1: email name, 2: comma-separated order status slugs, 3: order status slug. */
				__( 'The %1$s email only applies to an order with the status %2$s, and this order has the "%3$s" status. Send the invoice email, or change the status with the woocommerce/order-update-status ability first.', 'woocommerce' ),
				$email_name,
				implode( ', ', $statuses ),
				$order->get_status()
			),
			array( 'status' => 400 )
		);
	}

	/**
	 * Get the ability input schema.
	 *
	 * @return array
	 */
	private static function get_input_schema(): array {
		$lines = array();
		foreach ( self::get_emails() as $email_name => $email_args ) {
			$lines[] = sprintf(
				/* translators: 1: email name, 2: email description, 3: comma-separated order status slugs. */
				__( '%1$s: %2$s Statuses: %3$s.', 'woocommerce' ),
				$email_name,
				$email_args['description'],
				empty( $email_args['statuses'] ) ? __( 'any', 'woocommerce' ) : implode( ', ', $email_args['statuses'] )
			);
		}

		return array(
			'type'                 => 'object',
			'properties'           => array(
				'id'    => array(
					'type'    => 'integer',
					'minimum' => 1,
				),
				'email' => array(
					'type'        => 'string',
					'description' => __( 'The email to send, from the emails of WooCommerce and extensions. An email is refused for an order without one of its statuses.', 'woocommerce' ) . ' ' . implode( ' ', $lines ),
					'enum'        => array_keys( self::get_emails() ),
				),
			),
			'required'             => array( 'id', 'email' ),
			'additionalProperties' => false,
		);
	}

	/**
	 * Get the ability output schema.
	 *
	 * @return array
	 */
	private static function get_output_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'order_id' => array( 'type' => 'integer' ),
				'email'    => array(
					'type' => 'string',
					'enum' => array_keys( self::get_emails() ),
				),
			),
			'additionalProperties' => false,
		);
	}
}
