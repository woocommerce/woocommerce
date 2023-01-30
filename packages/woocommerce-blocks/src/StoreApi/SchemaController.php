<?php
namespace Automattic\WooCommerce\Blocks\StoreApi;

use Exception;
use Schemas\AbstractSchema;
use Automattic\WooCommerce\Blocks\Domain\Services\ExtendRestApi;


/**
 * SchemaController class.
 *
 * @internal This API is used internally by Blocks--it is still in flux and may be subject to revisions.
 */
class SchemaController {

	/**
	 * Stores schema class instances.
	 *
	 * @var AbstractSchema[]
	 */
	protected $schemas = [];

	/**
	 * Stores Rest Extending instance
	 *
	 * @var ExtendRestApi
	 */
	private $extend;

	/**
	 * Constructor.
	 *
	 * @param ExtendRestApi $extend Rest Extending instance.
	 */
	public function __construct( ExtendRestApi $extend ) {
		$this->extend = $extend;
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
			Schemas\BillingAddressSchema::IDENTIFIER   => new Schemas\BillingAddressSchema(
				$this->extend
			),
			Schemas\ShippingAddressSchema::IDENTIFIER  => new Schemas\ShippingAddressSchema(
				$this->extend
			),
			Schemas\CartSchema::IDENTIFIER             => new Schemas\CartSchema(
				$this->extend,
				new Schemas\CartItemSchema(
					$this->extend,
					new Schemas\ImageAttachmentSchema( $this->extend )
				),
				new Schemas\CartCouponSchema( $this->extend ),
				new Schemas\CartShippingRateSchema( $this->extend ),
				new Schemas\ShippingAddressSchema( $this->extend ),
				new Schemas\BillingAddressSchema( $this->extend ),
				new Schemas\ErrorSchema( $this->extend )
			),
			Schemas\CartCouponSchema::IDENTIFIER       => new Schemas\CartCouponSchema( $this->extend ),
			Schemas\CartItemSchema::IDENTIFIER         => new Schemas\CartItemSchema(
				$this->extend,
				new Schemas\ImageAttachmentSchema( $this->extend )
			),
			Schemas\CheckoutSchema::IDENTIFIER         => new Schemas\CheckoutSchema(
				$this->extend,
				new Schemas\BillingAddressSchema( $this->extend ),
				new Schemas\ShippingAddressSchema(
					$this->extend
				)
			),
			Schemas\ProductSchema::IDENTIFIER          => new Schemas\ProductSchema(
				$this->extend,
				new Schemas\ImageAttachmentSchema(
					$this->extend
				)
			),
			Schemas\ProductAttributeSchema::IDENTIFIER => new Schemas\ProductAttributeSchema( $this->extend ),
			Schemas\ProductCategorySchema::IDENTIFIER  => new Schemas\ProductCategorySchema(
				$this->extend,
				new Schemas\ImageAttachmentSchema( $this->extend )
			),
			Schemas\ProductCollectionDataSchema::IDENTIFIER => new Schemas\ProductCollectionDataSchema(
				$this->extend
			),
			Schemas\ProductReviewSchema::IDENTIFIER    => new Schemas\ProductReviewSchema(
				$this->extend,
				new Schemas\ImageAttachmentSchema( $this->extend )
			),
			Schemas\TermSchema::IDENTIFIER             => new Schemas\TermSchema(
				$this->extend
			),
		];
	}
}
