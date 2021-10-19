import { AbstractProduct, IProductCommon, IProductGrouped, IProductUpSells, ProductSearchParams } from './abstract';
import { ProductCommonUpdateParams, ProductGroupedUpdateParams, ProductUpSellUpdateParams } from './shared';
import { HTTPClient } from '../../http';
import { groupedProductRESTRepository } from '../../repositories';
import { CreatesModels, DeletesModels, ListsModels, ModelRepositoryParams, ReadsModels, UpdatesModels } from '../../framework';
/**
 * The parameters that Grouped products can update.
 */
declare type GroupedProductUpdateParams = ProductCommonUpdateParams & ProductGroupedUpdateParams & ProductUpSellUpdateParams;
/**
 * The parameters embedded in this generic can be used in the ModelRepository in order to give
 * type-safety in an incredibly granular way.
 */
export declare type GroupedProductRepositoryParams = ModelRepositoryParams<GroupedProduct, never, ProductSearchParams, GroupedProductUpdateParams>;
/**
 * An interface for listing Grouped products using the repository.
 *
 * @typedef ListsGroupedProducts
 * @alias ListsModels.<GroupedProduct>
 */
export declare type ListsGroupedProducts = ListsModels<GroupedProductRepositoryParams>;
/**
 * An interface for creating Grouped products using the repository.
 *
 * @typedef CreatesGroupedProducts
 * @alias CreatesModels.<GroupedProduct>
 */
export declare type CreatesGroupedProducts = CreatesModels<GroupedProductRepositoryParams>;
/**
 * An interface for reading Grouped products using the repository.
 *
 * @typedef ReadsGroupedProducts
 * @alias ReadsModels.<GroupedProduct>
 */
export declare type ReadsGroupedProducts = ReadsModels<GroupedProductRepositoryParams>;
/**
 * An interface for updating Grouped products using the repository.
 *
 * @typedef UpdatesGroupedProducts
 * @alias UpdatesModels.<GroupedProduct>
 */
export declare type UpdatesGroupedProducts = UpdatesModels<GroupedProductRepositoryParams>;
/**
 * An interface for deleting Grouped products using the repository.
 *
 * @typedef DeletesGroupedProducts
 * @alias DeletesModels.<GroupedProduct>
 */
export declare type DeletesGroupedProducts = DeletesModels<GroupedProductRepositoryParams>;
/**
 * The base for the Grouped product object.
 */
export declare class GroupedProduct extends AbstractProduct implements IProductCommon, IProductGrouped, IProductUpSells {
    /**
     * @see ./abstracts/grouped.ts
     */
    readonly groupedProducts: Array<number>;
    /**
     * @see ./abstracts/upsell.ts
     */
    readonly upSellIds: Array<number>;
    /**
     * Creates a new Grouped product instance with the given properties
     *
     * @param {Object} properties The properties to set in the object.
     */
    constructor(properties?: Partial<GroupedProduct>);
    /**
     * Creates a model repository configured for communicating via the REST API.
     *
     * @param {HTTPClient} httpClient The client for communicating via HTTP.
     */
    static restRepository(httpClient: HTTPClient): ReturnType<typeof groupedProductRESTRepository>;
}
export {};
//# sourceMappingURL=grouped-product.d.ts.map