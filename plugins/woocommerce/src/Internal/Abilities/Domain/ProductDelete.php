<?php
/**
 * Product delete ability definition file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Internal\Abilities\AbilityDefinition;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\ProductAbilityTrait;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the WooCommerce product delete ability.
 */
class ProductDelete extends DomainAbility implements AbilityDefinition {

	use ProductAbilityTrait;

	/**
	 * Get the ability name.
	 *
	 * @return string
	 *
	 * @since 10.9.0
	 */
	public static function get_name(): string {
		return 'woocommerce/product-delete';
	}

	/**
	 * Get the ability registration arguments.
	 *
	 * @return array
	 *
	 * @since 10.9.0
	 */
	public static function get_registration_args(): array {
		return array(
			'label'               => __( 'Delete Product', 'woocommerce' ),
			'description'         => __(
				'Delete a WooCommerce product using WooCommerce product APIs.',
				'woocommerce'
			),
			'category'            => 'woocommerce',
			'input_schema'        => self::get_input_schema(),
			'output_schema'       => self::get_delete_output_schema(),
			'execute_callback'    => array( __CLASS__, 'execute' ),
			'permission_callback' => array( __CLASS__, 'can_delete_product' ),
			'meta'                => self::get_domain_meta( false, false, true ),
		);
	}

	/**
	 * Delete a product.
	 *
	 * @param array $input Ability input.
	 * @return array|\WP_Error
	 *
	 * @since 10.9.0
	 */
	public static function execute( array $input ) {
		$product = self::get_product_from_input( $input );

		if ( is_wp_error( $product ) ) {
			return $product;
		}

		$product_id = $product->get_id();
		$deleted    = $product->delete( (bool) ( $input['force'] ?? false ) );

		return array(
			'deleted' => (bool) $deleted,
			'id'      => $product_id,
		);
	}

	/**
	 * Check product deletion access.
	 *
	 * @param mixed $input Ability input.
	 * @return bool
	 *
	 * @since 10.9.0
	 */
	public static function can_delete_product( $input = array() ): bool {
		$product_id = self::get_input_id( $input );

		return $product_id > 0 && wc_rest_check_post_permissions( 'product', 'delete', $product_id );
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
				'id'    => array( 'type' => 'integer' ),
				'force' => array(
					'type'    => 'boolean',
					'default' => false,
				),
			),
			'required'             => array( 'id' ),
			'additionalProperties' => false,
		);
	}
}
