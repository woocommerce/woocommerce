import { Model, ModelID, ModelParentID } from '../models/model';

/**
 * A callback for creating a model using a data source.
 *
 * @callback CreateFn
 * @param {Partial.<T>} properties The properties of the model to create.
 * @return {Promise.<T>} Resolves to the created model.
 * @template {Model} T
 */
export type CreateFn< T extends Model > = ( properties: Partial< T > ) => Promise< T >;

/**
 * A callback for reading a model using a data source.
 *
 * @callback ReadFn
 * @param {ModelID} id The ID of the model.
 * @return {Promise.<T>} Resolves to the read model.
 * @template {Model} T
 */
export type ReadFn< T extends Model > = ( id: ModelID ) => Promise< T >;

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
export type ReadChildFn< T extends Model, P extends ModelParentID > = ( parent: P, childID: ModelID ) => Promise< T >;

/**
 * A callback for updating a model using a data source.
 *
 * @callback UpdateFn
 * @param {ModelID} id The ID of the model.
 * @param {Partial.<T>} properties The properties to update.
 * @return {Promise.<T>} Resolves to the updated model.
 * @template {Model} T
 */
export type UpdateFn< T extends Model > = ( id: ModelID, properties: Partial< T > ) => Promise< T >;

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
export type UpdateChildFn< T extends Model, P extends ModelParentID > = (
	parent: P,
	childID: ModelID,
	properties: Partial< T >,
) => Promise< T >;

/**
 * A callback for deleting a model from a data source.
 *
 * @callback DeleteFn
 * @param {ModelID} id The ID of the model.
 * @return {Promise.<boolean>} Resolves to true once the model has been deleted.
 */
export type DeleteFn = ( id: ModelID ) => Promise< boolean >;

/**
 * A callback for deleting a child model from a data source.
 *
 * @callback DeleteChildFn
 * @param {P} parent The parent identifier for the model.
 * @param {ModelID} childID The ID of the model.
 * @return {Promise.<boolean>} Resolves to true once the model has been deleted.
 * @template {ModelParentID} P
 */
export type DeleteChildFn< P extends ModelParentID > = ( parent: P, childID: ModelID ) => Promise< boolean >;

/**
 * An interface for repositories that can create models.
 *
 * @typedef CreatesModels
 * @property {CreateFn.<T>} create Creates a model using the repository.
 * @template {Model} T
 */
export interface CreatesModels< T extends Model > {
	create( properties: Partial< T > ): Promise< T >;
}

/**
 * An interface for repositories that can read models.
 *
 * @typedef ReadsModels
 * @property {ReadFn.<T>} read Reads a model using the repository.
 * @template {Model} T
 */
export interface ReadsModels< T extends Model > {
	read( id: ModelID ): Promise< T >;
}

/**
 * An interface for repositories that can read models that are children.
 *
 * @typedef ReadsChildModels
 * @property {ReadChildFn.<T,P>} read Reads a model using the repository.
 * @template {Model} T
 * @template {ModelParentID} P
 */
export interface ReadsChildModels< T extends Model, P extends ModelParentID > {
	read( parent: P, childID: ModelID ): Promise< T >;
}

/**
 * An interface for repositories that can update models.
 *
 * @typedef UpdatesModels
 * @property {UpdateFn.<T>} update Updates a model using the repository.
 * @template {Model} T
 */
export interface UpdatesModels< T extends Model > {
	update( id: ModelID, properties: Partial< T > ): Promise< T >;
}

/**
 * An interface for repositories that can update models.
 *
 * @typedef UpdatesChildModels
 * @property {UpdateChildFn.<T,P>} update Updates a model using the repository.
 * @template {Model} T
 * @template {ModelParentID} P
 */
export interface UpdatesChildModels< T extends Model, P extends ModelParentID > {
	update( parent: P, childID: ModelID, properties: Partial< T > ): Promise< T >;
}

/**
 * An interface for repositories that can delete models.
 *
 * @typedef DeletesModels
 * @property {DeleteFn} delete Deletes a model using the repository.
 */
export interface DeletesModels {
	delete( id: ModelID ): Promise< boolean >;
}

/**
 * An interface for repositories that can delete models.
 *
 * @typedef DeletesModels
 * @property {DeleteChildFn.<P>} delete Deletes a model using the repository.
 * @template {ModelParentID} P
 */
export interface DeletesChildModels< P extends ModelParentID > {
	delete( parent: P, childID: ModelID ): Promise< boolean >;
}

/**
 * A class for performing CRUD operations on models using a number of internal hooks.
 * Note that if a model does not support a given operation then it will throw an
 * error when attempting to perform that action.
 *
 * @template {Model} T
 * @template {ModelParentID|void} P
 */
export class ModelRepository< T extends Model, P extends ModelParentID = never > implements
	ReadsModels< T >,
	ReadsChildModels< T, [ P ] extends [ ModelParentID ] ? P : never >,
	UpdatesModels< T >,
	UpdatesChildModels< T, [ P ] extends [ ModelParentID ] ? P : never >,
	DeletesModels,
	DeletesChildModels< [ P ] extends [ ModelParentID ] ? P : never > {
	/**
	 * The hook used to create models
	 *
	 * @type {CreateFn.<T>}
	 * @private
	 */
	private readonly createHook: CreateFn< T > | null;

	/**
	 * The hook used to read models.
	 *
	 * @type {ReadFn.<T>}
	 * @private
	 */
	private readonly readHook: ( [ P ] extends [ ModelParentID ] ? ReadChildFn< T, P > : ReadFn< T > ) | null;

	/**
	 * The hook used to update models.
	 *
	 * @type {UpdateFn.<T>}
	 * @private
	 */
	private readonly updateHook: ( [ P ] extends [ ModelParentID ] ? UpdateChildFn< T, P > : UpdateFn< T > ) | null;

	/**
	 * The hook used to delete models.
	 *
	 * @type {DeleteFn}
	 * @private
	 */
	private readonly deleteHook: ( [ P ] extends [ ModelParentID ] ? DeleteChildFn< P > : DeleteFn ) | null;

	/**
	 * Creates a new repository instance.
	 *
	 * @param {CreateFn.<T>|null} createHook The hook for model creation.
	 * @param {ReadFn.<T>|ReadChildFn.<T,P>|null} readHook The hook for model reading.
	 * @param {UpdateFn.<T>|UpdateChildFn.<T,P>|null} updateHook The hook for model updating.
	 * @param {DeleteFn|DeleteChildFn.<P>|null} deleteHook The hook for model deletion.
	 */
	public constructor(
		createHook: CreateFn< T > | null,
		readHook: ( [ P ] extends [ ModelParentID ] ? ReadChildFn< T, P > : ReadFn< T > ) | null,
		updateHook: ( [ P ] extends [ ModelParentID ] ? UpdateChildFn< T, P > : UpdateFn< T > ) | null,
		deleteHook: ( [ P ] extends [ ModelParentID ] ? DeleteChildFn< P > : DeleteFn ) | null,
	) {
		this.createHook = createHook;
		this.readHook = readHook;
		this.updateHook = updateHook;
		this.deleteHook = deleteHook;
	}

	/**
	 * Creates a new model using the repository.
	 *
	 * @param {Partial.<T>} properties The properties to create the model with.
	 * @return {Promise.<T>} Resolves to the created model.
	 */
	public create( properties: Partial< T > ): Promise< T > {
		if ( ! this.createHook ) {
			throw new Error( 'The \'create\' operation is not supported on this model.' );
		}

		return this.createHook( properties );
	}

	/**
	 * Reads a model using the repository.
	 *
	 * @param {ModelID|P} idOrParent The ID of the model or its parent if the model is a child.
	 * @param {ModelID} [childID] The ID of the model when using the parent.
	 * @return {Promise.<T>} Resolves to the loaded model.
	 */
	public read(
		idOrParent: ModelID | P,
		childID?: ModelID,
	): Promise< T > {
		if ( ! this.readHook ) {
			throw new Error( 'The \'read\' operation is not supported on this model.' );
		}

		if ( childID === undefined ) {
			return ( this.readHook as ReadFn< T > )(
				idOrParent as ModelID,
			);
		}

		return ( this.readHook as ReadChildFn< T, P > )(
			idOrParent as any,
			childID,
		);
	}

	/**
	 * Updates the model's properties using the repository.
	 *
	 * @param {ModelID|P} idOrParent The ID of the model or its parent if the model is a child.
	 * @param {Partial.<T>|ModelID} propertiesOrChildID The properties for the model or the ID when using the parent.
	 * @param {Partial.<T>} [properties] The properties for child models.
	 * @return {Promise.<T>} Resolves to the updated model.
	 */
	public update(
		idOrParent: ModelID | P,
		propertiesOrChildID: Partial< T > | ModelID,
		properties?: Partial< T >,
	): Promise< T > {
		if ( ! this.updateHook ) {
			throw new Error( 'The \'update\' operation is not supported on this model.' );
		}

		if ( properties === undefined ) {
			return ( this.updateHook as UpdateFn< T > )(
				idOrParent as ModelID,
				propertiesOrChildID as Partial< T >,
			);
		}

		return ( this.updateHook as UpdateChildFn< T, P > )(
			idOrParent as any,
			propertiesOrChildID as ModelID,
			properties,
		);
	}

	/**
	 * Deletes a model using the repository.
	 *
	 * @param {ModelID|P} idOrParent The ID of the model or its parent if the model is a child.
	 * @param {ModelID} [childID] The ID of the model when using the parent.
	 * @return {Promise.<T>} Resolves to the loaded model.
	 */
	public delete( idOrParent: ModelID | P, childID?: ModelID ): Promise< boolean > {
		if ( ! this.deleteHook ) {
			throw new Error( 'The \'delete\' operation is not supported on this model.' );
		}

		if ( childID === undefined ) {
			return ( this.deleteHook as DeleteFn )(
				idOrParent as ModelID,
			);
		}

		return ( this.deleteHook as DeleteChildFn< P > )(
			idOrParent as any,
			childID,
		);
	}
}
