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
	| 'id' | 'permalink' | 'price' | 'priceHtml' | 'type'
	| 'description' | 'sku' | 'categories' | 'tags' | 'isFeatured'
	| 'attributes' | 'images' | 'catalogVisibility' | 'allowReviews'
	| 'regularPrice' | 'salePrice' | 'saleStart' | 'saleEnd'
	| 'metaData' | 'menuOrder' | 'parentId' | 'relatedIds' | 'upsellIds'
	| 'links' | 'relatedIds' | 'menuOrder' | 'parentId';

/**
 * Cross sells property.
 */
export type ProductCrossUpdateParams = 'crossSellIds';

/**
 * Upsells property.
 */
export type ProductUpSellUpdateParams = 'upSellIds';

/**
 * Properties exclusive to the External product type.
 */
export type ProductExternalUpdateParams = 'buttonText' | 'externalUrl';

/**
 * Properties exclusive to the Grouped product type.
 */
export type ProductGroupedTypeUpdateParams = 'groupedProducts';

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
