<?php
/**
 * Product write ability class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Internal\AbilitiesApi\ObjectChangeAbility;

defined( 'ABSPATH' ) || exit;

/**
 * Runs the product create and update abilities as object changes, with the
 * steps their definitions provide.
 *
 * @since 11.3.0
 */
final class ProductWriteAbility extends ObjectChangeAbility {

	public const DEFINITIONS = array(
		'woocommerce/product-create' => ProductCreate::class,
		'woocommerce/product-update' => ProductUpdate::class,
	);

	/**
	 * Object type.
	 */
	public static function object_type(): string {
		return 'product';
	}

	/**
	 * Load the product. Never saves.
	 *
	 * @param array $input Ability input.
	 * @return \WC_Product|\WP_Error
	 */
	public function load( array $input ) {
		return $this->definition()::subject( $input );
	}

	/**
	 * Change the product in memory. Never saves.
	 *
	 * @param \WC_Product $subject Product.
	 * @param array       $input   Ability input.
	 * @return null|\WP_Error
	 */
	public function change( $subject, array $input ) {
		return $this->definition()::apply( $subject, $input );
	}

	/**
	 * The response for the saved product.
	 *
	 * @param \WC_Product $subject Saved product.
	 * @return array
	 */
	public function prepare_response( $subject ) {
		return $this->definition()::respond( $subject );
	}

	/**
	 * The definition class of this ability.
	 *
	 * @return class-string<ProductCreate|ProductUpdate>
	 */
	private function definition(): string {
		return self::DEFINITIONS[ $this->get_name() ];
	}
}
