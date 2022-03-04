<?php
/**
 * Class Aliases for graceful Backwards compatibility.
 *
 * This file is autoloaded via composer.json and maps the old namespaces to new namespaces.
 */

// Map old to new namespaces for utils and exceptions.
class_alias( Automattic\WooCommerce\StoreApi\Exceptions\RouteException::class, Automattic\WooCommerce\Blocks\StoreApi\Routes\RouteException::class );
class_alias( Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema::class, Automattic\WooCommerce\Blocks\Domain\Services\ExtendRestApi::class );
class_alias( Automattic\WooCommerce\StoreApi\SchemaController::class, Automattic\WooCommerce\Blocks\StoreApi\SchemaController::class );
class_alias( Automattic\WooCommerce\StoreApi\RoutesController::class, Automattic\WooCommerce\Blocks\StoreApi\RoutesController::class );
class_alias( Automattic\WooCommerce\StoreApi\Formatters::class, Automattic\WooCommerce\Blocks\StoreApi\Formatters::class );
class_alias( Automattic\WooCommerce\StoreApi\Payments\PaymentResult::class, Automattic\WooCommerce\Blocks\Payments\PaymentResult::class );
class_alias( Automattic\WooCommerce\StoreApi\Payments\PaymentContext::class, Automattic\WooCommerce\Blocks\Payments\PaymentContext::class );

// Map old schemas to V1 schemas under new namespace.
class_alias( Automattic\WooCommerce\StoreApi\Schemas\V1\AbstractAddressSchema::class, Automattic\WooCommerce\Blocks\StoreApi\Schemas\AbstractAddressSchema::class );
class_alias( Automattic\WooCommerce\StoreApi\Schemas\V1\AbstractSchema::class, Automattic\WooCommerce\Blocks\StoreApi\Schemas\AbstractSchema::class );
class_alias( Automattic\WooCommerce\StoreApi\Schemas\V1\BillingAddressSchema::class, Automattic\WooCommerce\Blocks\StoreApi\Schemas\BillingAddressSchema::class );
class_alias( Automattic\WooCommerce\StoreApi\Schemas\V1\CartCouponSchema::class, Automattic\WooCommerce\Blocks\StoreApi\Schemas\CartCouponSchema::class );
class_alias( Automattic\WooCommerce\StoreApi\Schemas\V1\CartExtensionsSchema::class, Automattic\WooCommerce\Blocks\StoreApi\Schemas\CartExtensionsSchema::class );
class_alias( Automattic\WooCommerce\StoreApi\Schemas\V1\CartFeeSchema::class, Automattic\WooCommerce\Blocks\StoreApi\Schemas\CartFeeSchema::class );
class_alias( Automattic\WooCommerce\StoreApi\Schemas\V1\CartItemSchema::class, Automattic\WooCommerce\Blocks\StoreApi\Schemas\CartItemSchema::class );
class_alias( Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema::class, Automattic\WooCommerce\Blocks\StoreApi\Schemas\CartSchema::class );
class_alias( Automattic\WooCommerce\StoreApi\Schemas\V1\CartShippingRateSchema::class, Automattic\WooCommerce\Blocks\StoreApi\Schemas\CartShippingRateSchema::class );
class_alias( Automattic\WooCommerce\StoreApi\Schemas\V1\CheckoutSchema::class, Automattic\WooCommerce\Blocks\StoreApi\Schemas\CheckoutSchema::class );
class_alias( Automattic\WooCommerce\StoreApi\Schemas\V1\ErrorSchema::class, Automattic\WooCommerce\Blocks\StoreApi\Schemas\ErrorSchema::class );
class_alias( Automattic\WooCommerce\StoreApi\Schemas\V1\ImageAttachmentSchema::class, Automattic\WooCommerce\Blocks\StoreApi\Schemas\ImageAttachmentSchema::class );
class_alias( Automattic\WooCommerce\StoreApi\Schemas\V1\OrderCouponSchema::class, Automattic\WooCommerce\Blocks\StoreApi\Schemas\OrderCouponSchema::class );
class_alias( Automattic\WooCommerce\StoreApi\Schemas\V1\ProductAttributeSchema::class, Automattic\WooCommerce\Blocks\StoreApi\Schemas\ProductAttributeSchema::class );
class_alias( Automattic\WooCommerce\StoreApi\Schemas\V1\ProductCategorySchema::class, Automattic\WooCommerce\Blocks\StoreApi\Schemas\ProductCategorySchema::class );
class_alias( Automattic\WooCommerce\StoreApi\Schemas\V1\ProductCollectionDataSchema::class, Automattic\WooCommerce\Blocks\StoreApi\Schemas\ProductCollectionDataSchema::class );
class_alias( Automattic\WooCommerce\StoreApi\Schemas\V1\ProductReviewSchema::class, Automattic\WooCommerce\Blocks\StoreApi\Schemas\ProductReviewSchema::class );
class_alias( Automattic\WooCommerce\StoreApi\Schemas\V1\ProductSchema::class, Automattic\WooCommerce\Blocks\StoreApi\Schemas\ProductSchema::class );
class_alias( Automattic\WooCommerce\StoreApi\Schemas\V1\ShippingAddressSchema::class, Automattic\WooCommerce\Blocks\StoreApi\Schemas\ShippingAddressSchema::class );
class_alias( Automattic\WooCommerce\StoreApi\Schemas\V1\TermSchema::class, Automattic\WooCommerce\Blocks\StoreApi\Schemas\TermSchema::class );

// Map old routes to V1 routes under new namespace.
class_alias( Automattic\WooCommerce\StoreApi\Routes\V1\AbstractCartRoute::class, Automattic\WooCommerce\Blocks\StoreApi\Routes\AbstractCartRoute::class );
class_alias( Automattic\WooCommerce\StoreApi\Routes\V1\AbstractRoute::class, Automattic\WooCommerce\Blocks\StoreApi\Routes\AbstractRoute::class );
class_alias( Automattic\WooCommerce\StoreApi\Routes\V1\AbstractTermsRoute::class, Automattic\WooCommerce\Blocks\StoreApi\Routes\AbstractTermsRoute::class );
class_alias( Automattic\WooCommerce\StoreApi\Routes\V1\Batch::class, Automattic\WooCommerce\Blocks\StoreApi\Routes\Batch::class );
class_alias( Automattic\WooCommerce\StoreApi\Routes\V1\Cart::class, Automattic\WooCommerce\Blocks\StoreApi\Routes\Cart::class );
class_alias( Automattic\WooCommerce\StoreApi\Routes\V1\CartAddItem::class, Automattic\WooCommerce\Blocks\StoreApi\Routes\CartAddItem::class );
class_alias( Automattic\WooCommerce\StoreApi\Routes\V1\CartApplyCoupon::class, Automattic\WooCommerce\Blocks\StoreApi\Routes\CartApplyCoupon::class );
class_alias( Automattic\WooCommerce\StoreApi\Routes\V1\CartCoupons::class, Automattic\WooCommerce\Blocks\StoreApi\Routes\CartCoupons::class );
class_alias( Automattic\WooCommerce\StoreApi\Routes\V1\CartCouponsByCode::class, Automattic\WooCommerce\Blocks\StoreApi\Routes\CartCouponsByCode::class );
class_alias( Automattic\WooCommerce\StoreApi\Routes\V1\CartExtensions::class, Automattic\WooCommerce\Blocks\StoreApi\Routes\CartExtensions::class );
class_alias( Automattic\WooCommerce\StoreApi\Routes\V1\CartItems::class, Automattic\WooCommerce\Blocks\StoreApi\Routes\CartItems::class );
class_alias( Automattic\WooCommerce\StoreApi\Routes\V1\CartItemsByKey::class, Automattic\WooCommerce\Blocks\StoreApi\Routes\CartItemsByKey::class );
class_alias( Automattic\WooCommerce\StoreApi\Routes\V1\CartRemoveCoupon::class, Automattic\WooCommerce\Blocks\StoreApi\Routes\CartRemoveCoupon::class );
class_alias( Automattic\WooCommerce\StoreApi\Routes\V1\CartRemoveItem::class, Automattic\WooCommerce\Blocks\StoreApi\Routes\CartRemoveItem::class );
class_alias( Automattic\WooCommerce\StoreApi\Routes\V1\CartSelectShippingRate::class, Automattic\WooCommerce\Blocks\StoreApi\Routes\CartSelectShippingRate::class );
class_alias( Automattic\WooCommerce\StoreApi\Routes\V1\CartUpdateCustomer::class, Automattic\WooCommerce\Blocks\StoreApi\Routes\CartUpdateCustomer::class );
class_alias( Automattic\WooCommerce\StoreApi\Routes\V1\CartUpdateItem::class, Automattic\WooCommerce\Blocks\StoreApi\Routes\CartUpdateItem::class );
class_alias( Automattic\WooCommerce\StoreApi\Routes\V1\Checkout::class, Automattic\WooCommerce\Blocks\StoreApi\Routes\Checkout::class );
class_alias( Automattic\WooCommerce\StoreApi\Routes\V1\ProductAttributes::class, Automattic\WooCommerce\Blocks\StoreApi\Routes\ProductAttributes::class );
class_alias( Automattic\WooCommerce\StoreApi\Routes\V1\ProductAttributesById::class, Automattic\WooCommerce\Blocks\StoreApi\Routes\ProductAttributesById::class );
class_alias( Automattic\WooCommerce\StoreApi\Routes\V1\ProductAttributeTerms::class, Automattic\WooCommerce\Blocks\StoreApi\Routes\ProductAttributeTerms::class );
class_alias( Automattic\WooCommerce\StoreApi\Routes\V1\ProductCategories::class, Automattic\WooCommerce\Blocks\StoreApi\Routes\ProductCategories::class );
class_alias( Automattic\WooCommerce\StoreApi\Routes\V1\ProductCategoriesById::class, Automattic\WooCommerce\Blocks\StoreApi\Routes\ProductCategoriesById::class );
class_alias( Automattic\WooCommerce\StoreApi\Routes\V1\ProductCollectionData::class, Automattic\WooCommerce\Blocks\StoreApi\Routes\ProductCollectionData::class );
class_alias( Automattic\WooCommerce\StoreApi\Routes\V1\ProductReviews::class, Automattic\WooCommerce\Blocks\StoreApi\Routes\ProductReviews::class );
class_alias( Automattic\WooCommerce\StoreApi\Routes\V1\Products::class, Automattic\WooCommerce\Blocks\StoreApi\Routes\Products::class );
class_alias( Automattic\WooCommerce\StoreApi\Routes\V1\ProductsById::class, Automattic\WooCommerce\Blocks\StoreApi\Routes\ProductsById::class );
class_alias( Automattic\WooCommerce\StoreApi\Routes\V1\ProductTags::class, Automattic\WooCommerce\Blocks\StoreApi\Routes\ProductTags::class );
