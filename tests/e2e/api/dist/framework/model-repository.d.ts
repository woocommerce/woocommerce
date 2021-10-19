import { Model, ModelID } from '../models';
/**
 * An interface for describing the shape of parent identifiers for repositories.
 *
 * @typedef ModelParentID
 * @alias Object.<string,ModelID>
 */
export interface ModelParentID {
    [key: number]: ModelID;
}
/**
 * This type describes the structure of different kinds of data that is extracted
 * for use in the repository to provide type-safety to repository actions.
 */
export interface ModelRepositoryParams<T extends Model = never, ParentID extends ModelID | ModelParentID = never, ListParams = never, UpdateParams extends keyof T = never> {
    thisTypeIsDeclarativeOnly: string;
}
/**
 * These helpers will extract information about a model from its repository params to be used in the repository.
 */
export declare type ModelClass<T extends ModelRepositoryParams> = [T] extends [ModelRepositoryParams<infer X>] ? X : never;
export declare type ParentID<T extends ModelRepositoryParams> = [T] extends [ModelRepositoryParams<any, infer X>] ? X : never;
export declare type HasParent<T extends ModelRepositoryParams, P, C> = [ParentID<T>] extends [never] ? C : P;
declare type ListParams<T extends ModelRepositoryParams> = [T] extends [ModelRepositoryParams<any, any, infer X>] ? X : never;
declare type PickUpdateParams<T, K extends keyof T> = {
    [P in K]?: T[P];
};
declare type UpdateParams<T extends ModelRepositoryParams> = [T] extends [ModelRepositoryParams<infer C, any, any, infer X>] ? ([X] extends [keyof C] ? PickUpdateParams<C, X> : never) : never;
/**
 * A callback for listing models using a data source.
 *
 * @callback ListFn
 * @param {L} [params] The list parameters for the query.
 * @return {Promise.<Array.<T>>} Resolves to an array of created models.
 * @template {Model} T
 * @template L
 */
export declare type ListFn<T extends ModelRepositoryParams> = (params?: ListParams<T>) => Promise<ModelClass<T>[]>;
/**
 * A callback for listing child models using a data source.
 *
 * @callback ListChildFn
 * @param {P} parent The parent identifier for the model.
 * @param {L} [params] The list parameters for the query.
 * @return {Promise.<Array.<T>>} Resolves to an array of created models.
 * @template {Model} T
 * @template {ModelParentID} P
 * @template L
 */
export declare type ListChildFn<T extends ModelRepositoryParams> = (parent: ParentID<T>, params?: ListParams<T>) => Promise<ModelClass<T>[]>;
/**
 * A callback for creating a model using a data source.
 *
 * @callback CreateFn
 * @param {Partial.<T>} properties The properties of the model to create.
 * @return {Promise.<T>} Resolves to the created model.
 * @template {Model} T
 */
export declare type CreateFn<T extends ModelRepositoryParams> = (properties: Partial<ModelClass<T>>) => Promise<ModelClass<T>>;
/**
 * A callback for creating a child model using a data source.
 *
 * @callback CreateChildFn
 * @param {ModelID} parent The parent identifier for the model.
 * @param {Partial.<T>} properties The properties of the model to create.
 * @return {Promise.<T>} Resolves to the created model.
 * @template {Model} T
 */
export declare type CreateChildFn<T extends ModelRepositoryParams> = (parent: ParentID<T>, properties: Partial<ModelClass<T>>) => Promise<ModelClass<T>>;
/**
 * A callback for reading a model using a data source.
 *
 * @callback ReadFn
 * @param {ModelID} id The ID of the model.
 * @return {Promise.<T>} Resolves to the read model.
 * @template {Model} T
 */
export declare type ReadFn<T extends ModelRepositoryParams> = (id: ModelID) => Promise<ModelClass<T>>;
/**
 * A callback for reading a child model using a data source.
 *
 * @callback ReadChildFn
 * @param {P} parent The parent identifier for the model.
 * @param {ModelID} childID The ID of the model.
 * @return {Promise.<T>} Resolves to the read model.
 * @template {Model} T
 * @template {ModelParentID} P
 */
export declare type ReadChildFn<T extends ModelRepositoryParams> = (parent: ParentID<T>, childID: ModelID) => Promise<ModelClass<T>>;
/**
 * A callback for updating a model using a data source.
 *
 * @callback UpdateFn
 * @param {ModelID} id The ID of the model.
 * @param {Partial.<T>} properties The properties to update.
 * @return {Promise.<T>} Resolves to the updated model.
 * @template {Model} T
 */
export declare type UpdateFn<T extends ModelRepositoryParams> = (id: ModelID, properties: UpdateParams<T>) => Promise<ModelClass<T>>;
/**
 * A callback for updating a child model using a data source.
 *
 * @callback UpdateChildFn
 * @param {P} parent The parent identifier for the model.
 * @param {ModelID} childID The ID of the model.
 * @param {Partial.<T>} properties The properties to update.
 * @return {Promise.<T>} Resolves to the updated model.
 * @template {Model} T
 * @template {ModelParentID} P
 */
export declare type UpdateChildFn<T extends ModelRepositoryParams> = (parent: ParentID<T>, childID: ModelID, properties: UpdateParams<T>) => Promise<ModelClass<T>>;
/**
 * A callback for deleting a model from a data source.
 *
 * @callback DeleteFn
 * @param {ModelID} id The ID of the model.
 * @return {Promise.<boolean>} Resolves to true once the model has been deleted.
 */
export declare type DeleteFn = (id: ModelID) => Promise<boolean>;
/**
 * A callback for deleting a child model from a data source.
 *
 * @callback DeleteChildFn
 * @param {P} parent The parent identifier for the model.
 * @param {ModelID} childID The ID of the model.
 * @return {Promise.<boolean>} Resolves to true once the model has been deleted.
 * @template {ModelParentID} P
 */
export declare type DeleteChildFn<T extends ModelRepositoryParams> = (parent: ParentID<T>, childID: ModelID) => Promise<boolean>;
/**
 * An interface for repositories that can list models.
 *
 * @typedef ListsModels
 * @property {ListFn.<T,L>} list Lists models using the repository.
 * @template {Model} T
 * @template L
 */
export interface ListsModels<T extends ModelRepositoryParams> {
    list(params?: HasParent<T, never, ListParams<T>>): Promise<ModelClass<T>[]>;
}
/**
 * An interface for repositories that can list child models.
 *
 * @typedef ListsChildModels
 * @property {ListChildFn.<T,P,L>} list Lists models using the repository.
 * @template {Model} T
 * @template {ModelParentID} P
 * @template L
 */
export interface ListsChildModels<T extends ModelRepositoryParams> {
    list(parent: HasParent<T, ParentID<T>, never>, params?: HasParent<T, ListParams<T>, never>): Promise<ModelClass<T>[]>;
}
/**
 * An interface for repositories that can create models.
 *
 * @typedef CreatesModels
 * @property {CreateFn.<T>} create Creates a model using the repository.
 * @template {Model} T
 */
export interface CreatesModels<T extends ModelRepositoryParams> {
    create(properties: Partial<ModelClass<T>>): Promise<ModelClass<T>>;
}
/**
 * An interface for repositories that can create child models.
 *
 * @typedef CreatesChildModels
 * @property {CreateChildFn.<T>} create Creates a child model using the repository.
 * @template {Model} T
 * @template {ModelParentID} P
 */
export interface CreatesChildModels<T extends ModelRepositoryParams> {
    create(parent: HasParent<T, ParentID<T>, never>, properties: HasParent<T, Partial<ModelClass<T>>, never>): Promise<ModelClass<T>>;
}
/**
 * An interface for repositories that can read models.
 *
 * @typedef ReadsModels
 * @property {ReadFn.<T>} read Reads a model using the repository.
 * @template {Model} T
 */
export interface ReadsModels<T extends ModelRepositoryParams> {
    read(id: HasParent<T, never, ModelID>): Promise<ModelClass<T>>;
}
/**
 * An interface for repositories that can read models that are children.
 *
 * @typedef ReadsChildModels
 * @property {ReadChildFn.<T,P>} read Reads a model using the repository.
 * @template {Model} T
 * @template {ModelParentID} P
 */
export interface ReadsChildModels<T extends ModelRepositoryParams> {
    read(parent: HasParent<T, ParentID<T>, never>, childID: HasParent<T, ModelID, never>): Promise<ModelClass<T>>;
}
/**
 * An interface for repositories that can update models.
 *
 * @typedef UpdatesModels
 * @property {UpdateFn.<T>} update Updates a model using the repository.
 * @template {Model} T
 */
export interface UpdatesModels<T extends ModelRepositoryParams> {
    update(id: HasParent<T, never, ModelID>, properties: HasParent<T, never, UpdateParams<T>>): Promise<ModelClass<T>>;
}
/**
 * An interface for repositories that can update models.
 *
 * @typedef UpdatesChildModels
 * @property {UpdateChildFn.<T,P>} update Updates a model using the repository.
 * @template {Model} T
 * @template {ModelParentID} P
 */
export interface UpdatesChildModels<T extends ModelRepositoryParams> {
    update(parent: HasParent<T, ParentID<T>, never>, childID: HasParent<T, ModelID, never>, properties: HasParent<T, UpdateParams<T>, never>): Promise<ModelClass<T>>;
}
/**
 * An interface for repositories that can delete models.
 *
 * @typedef DeletesModels
 * @property {DeleteFn} delete Deletes a model using the repository.
 */
export interface DeletesModels<T extends ModelRepositoryParams> {
    delete(id: HasParent<T, never, ModelID>): Promise<boolean>;
}
/**
 * An interface for repositories that can delete models.
 *
 * @typedef DeletesModels
 * @property {DeleteChildFn.<P>} delete Deletes a model using the repository.
 * @template {ModelParentID} P
 */
export interface DeletesChildModels<T extends ModelRepositoryParams> {
    delete(parent: HasParent<T, ParentID<T>, never>, childID: HasParent<T, ModelID, never>): Promise<boolean>;
}
/**
 * A class for performing CRUD operations on models using a number of internal hooks.
 * Note that if a model does not support a given operation then it will throw an
 * error when attempting to perform that action.
 *
 * @template {Model} T
 * @template {ModelParentID} P
 * @template {Object} L
 */
export declare class ModelRepository<T extends ModelRepositoryParams> implements ListsModels<T>, ListsChildModels<T>, ReadsModels<T>, ReadsChildModels<T>, UpdatesModels<T>, UpdatesChildModels<T>, DeletesModels<T>, DeletesChildModels<T> {
    /**
     * The hook used to list models.
     *
     * @type {ListFn.<T,P,L>|ListChildFn<T,P,L>}
     * @private
     */
    private readonly listHook;
    /**
     * The hook used to create models
     *
     * @type {CreateFn.<T>}
     * @private
     */
    private readonly createHook;
    /**
     * The hook used to read models.
     *
     * @type {ReadFn.<T>|ReadChildFn.<T,P>}
     * @private
     */
    private readonly readHook;
    /**
     * The hook used to update models.
     *
     * @type {UpdateFn.<T>|UpdateChildFn.<T,P>}
     * @private
     */
    private readonly updateHook;
    /**
     * The hook used to delete models.
     *
     * @type {DeleteFn|DeleteChildFn.<P>}
     * @private
     */
    private readonly deleteHook;
    /**
     * Creates a new repository instance.
     *
     * @param {ListFn.<T,L>|ListChildFn<T,P,L>} listHook The hook for model listing.
     * @param {CreateFn.<T>|null} createHook The hook for model creation.
     * @param {ReadFn.<T>|ReadChildFn.<T,P>|null} readHook The hook for model reading.
     * @param {UpdateFn.<T>|UpdateChildFn.<T,P>|null} updateHook The hook for model updating.
     * @param {DeleteFn|DeleteChildFn.<P>|null} deleteHook The hook for model deletion.
     */
    constructor(listHook: HasParent<T, ListChildFn<T>, ListFn<T>> | null, createHook: HasParent<T, CreateChildFn<T>, CreateFn<T>> | null, readHook: HasParent<T, ReadChildFn<T>, ReadFn<T>> | null, updateHook: HasParent<T, UpdateChildFn<T>, UpdateFn<T>> | null, deleteHook: HasParent<T, DeleteChildFn<T>, DeleteFn> | null);
    /**
     * Lists models using the repository.
     *
     * @param {L|P} [paramsOrParent] The params for the lookup or the parent to list if the model is a child.
     * @param {L} [params] The params when using the parent.
     * @return {Promise.<Array.<T>>} Resolves to the listed models.
     */
    list(paramsOrParent?: HasParent<T, ParentID<T>, ListParams<T>>, params?: HasParent<T, ListParams<T>, never>): Promise<ModelClass<T>[]>;
    /**
     * Creates a new model using the repository.
     *
     * @param {P|ModelID} propertiesOrParent The properties to create the model with or the model parent.
     * @param {Partial.<T>} properties The properties to create the model with.
     * @return {Promise.<T>} Resolves to the created model.
     */
    create(propertiesOrParent?: HasParent<T, ParentID<T>, Partial<ModelClass<T>>>, properties?: HasParent<T, Partial<ModelClass<T>>, never>): Promise<ModelClass<T>>;
    /**
     * Reads a model using the repository.
     *
     * @param {ModelID|P} idOrParent The ID of the model or its parent if the model is a child.
     * @param {ModelID} [childID] The ID of the model when using the parent.
     * @return {Promise.<T>} Resolves to the loaded model.
     */
    read(idOrParent: HasParent<T, ParentID<T>, ModelID>, childID?: HasParent<T, ModelID, never>): Promise<ModelClass<T>>;
    /**
     * Updates the model's properties using the repository.
     *
     * @param {ModelID|P} idOrParent The ID of the model or its parent if the model is a child.
     * @param {Partial.<T>|ModelID} propertiesOrChildID The properties for the model or the ID when using the parent.
     * @param {Partial.<T>} [properties] The properties for child models.
     * @return {Promise.<T>} Resolves to the updated model.
     */
    update(idOrParent: HasParent<T, ParentID<T>, ModelID>, propertiesOrChildID: HasParent<T, ModelID, UpdateParams<T>>, properties?: HasParent<T, UpdateParams<T>, never>): Promise<ModelClass<T>>;
    /**
     * Deletes a model using the repository.
     *
     * @param {ModelID|P} idOrParent The ID of the model or its parent if the model is a child.
     * @param {ModelID} [childID] The ID of the model when using the parent.
     * @return {Promise.<T>} Resolves to the loaded model.
     */
    delete(idOrParent: HasParent<T, ParentID<T>, ModelID>, childID?: HasParent<T, ModelID, never>): Promise<boolean>;
}
export {};
//# sourceMappingURL=model-repository.d.ts.map