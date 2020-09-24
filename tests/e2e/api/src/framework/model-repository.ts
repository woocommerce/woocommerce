import { Model } from '../models/model';

/**
 * A callback for creating a model using a data source.
 *
 * @callback CreateFn
 * @param {*} properties The properties of the model to create.
 * @return {Promise} Resolves to the created model.
 */
export type CreateFn< T > = ( properties: Partial< T > ) => Promise< T >;

/**
 * A callback for reading a model using a data source.
 *
 * @callback ReadFn
 * @param {number|Object} id The ID or object used to find the model.
 * @return {Promise} Resolves to the read model.
 */
export type ReadFn< IDParam, T > = ( id: IDParam ) => Promise< T >;

/**
 * A callback for updating a model using a data source.
 *
 * @callback UpdateFn
 * @param {number|Object} id The ID or object used to find the model.
 * @return {Promise} Resolves to the updated model.
 */
export type UpdateFn< IDParam, T > = ( id: IDParam, properties: Partial< T > ) => Promise< T >;

/**
 * A callback for deleting a model from a data source.
 *
 * @callback DeleteFn
 * @param {number|Object} id The ID or object used to find the model.
 * @return {Promise} Resolves to true once the model has been deleted.
 */
export type DeleteFn< IDParam > = ( id: IDParam ) => Promise< boolean >;

/**
 * @typedef CreatesModels
 * @property {CreateFn} create Creates a model using the repository.
 */
export interface CreatesModels< T extends Model > {
	create( properties: Partial< T > ): Promise< T >;
}

/**
 * @typedef ReadsModels
 * @property {ReadFn} read Reads a model using the repository.
 */
export interface ReadsModels< T extends Model, IDParam = number > {
	read( id: IDParam ): Promise< T >;
}

/**
 * @typedef UpdatesModels
 * @property {UpdateFn} update Updates a model using the repository.
 */
export interface UpdatesModels< T extends Model, IDParam = number > {
	update( id: IDParam, properties: Partial< T > ): Promise< T >;
}

/**
 * @typedef DeletesModels
 * @property {DeleteFn} delete Deletes a model using the repository.
 */
export interface DeletesModels< IDParam = number > {
	delete( id: IDParam ): Promise< boolean >;
}

/**
 * A class for performing CRUD operations on models using a number of internal hooks.
 * Note that if a model does not support a given operation then it will throw an
 * error when attempting to perform that action.
 */
export class ModelRepository< T extends Model, IDParam = number > implements
	CreatesModels< T >,
	ReadsModels< T, IDParam >,
	UpdatesModels< T, IDParam >,
	DeletesModels< IDParam > {
	private readonly createHook: CreateFn< T > | null;
	private readonly readHook: ReadFn< IDParam, T > | null;
	private readonly updateHook: UpdateFn< IDParam, T > | null;
	private readonly deleteHook: DeleteFn< IDParam > | null;

	/**
	 * Creates a new repository instance.
	 *
	 * @param {CreateFn|null} createHook The hook for model creation.
	 * @param {ReadFn|null} readHook The hook for model reading.
	 * @param {UpdateFn|null} updateHook The hook for model updating.
	 * @param {DeleteFn|null} deleteHook The hook for model deletion.
	 */
	public constructor(
		createHook: CreateFn< T > | null,
		readHook: ReadFn< IDParam, T > | null,
		updateHook: UpdateFn< IDParam, T > | null,
		deleteHook: DeleteFn< IDParam > | null,
	) {
		this.createHook = createHook;
		this.readHook = readHook;
		this.updateHook = updateHook;
		this.deleteHook = deleteHook;
	}

	/**
	 * Creates the given model.
	 *
	 * @param {*} properties The properties for the model we'd like to create.
	 * @return {Promise} A promise that resolves to the model after creation.
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
	 * @param {number|Object} id The identifier for the model to read.
	 * @return {Promise} A promise that resolves to the model.
	 */
	public read( id: IDParam ): Promise< T > {
		if ( ! this.readHook ) {
			throw new Error( 'The \'read\' operation is not supported on this model.' );
		}

		return this.readHook( id );
	}

	/**
	 * Updates the given model.
	 *
	 * @param {number|Object} id The identifier for the model to create.
	 * @param {*} properties The model properties that we'd like to update.
	 * @return {Promise} A promise that resolves to the model after updating.
	 */
	public update( id: IDParam, properties: Partial< T > ): Promise< T > {
		if ( ! this.updateHook ) {
			throw new Error( 'The \'update\' operation is not supported on this model.' );
		}

		return this.updateHook( id, properties );
	}

	/**
	 * Deletes the given model.
	 *
	 * @param {number|Object} id The identifier for the model to delete.
	 * @return {Promise} A promise that resolves to "true" on success.
	 */
	public delete( id: IDParam ): Promise< boolean > {
		if ( ! this.deleteHook ) {
			throw new Error( 'The \'delete\' operation is not supported on this model.' );
		}

		return this.deleteHook( id );
	}
}
