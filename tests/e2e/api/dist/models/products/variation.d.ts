import { ModelID } from '../model';
import { AbstractProductData, IProductDelivery, IProductInventory, IProductPrice, IProductSalesTax, IProductShipping, ProductSearchParams } from './abstract';
import { ProductDataUpdateParams, ProductDeliveryUpdateParams, ProductInventoryUpdateParams, ProductPriceUpdateParams, ProductSalesTaxUpdateParams, ProductShippingUpdateParams, Taxability, ProductDownload, StockStatus, BackorderStatus, ProductDefaultAttribute } from './shared';
import { ObjectLinks } from '../shared-types';
import { CreatesChildModels, DeletesChildModels, ListsChildModels, ModelRepositoryParams, ReadsChildModels, UpdatesChildModels } from '../../framework';
import { HTTPClient } from '../../http';
import { productVariationRESTRepository } from '../../repositories';
/**
 * The parameters that product variations can update.
 */
declare type ProductVariationUpdateParams = ProductDataUpdateParams & ProductDeliveryUpdateParams & ProductInventoryUpdateParams & ProductPriceUpdateParams & ProductSalesTaxUpdateParams & ProductShippingUpdateParams;
/**
 * The parameters embedded in this generic can be used in the ModelRepository in order to give
 * type-safety in an incredibly granular way.
 */
export declare type ProductVariationRepositoryParams = ModelRepositoryParams<ProductVariation, ModelID, ProductSearchParams, ProductVariationUpdateParams>;
/**
 * An interface for listing variable products using the repository.
 *
 * @typedef ListsProductVariations
 * @alias ListsModels.<ProductVariation>
 */
export declare type ListsProductVariations = ListsChildModels<ProductVariationRepositoryParams>;
/**
 * An interface for creating variable products using the repository.
 *
 * @typedef CreatesProductVariations
 * @alias CreatesModels.<ProductVariation>
 */
export declare type CreatesProductVariations = CreatesChildModels<ProductVariationRepositoryParams>;
/**
 * An interface for reading variable products using the repository.
 *
 * @typedef ReadsProductVariations
 * @alias ReadsModels.<ProductVariation>
 */
export declare type ReadsProductVariations = ReadsChildModels<ProductVariationRepositoryParams>;
/**
 * An interface for updating variable products using the repository.
 *
 * @typedef UpdatesProductVariations
 * @alias UpdatesModels.<ProductVariation>
 */
export declare type UpdatesProductVariations = UpdatesChildModels<ProductVariationRepositoryParams>;
/**
 * An interface for deleting variable products using the repository.
 *
 * @typedef DeletesProductVariations
 * @alias DeletesModels.<ProductVariation>
 */
export declare type DeletesProductVariations = DeletesChildModels<ProductVariationRepositoryParams>;
/**
 * The base for the product variation object.
 */
export declare class ProductVariation extends AbstractProductData implements IProductDelivery, IProductInventory, IProductPrice, IProductSalesTax, IProductShipping {
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
     * The variation's links.
     *
     * @type {ReadonlyArray.<ObjectLinks>}
     */
    readonly links: ObjectLinks;
    /**
     * The attributes for the variation.
     *
     * @type {ReadonlyArray.<ProductDefaultAttribute>}
     */
    readonly attributes: readonly ProductDefaultAttribute[];
    /**
     * Creates a new product variation instance with the given properties
     *
     * @param {Object} properties The properties to set in the object.
     */
    constructor(properties?: Partial<ProductVariation>);
    /**
     * Creates a model repository configured for communicating via the REST API.
     *
     * @param {HTTPClient} httpClient The client for communicating via HTTP.
     */
    static restRepository(httpClient: HTTPClient): ReturnType<typeof productVariationRESTRepository>;
}
export {};
//# sourceMappingURL=variation.d.ts.map