<?php
/**
 * TestProductRenameAbility class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\AbilitiesApi;

use Automattic\WooCommerce\Abilities\ExtensibleAbilities;
use Automattic\WooCommerce\Abilities\ActionableAbility;
use Automattic\WooCommerce\Abilities\WCDataSaveTrait;

/**
 * An extension's write ability that renames a product.
 */
class TestProductRenameAbility extends ActionableAbility {

	use WCDataSaveTrait;

	/**
	 * Resource that the ability changes.
	 *
	 * @return string
	 */
	public function get_resource(): string {
		return 'product';
	}

	/**
	 * Load the product.
	 *
	 * @param array $input Ability input.
	 * @return \WC_Product|\WP_Error
	 */
	protected function load( array $input ) {
		$product = wc_get_product( $input['id'] );
		return $product ? $product : new \WP_Error( 'test_not_found', 'Not found.', array( 'status' => 404 ) );
	}

	/**
	 * Rename the product in memory.
	 *
	 * @param \WC_Product $subject Product.
	 * @param array       $input   Ability input.
	 * @return null
	 */
	protected function change( $subject, array $input ) {
		$subject->set_name( $input['name'] );
		return null;
	}

	/**
	 * The ID of a product or an order item, or zero when it is not saved yet.
	 *
	 * @param \WC_Product|\WC_Order_Item $subject A product or an order item.
	 * @return int
	 */
	protected static function get_object_id( $subject ): int {
		return $subject->get_id();
	}

	/**
	 * The type of the object, which the `object_types` of a field are compared with.
	 *
	 * @param \WC_Product|\WC_Order_Item $subject A product or an order item.
	 * @return string
	 */
	protected static function get_object_type( $subject ): string {
		return $subject->get_type();
	}

	/**
	 * The renamed product with its extension fields.
	 *
	 * @param \WC_Product $subject Saved product.
	 * @return array
	 */
	protected function prepare_response( $subject ) {
		return ExtensibleAbilities::add_fields_to_object( array( 'id' => $subject->get_id() ), 'product', $subject, self::get_object_type( $subject ) );
	}
}
