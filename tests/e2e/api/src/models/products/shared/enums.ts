/**
 * An enum describing the catalog visibility options for products.
 *
 * @enum {string}
 */
export enum CatalogVisibility {
	/**
	 * The product should be visible everywhere.
	 */
	Everywhere = 'visible',

	/**
	 * The product should only be visible in the shop catalog.
	 */
	ShopOnly = 'catalog',

	/**
	 * The product should only be visible in search results.
	 */
	SearchOnly = 'search',

	/**
	 * The product should be hidden everywhere.
	 */
	Hidden = 'hidden'
}

/**
 * Indicates the taxability of a product.
 *
 * @enum {string}
 */
export enum Taxability {
	/**
	 * The product and shipping are both taxable.
	 */
	ProductAndShipping = 'taxable',

	/**
	 * Only the product's shipping is taxable.
	 */
	ShippingOnly = 'shipping',

	/**
	 * The product and shipping are not taxable.
	 */
	None = 'none'
}

/**
 * Indicates the status for backorders for a product.
 *
 * @enum {string}
 */
export enum BackorderStatus {
	/**
	 * The product is allowed to be backordered.
	 */
	Allowed = 'yes',

	/**
	 * The product is allowed to be backordered but it will notify the customer of that fact.
	 */
	AllowedWithNotification = 'notify',

	/**
	 * The product is not allowed to be backordered.
	 */
	NotAllowed = 'no'
}
