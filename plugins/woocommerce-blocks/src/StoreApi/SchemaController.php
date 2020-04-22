<?php
/**
 * Register Schemas.
 *
 * @internal This API is used internally by Blocks--it is still in flux and may be subject to revisions.
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\StoreApi;

defined( 'ABSPATH' ) || exit;

use Exception;
use Schemas\AbstractSchema;

/**
 * SchemaController class.
 */
class SchemaController {

	/**
	 * Stores schema class instances.
	 *
	 * @var AbstractSchema[]
	 */
	protected $schemas = [];

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->initialize();
	}

	/**
	 * Get a schema class instance.
	 *
	 * @throws Exception If the schema does not exist.
	 *
	 * @param string $name Name of schema.
	 * @return AbstractSchema
	 */
	public function get( $name ) {
		if ( ! isset( $this->schemas[ $name ] ) ) {
			throw new Exception( $name . ' schema does not exist' );
		}
		return $this->schemas[ $name ];
	}

	/**
	 * Load schema class instances.
	 */
	protected function initialize() {
		$this->schemas = [
			'cart'                    => new Schemas\CartSchema(
				new Schemas\CartItemSchema(),
				new Schemas\CartCouponSchema(),
				new Schemas\CartShippingRateSchema(),
				new Schemas\ShippingAddressSchema(),
				new Schemas\ErrorSchema()
			),
			'cart-coupon'             => new Schemas\CartCouponSchema(),
			'cart-item'               => new Schemas\CartItemSchema(),
			'checkout'                => new Schemas\CheckoutSchema(
				new Schemas\BillingAddressSchema(),
				new Schemas\ShippingAddressSchema()
			),
			'product'                 => new Schemas\ProductSchema(),
			'product-attribute'       => new Schemas\ProductAttributeSchema(),
			'product-collection-data' => new Schemas\ProductCollectionDataSchema(),
			'term'                    => new Schemas\TermSchema(),
		];
	}
}
