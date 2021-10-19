import { AbstractProduct, IProductCommon, IProductCrossSells, IProductDelivery, IProductInventory, IProductPrice, IProductSalesTax, IProductShipping, IProductUpSells, ProductSearchParams } from './abstract';
import { ProductCommonUpdateParams, ProductCrossUpdateParams, ProductDeliveryUpdateParams, ProductInventoryUpdateParams, ProductPriceUpdateParams, ProductSalesTaxUpdateParams, ProductShippingUpdateParams, ProductUpSellUpdateParams, ProductDownload, StockStatus, BackorderStatus, Taxability } from './shared';
import { HTTPClient } from '../../http';
import { simpleProductRESTRepository } from '../../repositories';
import { CreatesModels, DeletesModels, ListsModels, ModelRepositoryParams, ReadsModels, UpdatesModels } from '../../framework';
/**
 * The parameters that simple products can update.
 */
declare type SimpleProductUpdateParams = ProductDeliveryUpdateParams & ProductCommonUpdateParams & ProductCrossUpdateParams & ProductInventoryUpdateParams & ProductPriceUpdateParams & ProductSalesTaxUpdateParams & ProductShippingUpdateParams & ProductUpSellUpdateParams;
/**
 * The parameters embedded in this generic can be used in the ModelRepository in order to give
 * type-safety in an incredibly granular way.
 */
export declare type SimpleProductRepositoryParams = ModelRepositoryParams<SimpleProduct, never, ProductSearchParams, SimpleProductUpdateParams>;
/**
 * An interface for listing simple products using the repository.
 *
 * @typedef ListsSimpleProducts
 * @alias ListsModels.<SimpleProduct>
 */
export declare type ListsSimpleProducts = ListsModels<SimpleProductRepositoryParams>;
/**
 * An interface for creating simple products using the repository.
 *
 * @typedef CreatesSimpleProducts
 * @alias CreatesModels.<SimpleProduct>
 */
export declare type CreatesSimpleProducts = CreatesModels<SimpleProductRepositoryParams>;
/**
 * An interface for reading simple products using the repository.
 *
 * @typedef ReadsSimpleProducts
 * @alias ReadsModels.<SimpleProduct>
 */
export declare type ReadsSimpleProducts = ReadsModels<SimpleProductRepositoryParams>;
/**
 * An interface for updating simple products using the repository.
 *
 * @typedef UpdatesSimpleProducts
 * @alias UpdatesModels.<SimpleProduct>
 */
export declare type UpdatesSimpleProducts = UpdatesModels<SimpleProductRepositoryParams>;
/**
 * An interface for deleting simple products using the repository.
 *
 * @typedef DeletesSimpleProducts
 * @alias DeletesModels.<SimpleProduct>
 */
export declare type DeletesSimpleProducts = DeletesModels<SimpleProductRepositoryParams>;
/**
 * The base for the simple product object.
 */
export declare class SimpleProduct extends AbstractProduct implements IProductCommon, IProductCrossSells, IProductDelivery, IProductInventory, IProductPrice, IProductSalesTax, IProductShipping, IProductUpSells {
    /**
     * @see ./abstracts/cross-sells.ts
     */
    readonly crossSellIds: Array<number>;
    /**
     * @see ./abstracts/upsell.ts
     */
    readonly upSellIds: Array<number>;
    /**
     * @see ./abstracts/delivery.ts
     */
    readonly isVirtual: boolean;
    readonly isDownloadable: boolean;
    readonly downloads: readonly ProductDownload[];
    readonly downloadLimit: number;
    readonly daysToDownload: number;
    readonly purchaseNote: string;
    /**
     * @see ./abstracts/inventory.ts
     */
    readonly onePerOrder: boolean;
    readonly trackInventory: boolean;
    readonly remainingStock: number;
    readonly stockStatus: StockStatus;
    readonly backorderStatus: BackorderStatus;
    readonly canBackorder: boolean;
    readonly isOnBackorder: boolean;
    /**
     * @see ./abstracts/price.ts
     */
    readonly price: string;
    readonly priceHtml: string;
    readonly regularPrice: string;
    readonly onSale: boolean;
    readonly salePrice: string;
    readonly saleStart: Date | null;
    readonly saleEnd: Date | null;
    /**
     * @see ./abstracts/sales-tax.ts
     */
    readonly taxStatus: Taxability;
    readonly taxClass: string;
    /**
     * @see ./abstracts/shipping.ts
     */
    readonly weight: string;
    readonly length: string;
    readonly width: string;
    readonly height: string;
    readonly requiresShipping: boolean;
    readonly isShippingTaxable: boolean;
    readonly shippingClass: string;
    readonly shippingClassId: number;
    /**
     * Creates a new simple product instance with the given properties
     *
     * @param {Object} properties The properties to set in the object.
     */
    constructor(properties?: Partial<SimpleProduct>);
    /**
     * Creates a model repository configured for communicating via the REST API.
     *
     * @param {HTTPClient} httpClient The client for communicating via HTTP.
     */
    static restRepository(httpClient: HTTPClient): ReturnType<typeof simpleProductRESTRepository>;
}
export {};
//# sourceMappingURL=simple-product.d.ts.map