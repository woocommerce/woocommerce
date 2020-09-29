import { Model, ModelID } from '../models/model';

/**
 * A callback for creating a model using a data source.
 *
 * @callback CreateFn
 * @template {Model} T
 * @param {Partial.<T>} properties The properties of the model to create.
 * @return {Promise.<T>} Resolves to the created model.
 */
export type CreateFn< T extends Model > = ( properties: Partial< T > ) => Promise< T >;

/**
 * A callback for reading a model using a data source.
 *
 * @callback ReadFn
 * @template {Model} T
 * @param {ModelID} id The ID of the model.
 * @return {Promise.<T>} Resolves to the read model.
 */
export type ReadFn< T extends Model > = ( id: ModelID ) => Promise< T >;

/**
 * A callback for updating a model using a data source.
 *
 * @callback UpdateFn
 * @template {Model} T
 * @param {ModelID} id The ID of the model.
 * @param {Partial.<T>} properties The properties of the model.
 * @return {Promise.<T>} Resolves to the updated model.
 */
export type UpdateFn< T > = ( id: ModelID, properties: Partial< T > ) => Promise< T >;

/**
 * A callback for deleting a model from a data source.
 *
 * @callback DeleteFn
 * @param {ModelID} id The ID of the model.
 * @return {Promise.<boolean>} Resolves to true once the model has been deleted.
 */
export type DeleteFn = ( id: ModelID ) => Promise< boolean >;

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
 * An interface for repositories that can delete models.
 *
 * @typedef DeletesModels
 * @property {DeleteFn} delete Deletes a model using the repository.
 */
export interface DeletesModels {
	delete( id: ModelID ): Promise< boolean >;
}

/**
 * A class for performing CRUD operations on models using a number of internal hooks.
 * Note that if a model does not support a given operation then it will throw an
 * error when attempting to perform that action.
 *
 * @template {Model} T
 */
export class ModelRepository< T extends Model > implements
	CreatesModels< T >,
	ReadsModels< T >,
	UpdatesModels< T >,
	DeletesModels {
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
	private readonly readHook: ReadFn< T > | null;

	/**
	 * The hook used to update models.
	 *
	 * @type {UpdateFn.<T>}
	 * @private
	 */
	private readonly updateHook: UpdateFn< T > | null;

	/**
	 * The hook used to delete models.
	 *
	 * @type {DeleteFn}
	 * @private
	 */
	private readonly deleteHook: DeleteFn | null;

	/**
	 * Creates a new repository instance.
	 *
	 * @param {CreateFn.<T>|null} createHook The hook for model creation.
	 * @param {ReadFn.<T>|null} readHook The hook for model reading.
	 * @param {UpdateFn.<T>|null} updateHook The hook for model updating.
	 * @param {DeleteFn.<T>|null} deleteHook The hook for model deletion.
	 */
	public constructor(
		createHook: CreateFn< T > | null,
		readHook: ReadFn< T > | null,
		updateHook: UpdateFn< T > | null,
		deleteHook: DeleteFn | null,
	) {
		this.createHook = createHook;
		this.readHook = readHook;
		this.updateHook = updateHook;
		this.deleteHook = deleteHook;
	}

	/**
	 * Creates the given model.
	 *
	 * @param {Partial.<T>} properties The properties for the model we'd like to create.
	 * @return {Promise.<T>} A promise that resolves to the model after creation.
	 */
	public create( properties: Partial< T > ): Promise< T > {
		if ( ! this.createHook ) {
			throw new Error( 'The \'create\' operation is not supported on this model.' );
		}

		return this.createHook( properties );
	}

	/**
	 * Reads the given model.
	 *
	 * @param {string|number} id The identifier for the model to read.
	 * @return {Promise.<T>} A promise that resolves to the model.
	 */
	public read( id: ModelID ): Promise< T > {
		if ( ! this.readHook ) {
			throw new Error( 'The \'read\' operation is not supported on this model.' );
		}

		return this.readHook( id );
	}

	/**
	 * Updates the given model.
	 *
	 * @param {string|number} id The identifier for the model to create.
	 * @param {Partial.<T>} properties The model properties that we'd like to update.
	 * @return {Promise.<T>} A promise that resolves to the model after updating.
	 */
	public update( id: ModelID, properties: Partial< T > ): Promise< T > {
		if ( ! this.updateHook ) {
			throw new Error( 'The \'update\' operation is not supported on this model.' );
		}

		return this.updateHook( id, properties );
	}

	/**
	 * Deletes the given model.
	 *
	 * @param {string|number} id The identifier for the model to delete.
	 * @return {Promise.<boolean>} A promise that resolves to "true" on success.
	 */
	public delete( id: ModelID ): Promise< boolean > {
		if ( ! this.deleteHook ) {
			throw new Error( 'The \'delete\' operation is not supported on this model.' );
		}

		return this.deleteHook( id );
	}
}
