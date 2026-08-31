<?php
/**
 * Product delete ability definition file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;
use Automattic\WooCommerce\Enums\ProductType;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\ProductAbilityTrait;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the WooCommerce product delete ability.
 */
class ProductDelete extends AbstractDomainAbility implements AbilityDefinition {

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
			'label'               => __( 'Delete product', 'woocommerce' ),
			'description'         => __(
				'Delete, trash, or restore a product.',
				'woocommerce'
			),
			'category'            => 'woocommerce',
			'input_schema'        => self::get_input_schema(),
			'output_schema'       => self::get_delete_output_schema(),
			'execute_callback'    => array( __CLASS__, 'execute' ),
			'permission_callback' => array( __CLASS__, 'can_delete_product' ),
			'meta'                => array(
				'show_in_rest' => true,
				'mcp'          => array(
					'public' => true,
					'type'   => 'tool',
				),
				'annotations'  => array(
					'readonly'    => false,
					'idempotent'  => true,
					'destructive' => true,
				),
			),
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

		if ( $product->is_type( ProductType::VARIATION ) ) {
			return new \WP_Error(
				'woocommerce_product_type_unsupported',
				__( 'Product type is not supported by this ability.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$product_id = $product->get_id();
		$force      = (bool) ( $input['force'] ?? false );

		/**
		 * Filter whether a product supports trashing in WooCommerce domain abilities.
		 *
		 * @param bool        $supports_trash Whether the product supports trashing.
		 * @param \WC_Product $product        The product being considered for trashing.
		 *
		 * @since 10.9.0
		 */
		$supports_trash = apply_filters( 'woocommerce_product_object_trashable', EMPTY_TRASH_DAYS > 0, $product );

		if ( ! $force && ! $supports_trash ) {
			return new \WP_Error(
				'woocommerce_trash_not_supported',
				__( 'Trash is disabled on this site. Pass force: true to permanently delete.', 'woocommerce' ),
				array( 'status' => 501 )
			);
		}

		$deleted = $product->delete( $force );

		if (
			! $deleted
			|| ( $force && null !== get_post( $product_id ) )
			|| ( ! $force && 'trash' !== get_post_status( $product_id ) )
		) {
			return new \WP_Error(
				'woocommerce_product_delete_failed',
				__( 'Failed to delete product.', 'woocommerce' ),
				array( 'status' => 500 )
			);
		}

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
		$product_id = self::get_id_from_input( $input );

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
				'id'    => array(
					'type'    => 'integer',
					'minimum' => 1,
				),
				'force' => array(
					'type'        => 'boolean',
					'description' => __(
						'Permanently delete the product. Defaults to false, which moves the product to trash.',
						'woocommerce'
					),
					'default'     => false,
				),
			),
			'required'             => array( 'id' ),
			'additionalProperties' => false,
		);
	}
}
