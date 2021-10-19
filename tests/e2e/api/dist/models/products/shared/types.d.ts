/**
 * A product's stock status.
 *
 * @typedef StockStatus
 * @alias 'instock'|'outofstock'|'onbackorder'|string
 */
export declare type StockStatus = 'instock' | 'outofstock' | 'onbackorder' | string;
/**
 * Base product properties.
 */
export declare type ProductDataUpdateParams = 'created' | 'postStatus' | 'id' | 'permalink' | 'price' | 'priceHtml' | 'description' | 'sku' | 'attributes' | 'images' | 'regularPrice' | 'salePrice' | 'saleStart' | 'saleEnd' | 'metaData' | 'menuOrder' | 'parentId' | 'links';
/**
 * Properties common to all product types.
 */
export declare type ProductCommonUpdateParams = 'name' | 'slug' | 'shortDescription' | 'categories' | 'tags' | 'isFeatured' | 'averageRating' | 'numRatings' | 'catalogVisibility' | 'allowReviews' | 'upsellIds' | 'type' & ProductDataUpdateParams;
/**
 * Cross sells property.
 */
export declare type ProductCrossUpdateParams = 'crossSellIds';
/**
 * Price properties.
 */
export declare type ProductPriceUpdateParams = 'price' | 'priceHtml' | 'regularPrice' | 'salePrice' | 'saleStart' | 'saleEnd';
/**
 * Upsells property.
 */
export declare type ProductUpSellUpdateParams = 'upSellIds';
/**
 * Properties exclusive to the External product type.
 */
export declare type ProductExternalUpdateParams = 'buttonText' | 'externalUrl';
/**
 * Properties exclusive to the Grouped product type.
 */
export declare type ProductGroupedUpdateParams = 'groupedProducts';
/**
 * Properties related to tracking inventory.
 */
export declare type ProductInventoryUpdateParams = 'backorderStatus' | 'canBackorder' | 'trackInventory' | 'onePerOrder' | 'remainingStock';
/**
 * Properties related to sales tax.
 */
export declare type ProductSalesTaxUpdateParams = 'taxClass' | 'taxStatus';
/**
 * Properties related to shipping.
 */
export declare type ProductShippingUpdateParams = 'height' | 'length' | 'weight' | 'width' | 'shippingClass' | 'shippingClassId';
/**
 * Properties exclusive to the Simple product type.
 */
export declare type ProductDeliveryUpdateParams = 'daysToDownload' | 'downloadLimit' | 'downloads' | 'purchaseNote' | 'isVirtual';
/**
 * Properties exclusive to the Variable product type.
 */
export declare type ProductVariableUpdateParams = 'defaultAttributes' | 'variations';
//# sourceMappingURL=types.d.ts.map