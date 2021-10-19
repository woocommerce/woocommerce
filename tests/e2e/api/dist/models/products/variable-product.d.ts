import { AbstractProduct, IProductCommon, IProductCrossSells, IProductInventory, IProductSalesTax, IProductShipping, IProductUpSells, ProductSearchParams } from './abstract';
import { ProductInventoryUpdateParams, ProductCommonUpdateParams, ProductDefaultAttribute, ProductSalesTaxUpdateParams, ProductCrossUpdateParams, ProductShippingUpdateParams, ProductUpSellUpdateParams, ProductVariableUpdateParams, StockStatus, BackorderStatus, Taxability } from './shared';
import { HTTPClient } from '../../http';
import { variableProductRESTRepository } from '../../repositories';
import { CreatesModels, DeletesModels, ListsModels, ModelRepositoryParams, ReadsModels, UpdatesModels } from '../../framework';
/**
 * The parameters that variable products can update.
 */
declare type VariableProductUpdateParams = ProductVariableUpdateParams & ProductCommonUpdateParams & ProductCrossUpdateParams & ProductInventoryUpdateParams & ProductSalesTaxUpdateParams & ProductShippingUpdateParams & ProductUpSellUpdateParams;
/**
 * The parameters embedded in this generic can be used in the ModelRepository in order to give
 * type-safety in an incredibly granular way.
 */
export declare type VariableProductRepositoryParams = ModelRepositoryParams<VariableProduct, never, ProductSearchParams, VariableProductUpdateParams>;
/**
 * An interface for listing variable products using the repository.
 *
 * @typedef ListsVariableProducts
 * @alias ListsModels.<VariableProduct>
 */
export declare type ListsVariableProducts = ListsModels<VariableProductRepositoryParams>;
/**
 * An interface for creating variable products using the repository.
 *
 * @typedef CreatesVariableProducts
 * @alias CreatesModels.<VariableProduct>
 */
export declare type CreatesVariableProducts = CreatesModels<VariableProductRepositoryParams>;
/**
 * An interface for reading variable products using the repository.
 *
 * @typedef ReadsVariableProducts
 * @alias ReadsModels.<VariableProduct>
 */
export declare type ReadsVariableProducts = ReadsModels<VariableProductRepositoryParams>;
/**
 * An interface for updating variable products using the repository.
 *
 * @typedef UpdatesVariableProducts
 * @alias UpdatesModels.<VariableProduct>
 */
export declare type UpdatesVariableProducts = UpdatesModels<VariableProductRepositoryParams>;
/**
 * An interface for deleting variable products using the repository.
 *
 * @typedef DeletesVariableProducts
 * @alias DeletesModels.<VariableProduct>
 */
export declare type DeletesVariableProducts = DeletesModels<VariableProductRepositoryParams>;
/**
 * The base for the Variable product object.
 */
export declare class VariableProduct extends AbstractProduct implements IProductCommon, IProductCrossSells, IProductInventory, IProductSalesTax, IProductShipping, IProductUpSells {
    /**
     * @see ./abstracts/cross-sells.ts
     */
    readonly crossSellIds: Array<number>;
    /**
     * @see ./abstracts/upsell.ts
     */
    readonly upSellIds: Array<number>;
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
     * Default product attributes.
     *
     * @type {ReadonlyArray.<ProductDefaultAttribute>}
     */
    readonly defaultAttributes: readonly ProductDefaultAttribute[];
    /**
     * Product variations.
     *
     * @type {ReadonlyArray.<number>}
     */
    readonly variations: Array<number>;
    /**
     * Creates a new Variable product instance with the given properties
     *
     * @param {Object} properties The properties to set in the object.
     */
    constructor(properties?: Partial<VariableProduct>);
    /**
     * Creates a model repository configured for communicating via the REST API.
     *
     * @param {HTTPClient} httpClient The client for communicating via HTTP.
     */
    static restRepository(httpClient: HTTPClient): ReturnType<typeof variableProductRESTRepository>;
}
export {};
//# sourceMappingURL=variable-product.d.ts.map