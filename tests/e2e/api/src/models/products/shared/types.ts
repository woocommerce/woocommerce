/**
 * A product's stock status.
 *
 * @typedef StockStatus
 * @alias 'instock'|'outofstock'|'onbackorder'|string
 */
export type StockStatus = 'instock' | 'outofstock' | 'onbackorder' | string

/**
 * Properties common to all product types.
 */
export type ProductCommonUpdateParams = 'name' | 'slug' | 'created' | 'postStatus' | 'shortDescription'
	| 'id' | 'permalink' | 'type' | 'description' | 'sku' | 'categories' | 'tags' | 'isFeatured'
	| 'attributes' | 'images' | 'catalogVisibility' | 'allowReviews'
	| 'metaData' | 'menuOrder' | 'parentId' | 'relatedIds' | 'upsellIds'
	| 'links' | 'relatedIds' | 'menuOrder' | 'parentId';

/**
 * Cross sells property.
 */
export type ProductCrossUpdateParams = 'crossSellIds';

/**
 * Price properties.
 */
export type ProductPriceUpdateParams = 'price' | 'priceHtml' | 'regularPrice'
	| 'salePrice' | 'saleStart' | 'saleEnd';

/**
 * Upsells property.
 */
export type ProductUpSellUpdateParams = 'upSellIds';

/**
 * Properties exclusive to the External product type.
 */
export type ProductExternalTypeUpdateParams = 'buttonText' | 'externalUrl';

/**
 * Properties exclusive to the Grouped product type.
 */
export type ProductGroupedUpdateParams = 'groupedProducts';

/**
 * Properties related to tracking inventory.
 */
export type ProductInventoryUpdateParams = 'backorderStatus' | 'canBackorder' | 'trackInventory'
	| 'onePerOrder' | 'remainingStock';

/**
 * Properties related to sales tax.
 */
export type ProductSalesTaxUpdateParams = 'taxClass' | 'taxStatus';

/**
 * Properties related to shipping.
 */
export type ProductShippingUpdateParams = 'height' | 'length' | 'weight' | 'width'
	| 'shippingClass' | 'shippingClassId';

/**
 * Properties exclusive to the Simple product type.
 */
export type ProductDeliveryUpdateParams = 'daysToDownload' | 'downloadLimit' | 'downloads'
	| 'purchaseNote' | 'isVirtual';

/**
 * Properties exclusive to the Variable product type.
 */
export type ProductVariableTypeUpdateParams = 'defaultAttributes' | 'variations';
