import { HTTPClient } from '../../http';
import { ListFn, ModelRepositoryParams, ModelClass, HasParent, ParentID, ListChildFn, ReadChildFn, ReadFn, DeleteFn, UpdateFn, UpdateChildFn, DeleteChildFn, CreateFn, CreateChildFn, ModelTransformer } from '../../framework';
import { ModelID, MetaData, ModelConstructor } from '../../models';
/**
 * Creates a new transformer for metadata models.
 *
 * @return {ModelTransformer} The created transformer.
 */
export declare function createMetaDataTransformer(): ModelTransformer<MetaData>;
/**
 * A callback to build a URL for a request.
 *
 * @callback BuildURLFn
 * @param {ModelID} [id] The ID of the model we're dealing with if used for the request.
 * @return {string} The URL to make the request to.
 */
declare type BuildURLFn<T extends ('list' | 'general') = 'general'> = [T] extends ['list'] ? () => string : (id: ModelID) => string;
/**
 * A callback to build a URL for a request.
 *
 * @callback BuildURLWithParentFn
 * @param {P} parent The ID of the model's parent.
 * @param {ModelID} [id] The ID of the model we're dealing with if used for the request.
 * @return {string} The URL to make the request to.
 * @template {ModelParentID} P
 */
declare type BuildURLWithParentFn<P extends ModelRepositoryParams, T extends ('list' | 'general') = 'general'> = [T] extends ['list'] ? (parent: ParentID<P>) => string : (parent: ParentID<P>, id: ModelID) => string;
/**
 * Creates a callback for listing models using the REST API.
 *
 * @param {BuildURLFn} buildURL A callback to build the URL for the request.
 * @param {Function} modelClass The model we're listing.
 * @param {HTTPClient} httpClient The HTTP client to use for the request.
 * @param {ModelTransformer} transformer The transformer to use for the response data.
 * @return {ListFn} The callback for the repository.
 */
export declare function restList<T extends ModelRepositoryParams>(buildURL: HasParent<T, never, BuildURLFn<'list'>>, modelClass: ModelConstructor<ModelClass<T>>, httpClient: HTTPClient, transformer: ModelTransformer<ModelClass<T>>): ListFn<T>;
/**
 * Creates a callback for listing child models using the REST API.
 *
 * @param {BuildURLWithParentFn} buildURL A callback to build the URL for the request.
 * @param {Function} modelClass The model we're listing.
 * @param {HTTPClient} httpClient The HTTP client to use for the request.
 * @param {ModelTransformer} transformer The transformer to use for the response data.
 * @return {ListChildFn} The callback for the repository.
 */
export declare function restListChild<T extends ModelRepositoryParams>(buildURL: HasParent<T, BuildURLWithParentFn<T, 'list'>, never>, modelClass: ModelConstructor<ModelClass<T>>, httpClient: HTTPClient, transformer: ModelTransformer<ModelClass<T>>): ListChildFn<T>;
/**
 * Creates a callback for creating models using the REST API.
 *
 * @param {Function} buildURL A callback to build the URL. (This is passed the properties for the new model.)
 * @param {Function} modelClass The model we're listing.
 * @param {HTTPClient} httpClient The HTTP client to use for the request.
 * @param {ModelTransformer} transformer The transformer to use for the response data.
 * @return {CreateFn} The callback for the repository.
 */
export declare function restCreate<T extends ModelRepositoryParams>(buildURL: (properties: Partial<ModelClass<T>>) => string, modelClass: ModelConstructor<ModelClass<T>>, httpClient: HTTPClient, transformer: ModelTransformer<ModelClass<T>>): CreateFn<T>;
/**
 * Creates a callback for creating child models using the REST API.
 *
 * @param {Function} buildURL A callback to build the URL. (This is passed the properties for the new model.)
 * @param {Function} modelClass The model we're listing.
 * @param {HTTPClient} httpClient The HTTP client to use for the request.
 * @param {ModelTransformer} transformer The transformer to use for the response data.
 * @return {CreateChildFn} The callback for the repository.
 */
export declare function restCreateChild<T extends ModelRepositoryParams>(buildURL: (parent: ParentID<T>, properties: Partial<ModelClass<T>>) => string, modelClass: ModelConstructor<ModelClass<T>>, httpClient: HTTPClient, transformer: ModelTransformer<ModelClass<T>>): CreateChildFn<T>;
/**
 * Creates a callback for reading models using the REST API.
 *
 * @param {BuildURLFn} buildURL A callback to build the URL for the request.
 * @param {Function} modelClass The model we're listing.
 * @param {HTTPClient} httpClient The HTTP client to use for the request.
 * @param {ModelTransformer} transformer The transformer to use for the response data.
 * @return {ReadFn} The callback for the repository.
 */
export declare function restRead<T extends ModelRepositoryParams>(buildURL: HasParent<T, never, BuildURLFn>, modelClass: ModelConstructor<ModelClass<T>>, httpClient: HTTPClient, transformer: ModelTransformer<ModelClass<T>>): ReadFn<T>;
/**
 * Creates a callback for reading child models using the REST API.
 *
 * @param {BuildURLWithParentFn} buildURL A callback to build the URL for the request.
 * @param {Function} modelClass The model we're listing.
 * @param {HTTPClient} httpClient The HTTP client to use for the request.
 * @param {ModelTransformer} transformer The transformer to use for the response data.
 * @return {ReadChildFn} The callback for the repository.
 */
export declare function restReadChild<T extends ModelRepositoryParams>(buildURL: HasParent<T, BuildURLWithParentFn<T>, never>, modelClass: ModelConstructor<ModelClass<T>>, httpClient: HTTPClient, transformer: ModelTransformer<ModelClass<T>>): ReadChildFn<T>;
/**
 * Creates a callback for updating models using the REST API.
 *
 * @param {BuildURLFn} buildURL A callback to build the URL for the request.
 * @param {Function} modelClass The model we're listing.
 * @param {HTTPClient} httpClient The HTTP client to use for the request.
 * @param {ModelTransformer} transformer The transformer to use for the response data.
 * @return {UpdateFn} The callback for the repository.
 */
export declare function restUpdate<T extends ModelRepositoryParams>(buildURL: HasParent<T, never, BuildURLFn>, modelClass: ModelConstructor<ModelClass<T>>, httpClient: HTTPClient, transformer: ModelTransformer<ModelClass<T>>): UpdateFn<T>;
/**
 * Creates a callback for updating child models using the REST API.
 *
 * @param {BuildURLWithParentFn} buildURL A callback to build the URL for the request.
 * @param {Function} modelClass The model we're listing.
 * @param {HTTPClient} httpClient The HTTP client to use for the request.
 * @param {ModelTransformer} transformer The transformer to use for the response data.
 * @return {UpdateChildFn} The callback for the repository.
 */
export declare function restUpdateChild<T extends ModelRepositoryParams>(buildURL: HasParent<T, BuildURLWithParentFn<T>, never>, modelClass: ModelConstructor<ModelClass<T>>, httpClient: HTTPClient, transformer: ModelTransformer<ModelClass<T>>): UpdateChildFn<T>;
/**
 * Creates a callback for deleting models using the REST API.
 *
 * @param {BuildURLFn} buildURL A callback to build the URL for the request.
 * @param {HTTPClient} httpClient The HTTP client to use for the request.
 * @return {DeleteFn} The callback for the repository.
 */
export declare function restDelete<T extends ModelRepositoryParams>(buildURL: HasParent<T, never, BuildURLFn>, httpClient: HTTPClient): DeleteFn;
/**
 * Creates a callback for deleting child models using the REST API.
 *
 * @param {BuildURLWithParentFn} buildURL A callback to build the URL for the request.
 * @param {HTTPClient} httpClient The HTTP client to use for the request.
 * @return {DeleteChildFn} The callback for the repository.
 */
export declare function restDeleteChild<T extends ModelRepositoryParams>(buildURL: HasParent<T, BuildURLWithParentFn<T>, never>, httpClient: HTTPClient): DeleteChildFn<T>;
export {};
//# sourceMappingURL=shared.d.ts.map