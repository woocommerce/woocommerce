<?php
namespace Automattic\WooCommerce\Blocks\StoreApi;

use Exception;
use Automattic\WooCommerce\Blocks\StoreApi\Schemas\AbstractSchema;
use Automattic\WooCommerce\Blocks\StoreApi\Schemas\BillingAddressSchema;
use Automattic\WooCommerce\Blocks\StoreApi\Schemas\ShippingAddressSchema;
use Automattic\WooCommerce\Blocks\StoreApi\Schemas\CartShippingRateSchema;
use Automattic\WooCommerce\Blocks\StoreApi\Schemas\CartSchema;
use Automattic\WooCommerce\Blocks\StoreApi\Schemas\CartItemSchema;
use Automattic\WooCommerce\Blocks\StoreApi\Schemas\CartCouponSchema;
use Automattic\WooCommerce\Blocks\StoreApi\Schemas\CartFeeSchema;
use Automattic\WooCommerce\Blocks\StoreApi\Schemas\ErrorSchema;
use Automattic\WooCommerce\Blocks\StoreApi\Schemas\CheckoutSchema;
use Automattic\WooCommerce\Blocks\StoreApi\Schemas\ProductSchema;
use Automattic\WooCommerce\Blocks\StoreApi\Schemas\ImageAttachmentSchema;
use Automattic\WooCommerce\Blocks\StoreApi\Schemas\ProductAttributeSchema;
use Automattic\WooCommerce\Blocks\StoreApi\Schemas\ProductCategorySchema;
use Automattic\WooCommerce\Blocks\StoreApi\Schemas\ProductCollectionDataSchema;
use Automattic\WooCommerce\Blocks\StoreApi\Schemas\ProductReviewSchema;
use Automattic\WooCommerce\Blocks\StoreApi\Schemas\TermSchema;
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
		$this->schemas                                       = [];
		$this->schemas[ ErrorSchema::IDENTIFIER ]            = new ErrorSchema( $this->extend );
		$this->schemas[ ImageAttachmentSchema::IDENTIFIER ]  = new ImageAttachmentSchema( $this->extend );
		$this->schemas[ TermSchema::IDENTIFIER ]             = new TermSchema( $this->extend );
		$this->schemas[ BillingAddressSchema::IDENTIFIER ]   = new BillingAddressSchema( $this->extend );
		$this->schemas[ ShippingAddressSchema::IDENTIFIER ]  = new ShippingAddressSchema( $this->extend );
		$this->schemas[ CartShippingRateSchema::IDENTIFIER ] = new CartShippingRateSchema( $this->extend );
		$this->schemas[ CartCouponSchema::IDENTIFIER ]       = new CartCouponSchema( $this->extend );
		$this->schemas[ CartFeeSchema::IDENTIFIER ]          = new CartFeeSchema( $this->extend );
		$this->schemas[ CartItemSchema::IDENTIFIER ]         = new CartItemSchema(
			$this->extend,
			$this->schemas[ ImageAttachmentSchema::IDENTIFIER ]
		);
		$this->schemas[ CartSchema::IDENTIFIER ]             = new CartSchema(
			$this->extend,
			$this->schemas[ CartItemSchema::IDENTIFIER ],
			$this->schemas[ CartCouponSchema::IDENTIFIER ],
			$this->schemas[ CartFeeSchema::IDENTIFIER ],
			$this->schemas[ CartShippingRateSchema::IDENTIFIER ],
			$this->schemas[ ShippingAddressSchema::IDENTIFIER ],
			$this->schemas[ BillingAddressSchema::IDENTIFIER ],
			$this->schemas[ ErrorSchema::IDENTIFIER ]
		);
		$this->schemas[ CheckoutSchema::IDENTIFIER ]         = new CheckoutSchema(
			$this->extend,
			$this->schemas[ BillingAddressSchema::IDENTIFIER ],
			$this->schemas[ ShippingAddressSchema::IDENTIFIER ]
		);
		$this->schemas[ ProductSchema::IDENTIFIER ]          = new ProductSchema(
			$this->extend,
			$this->schemas[ ImageAttachmentSchema::IDENTIFIER ]
		);
		$this->schemas[ ProductAttributeSchema::IDENTIFIER ] = new ProductAttributeSchema( $this->extend );
		$this->schemas[ ProductCategorySchema::IDENTIFIER ]  = new ProductCategorySchema(
			$this->extend,
			$this->schemas[ ImageAttachmentSchema::IDENTIFIER ]
		);
		$this->schemas[ ProductCollectionDataSchema::IDENTIFIER ] = new ProductCollectionDataSchema( $this->extend );
		$this->schemas[ ProductReviewSchema::IDENTIFIER ]         = new ProductReviewSchema(
			$this->extend,
			$this->schemas[ ImageAttachmentSchema::IDENTIFIER ]
		);
	}
}
