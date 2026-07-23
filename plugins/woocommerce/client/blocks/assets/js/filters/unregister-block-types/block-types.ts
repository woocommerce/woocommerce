/**
 * WooCommerce block types hidden from post editors.
 */
export const POST_EDITOR_BLOCK_TYPES_TO_UNREGISTER = [
	'woocommerce/breadcrumbs',
	'woocommerce/catalog-sorting',
	'woocommerce/legacy-template',
	'woocommerce/product-results-count',
	'woocommerce/product-reviews',
	'woocommerce/order-confirmation-status',
	'woocommerce/order-confirmation-summary',
	'woocommerce/order-confirmation-totals',
	'woocommerce/order-confirmation-totals-wrapper',
	'woocommerce/order-confirmation-downloads',
	'woocommerce/order-confirmation-downloads-wrapper',
	'woocommerce/order-confirmation-billing-address',
	'woocommerce/order-confirmation-shipping-address',
	'woocommerce/order-confirmation-billing-wrapper',
	'woocommerce/order-confirmation-shipping-wrapper',
	'woocommerce/order-confirmation-additional-information',
	'woocommerce/order-confirmation-additional-fields-wrapper',
	'woocommerce/order-confirmation-additional-fields',
];

/**
 * WooCommerce block types allowed in Widget Areas. New blocks won't be
 * exposed in the Widget Area unless specifically added here.
 */
export const WIDGET_EDITOR_ALLOWED_BLOCK_TYPES = [
	'woocommerce/all-reviews',
	'woocommerce/breadcrumbs',
	'woocommerce/cart-link',
	'woocommerce/catalog-sorting',
	'woocommerce/classic-shortcode',
	'woocommerce/customer-account',
	'woocommerce/dropdown',
	'woocommerce/featured-category',
	'woocommerce/featured-product',
	'woocommerce/mini-cart',
	'woocommerce/product-categories',
	'woocommerce/product-results-count',
	'woocommerce/product-search',
	'woocommerce/reviews-by-category',
	'woocommerce/reviews-by-product',
	'woocommerce/product-filters',
	'woocommerce/product-filter-status',
	'woocommerce/product-filter-price',
	'woocommerce/product-filter-price-slider',
	'woocommerce/product-filter-attribute',
	'woocommerce/product-filter-rating',
	'woocommerce/product-filter-active',
	'woocommerce/product-filter-removable-chips',
	'woocommerce/product-filter-clear-button',
	'woocommerce/product-filter-checkbox-list',
	'woocommerce/product-filter-chips',
	'woocommerce/product-filter-taxonomy',

	// Keep hidden legacy filter blocks for backward compatibility.
	'woocommerce/active-filters',
	'woocommerce/attribute-filter',
	'woocommerce/filter-wrapper',
	'woocommerce/price-filter',
	'woocommerce/rating-filter',
	'woocommerce/stock-filter',
	// End: legacy filter blocks.

	// Below product grids are hidden from inserter however they could have been used in widgets.
	// Keep them for backward compatibility.
	'woocommerce/handpicked-products',
	'woocommerce/product-best-sellers',
	'woocommerce/product-new',
	'woocommerce/product-on-sale',
	'woocommerce/product-top-rated',
	'woocommerce/products-by-attribute',
	'woocommerce/product-category',
	'woocommerce/product-tag',
	// End: legacy product grids blocks.
];
