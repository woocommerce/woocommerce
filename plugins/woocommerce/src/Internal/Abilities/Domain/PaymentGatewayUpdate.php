<?php
/**
 * Payment gateway update ability definition file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\PaymentGatewayAbilityTrait;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders;
use Automattic\WooCommerce\Internal\Admin\Settings\Utils as PaymentsSettingsUtils;

defined( 'ABSPATH' ) || exit;

/**
 * Writes only a gateway's enabled state, title and checkout position, never its
 * other settings, which hold credentials.
 */
class PaymentGatewayUpdate extends AbstractDomainAbility implements AbilityDefinition {

	use PaymentGatewayAbilityTrait;

	/**
	 * Ability ID, stable once released.
	 *
	 * @return string
	 *
	 * @since 11.4.0
	 */
	public static function get_name(): string {
		return 'woocommerce/payment-gateway-update';
	}

	/**
	 * Idempotent, shop-manager-level registration.
	 *
	 * @return array
	 *
	 * @since 11.4.0
	 */
	public static function get_registration_args(): array {
		return array(
			'label'               => __( 'Update payment gateway', 'woocommerce' ),
			'description'         => __(
				'Turn a payment gateway on or off, change its checkout title, or move it in the checkout order. Cannot change other gateway settings or credentials.',
				'woocommerce'
			),
			'category'            => 'woocommerce',
			'input_schema'        => self::get_input_schema(),
			'output_schema'       => self::get_entity_output_schema( 'payment_gateway', self::get_payment_gateway_item_schema() ),
			'execute_callback'    => array( __CLASS__, 'execute' ),
			'permission_callback' => array( __CLASS__, 'can_update_payment_gateways' ),
			'meta'                => array(
				'show_in_rest' => true,
				'mcp'          => array(
					'public' => true,
					'type'   => 'tool',
				),
				'annotations'  => array(
					'readonly'    => false,
					'idempotent'  => true,
					'destructive' => false,
				),
			),
		);
	}

	/**
	 * Checks every change before writing any of them, so a refusal leaves the gateway as it was.
	 *
	 * @param array $input Ability input.
	 * @return array|\WP_Error
	 *
	 * @since 11.4.0
	 */
	public static function execute( array $input ) {
		$gateways = WC()->payment_gateways()->payment_gateways();
		$id       = $input['id'];

		if ( ! isset( $gateways[ $id ] ) ) {
			return self::payment_gateway_not_found( 'woocommerce_ability_payment_gateway_not_found' );
		}

		$gateway   = $gateways[ $id ];
		$providers = wc_get_container()->get( PaymentsProviders::class );

		if ( ( $input['enabled'] ?? false ) && 'yes' !== $gateway->enabled ) {
			$refusal = self::refuse_if_needs_setup( $providers, $gateway );
			if ( $refusal ) {
				return $refusal;
			}
		}

		if ( isset( $input['title'] ) ) {
			$title = self::validate_title( $gateway, $input['title'] );
			if ( is_wp_error( $title ) ) {
				return $title;
			}

			$gateway->update_option( 'title', $title );
			$gateway->title = $title;
		}

		if ( isset( $input['enabled'] ) ) {
			$enabled = $input['enabled'] ? 'yes' : 'no';
			$gateway->update_option( 'enabled', $enabled );
			$gateway->enabled = $enabled;
		}

		$ids   = array_map( 'strval', array_keys( $gateways ) );
		$order = (int) array_search( $id, $ids, true );

		if ( isset( $input['order'] ) ) {
			$order = self::move_in_checkout_order( $providers, $ids, $id, min( $input['order'], count( $ids ) - 1 ) );
		}

		return array( 'payment_gateway' => self::format_payment_gateway_for_response( $gateway, $order ) );
	}

	/**
	 * Same check as editing a payment gateway through the REST API.
	 *
	 * @return bool
	 *
	 * @since 11.4.0
	 */
	public static function can_update_payment_gateways(): bool {
		return (bool) wc_rest_check_manager_permissions( 'payment_gateways', 'edit' );
	}

	/**
	 * The admin toggle refuses the same gateways, which would otherwise be on at
	 * checkout without a connected account.
	 *
	 * @param PaymentsProviders   $providers Payment providers.
	 * @param \WC_Payment_Gateway $gateway   Gateway to turn on.
	 * @return \WP_Error|null
	 */
	private static function refuse_if_needs_setup( PaymentsProviders $providers, \WC_Payment_Gateway $gateway ): ?\WP_Error {
		$provider = $providers->get_payment_gateway_provider_instance( $gateway->id );

		if ( ! $provider->needs_setup( $gateway ) ) {
			return null;
		}

		$settings_url = $provider->get_settings_url( $gateway );
		$message      = __( 'This payment gateway needs setup before it can be turned on. The merchant has to finish the setup in the payment settings.', 'woocommerce' );
		$data         = array( 'status' => 400 );

		if ( $settings_url ) {
			/* translators: %s: payment gateway settings URL. */
			$message              = sprintf( __( 'This payment gateway needs setup before it can be turned on. The merchant has to finish the setup at %s.', 'woocommerce' ), $settings_url );
			$data['settings_url'] = $settings_url;
		}

		return new \WP_Error( 'woocommerce_ability_payment_gateway_needs_setup', $message, $data );
	}

	/**
	 * Runs the gateway's own title validation, as its settings screen does.
	 *
	 * @param \WC_Payment_Gateway $gateway Gateway.
	 * @param string              $title   New title.
	 * @return string|\WP_Error
	 */
	private static function validate_title( \WC_Payment_Gateway $gateway, string $title ) {
		$field = $gateway->get_form_fields()['title'] ?? null;

		if ( ! is_array( $field ) ) {
			return new \WP_Error(
				'woocommerce_ability_payment_gateway_title_unsupported',
				__( 'This payment gateway has no title setting.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		try {
			// get_field_value() expects slashed form data.
			$value = (string) $gateway->get_field_value( 'title', $field, array( $gateway->get_field_key( 'title' ) => wp_slash( $title ) ) );
		} catch ( \Exception $e ) {
			return new \WP_Error(
				'woocommerce_ability_payment_gateway_title_invalid',
				$e->getMessage(),
				array( 'status' => 400 )
			);
		}

		// minLength runs before sanitizing. Stripping the raw title also drops script and style contents, which kses keeps as text.
		if ( '' === trim( wp_strip_all_tags( $title ) ) || '' === trim( wp_strip_all_tags( $value ) ) ) {
			return new \WP_Error(
				'woocommerce_ability_payment_gateway_title_invalid',
				__( 'The title is empty once markup and whitespace are removed. Send a title with visible text.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		return $value;
	}

	/**
	 * Moves the gateway onto the order-map slot of the gateway now at the target
	 * position, the way a drag and drop on the payments settings page does, so
	 * suggestions and offline methods in the map keep their place.
	 *
	 * Offline methods always follow the offline group entry, so an offline method
	 * only moves within that block, and any other gateway sent inside it lands at
	 * the nearer edge. The edge depends only on the target, so a repeated call
	 * leaves the order as it is.
	 *
	 * @param PaymentsProviders  $providers Payment providers.
	 * @param array<int, string> $ids       Gateway IDs in checkout order.
	 * @param string             $id        Gateway to move.
	 * @param int                $position  Target position.
	 * @return int The saved position, which can differ when offline methods stay grouped.
	 */
	private static function move_in_checkout_order( PaymentsProviders $providers, array $ids, string $id, int $position ): int {
		$order_map  = $providers->get_order_map();
		$current    = (int) array_search( $id, $ids, true );
		$is_offline = $providers->is_offline_payment_method( $id );
		$others     = array_values( array_diff( $ids, array( $id ) ) );
		$offline    = array_keys( array_filter( $others, array( $providers, 'is_offline_payment_method' ) ) );

		// The gateway lands before $others[ $position ], so a position from $first + 1 to $last splits the offline block.
		if ( $offline ) {
			$first = min( $offline );
			$last  = max( $offline );

			if ( $is_offline ) {
				$position = max( $first, min( $position, $last + 1 ) );
			} elseif ( $position > $first && $position <= $last ) {
				$position = $position - $first <= $last + 1 - $position ? $first : $last + 1;
			}
		}

		$target = $ids[ $position ];

		if ( ! $is_offline && $position < $current && $providers->is_offline_payment_method( $target ) ) {
			$target = PaymentsProviders::OFFLINE_METHODS_ORDERING_GROUP;
		}

		if ( $position !== $current && isset( $order_map[ $target ] ) ) {
			$order_map = PaymentsSettingsUtils::order_map_apply_mappings( $order_map, array( $id => $order_map[ $target ] ) );
			$providers->save_order_map( $providers->enhance_order_map( $order_map ) );
		}

		return self::get_checkout_position( $ids, $id );
	}

	/**
	 * Mirrors how WC_Payment_Gateways::init() sorts gateways: by their saved
	 * order, with unordered gateways after them.
	 *
	 * @param array<int, string> $ids Gateway IDs.
	 * @param string             $id  Gateway to find.
	 * @return int
	 */
	private static function get_checkout_position( array $ids, string $id ): int {
		$saved     = (array) get_option( PaymentsProviders::PROVIDERS_ORDER_OPTION );
		$positions = array();
		$end       = PHP_INT_MAX - count( $ids );

		foreach ( $ids as $gateway_id ) {
			$positions[ $gateway_id ] = isset( $saved[ $gateway_id ] ) && is_numeric( $saved[ $gateway_id ] ) ? (int) $saved[ $gateway_id ] : $end++;
		}

		asort( $positions );

		return (int) array_search( $id, array_map( 'strval', array_keys( $positions ) ), true );
	}

	/**
	 * Only the three writable fields; any other field is a schema error.
	 *
	 * @return array
	 */
	private static function get_input_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'id'      => array(
					'type'        => 'string',
					'description' => __( 'Payment gateway ID, such as bacs or cod.', 'woocommerce' ),
					'minLength'   => 1,
				),
				'enabled' => array(
					'type'        => 'boolean',
					'description' => __( 'Whether customers can pay with this gateway at checkout.', 'woocommerce' ),
				),
				'title'   => array(
					'type'        => 'string',
					'description' => __( 'Title shown to customers at checkout.', 'woocommerce' ),
					'minLength'   => 1,
				),
				'order'   => array(
					'type'        => 'integer',
					'description' => __( 'New position in the checkout order, starting at 0. Offline methods stay grouped, so the response gives the saved position.', 'woocommerce' ),
					'minimum'     => 0,
				),
			),
			'required'             => array( 'id' ),
			'minProperties'        => 2,
			'additionalProperties' => false,
		);
	}
}
