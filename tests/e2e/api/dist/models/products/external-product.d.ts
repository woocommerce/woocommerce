import { AbstractProduct, IProductCommon, IProductExternal, IProductPrice, IProductSalesTax, IProductUpSells, ProductSearchParams } from './abstract';
import { ProductCommonUpdateParams, ProductExternalUpdateParams, ProductPriceUpdateParams, ProductSalesTaxUpdateParams, ProductUpSellUpdateParams, Taxability } from './shared';
import { HTTPClient } from '../../http';
import { externalProductRESTRepository } from '../../repositories';
import { CreatesModels, DeletesModels, ListsModels, ModelRepositoryParams, ReadsModels, UpdatesModels } from '../../framework';
/**
 * The parameters that external products can update.
 */
declare type ExternalProductUpdateParams = ProductCommonUpdateParams & ProductExternalUpdateParams & ProductPriceUpdateParams & ProductSalesTaxUpdateParams & ProductUpSellUpdateParams;
/**
 * The parameters embedded in this generic can be used in the ModelRepository in order to give
 * type-safety in an incredibly granular way.
 */
export declare type ExternalProductRepositoryParams = ModelRepositoryParams<ExternalProduct, never, ProductSearchParams, ExternalProductUpdateParams>;
/**
 * An interface for listing external products using the repository.
 *
 * @typedef ListsExternalProducts
 * @alias ListsModels.<ExternalProduct>
 */
export declare type ListsExternalProducts = ListsModels<ExternalProductRepositoryParams>;
/**
 * An interface for external simple products using the repository.
 *
 * @typedef CreatesExternalProducts
 * @alias CreatesModels.<ExternalProduct>
 */
export declare type CreatesExternalProducts = CreatesModels<ExternalProductRepositoryParams>;
/**
 * An interface for reading external products using the repository.
 *
 * @typedef ReadsExternalProducts
 * @alias ReadsModels.<ExternalProduct>
 */
export declare type ReadsExternalProducts = ReadsModels<ExternalProductRepositoryParams>;
/**
 * An interface for updating external products using the repository.
 *
 * @typedef UpdatesExternalProducts
 * @alias UpdatesModels.<ExternalProduct>
 */
export declare type UpdatesExternalProducts = UpdatesModels<ExternalProductRepositoryParams>;
/**
 * An interface for deleting external products using the repository.
 *
 * @typedef DeletesExternalProducts
 * @alias DeletesModels.<ExternalProduct>
 */
export declare type DeletesExternalProducts = DeletesModels<ExternalProductRepositoryParams>;
/**
 * The base for the external product object.
 */
export declare class ExternalProduct extends AbstractProduct implements IProductCommon, IProductExternal, IProductPrice, IProductSalesTax, IProductUpSells {
    /**
     * @see ./abstracts/external.ts
     */
    readonly buttonText: string;
    readonly externalUrl: string;
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
     * @see ./abstracts/upsell.ts
     */
    readonly upSellIds: Array<number>;
    /**
     * @see ./abstracts/sales-tax.ts
     */
    readonly taxStatus: Taxability;
    readonly taxClass: string;
    /**
     * Creates a new simple product instance with the given properties
     *
     * @param {Object} properties The properties to set in the object.
     */
    constructor(properties?: Partial<ExternalProduct>);
    /**
     * Creates a model repository configured for communicating via the REST API.
     *
     * @param {HTTPClient} httpClient The client for communicating via HTTP.
     */
    static restRepository(httpClient: HTTPClient): ReturnType<typeof externalProductRESTRepository>;
}
export {};
//# sourceMappingURL=external-product.d.ts.map