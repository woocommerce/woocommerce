<?php
/**
 * Customer update ability class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\ActionableAbility;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\AddressInputTrait;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\DomainAbilityHelpers;

defined( 'ABSPATH' ) || exit;

/**
 * Changes the name and addresses of a registered customer. The response names the changed fields
 * without their values, so no personal data comes back.
 */
class CustomerUpdate extends ActionableAbility {

	use AddressInputTrait;
	use DomainAbilityHelpers;

	/**
	 * Ability name.
	 */
	public const ABILITY_NAME = 'woocommerce/customer-update';

	/**
	 * Fields that change() changed, as `first_name` or `billing.city`.
	 *
	 * @var array<int, string>
	 */
	private array $updated_fields = array();

	/**
	 * Analytics customer ID from the input.
	 *
	 * @var int
	 */
	private int $customer_id = 0;

	/**
	 * Get the ability registration arguments.
	 *
	 * @return array
	 *
	 * @since 11.4.0
	 */
	public static function get_registration_args(): array {
		return array(
			'label'               => __( 'Update customer', 'woocommerce' ),
			'description'         => __(
				'Update the first name, last name, billing address and shipping address of a registered customer. Fields that are not sent keep their value. Guest customers have no account to update; their addresses live on their orders. The response lists the changed fields without their values.',
				'woocommerce'
			),
			'category'            => 'woocommerce',
			'input_schema'        => self::build_input_schema(),
			'output_schema'       => self::get_entity_output_schema(
				'customer',
				array(
					'type'       => 'object',
					'properties' => array(
						'id'             => array( 'type' => 'integer' ),
						'user_id'        => array( 'type' => 'integer' ),
						'updated_fields' => array(
							'type'        => 'array',
							'description' => __( 'Fields whose value changed, such as first_name or billing.city.', 'woocommerce' ),
							'items'       => array( 'type' => 'string' ),
						),
					),
				)
			),
			'permission_callback' => array( __CLASS__, 'can_edit_customer' ),
			'meta'                => array(
				'show_in_rest' => true,
				'mcp'          => array(
					'public' => true,
					'type'   => 'tool',
				),
				// Not idempotent: the Abilities API run endpoint demands DELETE for destructive idempotent abilities.
				'annotations'  => array(
					'readonly'    => false,
					'idempotent'  => false,
					'destructive' => true,
				),
			),
		);
	}

	/**
	 * Check user edit access, with the same rules as the customers REST API. An ID that is a guest or
	 * no customer passes for users who can list customers, so they get the specific error.
	 *
	 * @since 11.4.0
	 * @param mixed $input Ability input.
	 * @return bool
	 */
	public static function can_edit_customer( $input = array() ): bool {
		$customer_id = self::get_id_from_input( $input );
		if ( $customer_id <= 0 ) {
			return false;
		}

		$user_id = CustomerLookup::get_user_id( $customer_id );
		if ( ! is_int( $user_id ) ) {
			return wc_rest_check_user_permissions( 'read' );
		}

		return wc_rest_check_user_permissions( 'edit', $user_id );
	}

	/**
	 * Resource that the ability changes.
	 *
	 * @return string
	 */
	public function get_resource(): string {
		return 'customer';
	}

	/**
	 * Load the customer to change.
	 *
	 * @param array $input Ability input.
	 * @return \WC_Customer|\WP_Error
	 */
	protected function load( array $input ) {
		$unavailable = CustomerLookup::get_unavailable_error();
		if ( null !== $unavailable ) {
			return $unavailable;
		}

		$this->customer_id = $input['id'];
		$user_id           = CustomerLookup::get_user_id( $input['id'] );

		if ( false === $user_id ) {
			return new \WP_Error(
				'woocommerce_ability_customer_not_found',
				/* translators: %d: customer ID. */
				sprintf( __( 'Customer %d does not exist. Use the woocommerce/customers-query ability to find the customer ID.', 'woocommerce' ), $input['id'] ),
				array( 'status' => 404 )
			);
		}

		if ( null === $user_id ) {
			return new \WP_Error(
				'woocommerce_ability_customer_guest',
				/* translators: %d: customer ID. */
				sprintf( __( 'Customer %d is a guest, with no account to update. Change the addresses on their orders with the woocommerce/order-update ability.', 'woocommerce' ), $input['id'] ),
				array( 'status' => 409 )
			);
		}

		if ( empty( array_diff( array_keys( $input ), array( 'id' ) ) ) ) {
			return new \WP_Error(
				'woocommerce_ability_customer_no_fields',
				__( 'No change was sent. Send at least one customer field to change, in addition to the id.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		try {
			return new \WC_Customer( $user_id );
		} catch ( \Exception $e ) {
			return new \WP_Error(
				'woocommerce_ability_customer_not_found',
				/* translators: %d: customer ID. */
				sprintf( __( 'Customer %d could not be loaded.', 'woocommerce' ), $input['id'] ),
				array( 'status' => 404 )
			);
		}
	}

	/**
	 * Change the customer in memory. Every refused address field comes back in one error.
	 *
	 * @param \WC_Customer $subject Customer.
	 * @param array        $input   Ability input.
	 * @return null|\WP_Error
	 */
	protected function change( $subject, array $input ) {
		foreach ( array( 'first_name', 'last_name' ) as $field ) {
			if ( isset( $input[ $field ] ) ) {
				$subject->{"set_{$field}"}( sanitize_text_field( $input[ $field ] ) );
			}
		}

		$refused = new \WP_Error();
		foreach ( array( 'billing', 'shipping' ) as $address_type ) {
			$error = isset( $input[ $address_type ] ) ? self::set_address( $subject, 'customer', $address_type, $input[ $address_type ] ) : null;
			if ( is_wp_error( $error ) ) {
				$refused->merge_from( $error );
			}
		}

		$this->updated_fields = array();
		foreach ( $subject->get_changes() as $key => $value ) {
			if ( is_array( $value ) ) {
				foreach ( array_keys( $value ) as $address_field ) {
					$this->updated_fields[] = "{$key}.{$address_field}";
				}
				continue;
			}
			$this->updated_fields[] = $key;
		}

		return $refused->has_errors() ? $refused : null;
	}

	/**
	 * The ability output for the saved customer.
	 *
	 * @param \WC_Customer $subject Saved customer.
	 * @return array
	 */
	protected function prepare_response( $subject ) {
		return array(
			'customer' => array(
				'id'             => $this->customer_id,
				'user_id'        => $subject->get_id(),
				'updated_fields' => $this->updated_fields,
			),
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
				'id'         => array(
					'type'        => 'integer',
					'description' => __( 'Customer ID from the woocommerce/customers-query ability.', 'woocommerce' ),
					'minimum'     => 1,
				),
				'first_name' => array( 'type' => 'string' ),
				'last_name'  => array( 'type' => 'string' ),
				'billing'    => self::get_address_input_schema( array_merge( self::get_address_fields(), array( 'email' ) ) ),
				'shipping'   => self::get_address_input_schema( self::get_address_fields() ),
			),
			'required'             => array( 'id' ),
			'additionalProperties' => false,
		);
	}
}
