<?php
/**
 * TestProductWithItemAbility class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\AbilitiesApi;

/**
 * An extension's write ability that renames a product and changes an order item, a secondary object of another resource.
 */
class TestProductWithItemAbility extends TestProductRenameAbility {

	/**
	 * Resource name that the secondary object carries.
	 *
	 * @var string
	 */
	public static string $item_resource = 'order_item';

	/**
	 * The order item of the call.
	 *
	 * @var \WC_Order_Item_Product
	 */
	private \WC_Order_Item_Product $item;

	/**
	 * Rename the product in memory, and return a new order item as a secondary object of the `$item_resource` resource.
	 *
	 * @param \WC_Product $subject Product.
	 * @param array       $input   Ability input.
	 * @return array<int, array{0: \WC_Data, 1: array, 2: string}>
	 */
	public function change( $subject, array $input ) {
		parent::change( $subject, $input );
		$this->item = new \WC_Order_Item_Product();
		return array( array( $this->item, $input['item_extensions'] ?? array(), self::$item_resource ) );
	}

	/**
	 * Save the product, then the order item.
	 *
	 * @param \WC_Product $subject Product.
	 * @return null|\WP_Error
	 */
	protected function save( $subject ) {
		$this->item->save();
		return parent::save( $subject );
	}
}
