<?php
/**
 * Order send email ability definition file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\OrderAbilityTrait;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the WooCommerce order send email ability.
 */
class OrderSendEmail extends AbstractDomainAbility implements AbilityDefinition {

	use OrderAbilityTrait;

	/**
	 * Email class for each email that the ability can send.
	 */
	private const EMAIL_CLASSES = array(
		'invoice'    => 'WC_Email_Customer_Invoice',
		'processing' => 'WC_Email_Customer_Processing_Order',
		'completed'  => 'WC_Email_Customer_Completed_Order',
	);

	/**
	 * Order status that each status email needs.
	 */
	private const REQUIRED_STATUSES = array(
		'processing' => OrderStatus::PROCESSING,
		'completed'  => OrderStatus::COMPLETED,
	);

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
				'Send an order email to the customer again.',
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
		$refusal    = self::get_status_refusal( $order, $email_name );

		if ( is_wp_error( $refusal ) ) {
			return $refusal;
		}

		if ( ! is_email( $order->get_billing_email() ) ) {
			return new \WP_Error(
				'woocommerce_ability_order_email_no_recipient',
				__( 'The order has no valid billing email. Set billing.email with the woocommerce/order-update ability, then send the email again.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		WC()->payment_gateways();
		WC()->shipping();

		/**
		 * The email to send, or null when a filter removed its class.
		 *
		 * @var \WC_Email_Customer_Invoice|\WC_Email_Customer_Processing_Order|\WC_Email_Customer_Completed_Order|null $email
		 */
		$email = WC()->mailer()->get_emails()[ self::EMAIL_CLASSES[ $email_name ] ] ?? null;

		if ( ! $email instanceof \WC_Email || ( ! $email->is_manual() && ! $email->is_enabled() ) ) {
			return new \WP_Error(
				'woocommerce_ability_order_email_disabled',
				/* translators: %s: email name. */
				sprintf( __( 'The %s email is turned off on this store. Ask the store owner to turn it on in WooCommerce > Settings > Emails.', 'woocommerce' ), $email_name ),
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

		add_action( 'woocommerce_email_sent', $capture, 10, 2 );
		$email->trigger( $order->get_id(), $order );
		remove_action( 'woocommerce_email_sent', $capture, 10 );

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
	 * Refuse a status email when the order does not have that status. The email
	 * would tell the customer something that is not true.
	 *
	 * @param \WC_Order $order      Order.
	 * @param string    $email_name Email name from the input.
	 * @return null|\WP_Error
	 */
	private static function get_status_refusal( \WC_Order $order, string $email_name ): ?\WP_Error {
		$required_status = self::REQUIRED_STATUSES[ $email_name ] ?? null;

		if ( null === $required_status || $order->has_status( $required_status ) ) {
			return null;
		}

		return new \WP_Error(
			'woocommerce_ability_order_email_status_mismatch',
			sprintf(
				/* translators: 1: email name, 2: order status slug. */
				__( 'The %1$s email only applies to an order with that status, and this order has the "%2$s" status. Send the invoice email, or change the status with the woocommerce/order-update-status ability first.', 'woocommerce' ),
				$email_name,
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
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'id'    => array(
					'type'    => 'integer',
					'minimum' => 1,
				),
				'email' => array(
					'type'        => 'string',
					'description' => __( 'A status email needs the order to have that status.', 'woocommerce' ),
					'enum'        => array_keys( self::EMAIL_CLASSES ),
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
					'enum' => array_keys( self::EMAIL_CLASSES ),
				),
			),
			'additionalProperties' => false,
		);
	}
}
