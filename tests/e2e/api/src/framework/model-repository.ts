import { Model } from '../models/model';

export type CreateFn< T > = ( properties: Partial< T > ) => Promise< T >;
export type ReadFn< IDParam, T > = ( id: IDParam ) => Promise< T >;
export type UpdateFn< IDParam, T > = ( id: IDParam, properties: Partial< T > ) => Promise< T >;
export type DeleteFn< IDParam > = ( id: IDParam ) => Promise< boolean >;

/**
 * An interface for repositories that perform CRUD actions.
 */
export class ModelRepository< T extends Model, IDParam = number > {
	private readonly createHook: CreateFn< T > | null;
	private readonly readHook: ReadFn< IDParam, T > | null;
	private readonly updateHook: UpdateFn< IDParam, T > | null;
	private readonly deleteHook: DeleteFn< IDParam > | null;

	/**
	 * Creates a new repository instance.
	 *
	 * @param {Function|null} createHook The hook for model creation.
	 * @param {Function|null} readHook The hook for model reading.
	 * @param {Function|null} updateHook The hook for model updating.
	 * @param {Function|null} deleteHook The hook for model deletion.
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
			throw new Error( 'The \'create\' hook is not defined.' );
		}

		return this.createHook( properties );
	}

	/**
	 * Reads the given model.
	 *
	 * @param {Object} id The identifier for the model to read.
	 * @return {Promise} A promise that resolves to the model.
	 */
	public read( id: IDParam ): Promise< T > {
		if ( ! this.readHook ) {
			throw new Error( 'The \'read\' hook is not defined.' );
		}

		return this.readHook( id );
	}

	/**
	 * Updates the given model.
	 *
	 * @param {*} id The identifier for the model to create.
	 * @param {*} properties The model properties that we'd like to update.
	 * @return {Promise} A promise that resolves to the model after updating.
	 */
	public update( id: IDParam, properties: Partial< T > ): Promise< T > {
		if ( ! this.updateHook ) {
			throw new Error( 'The \'update\' hook is not defined.' );
		}

		return this.updateHook( id, properties );
	}

	/**
	 * Deletes the given model.
	 *
	 * @param {*} id The identifier for the model to delete.
	 * @return {Promise} A promise that resolves to "true" on success.
	 */
	public delete( id: IDParam ): Promise< boolean > {
		if ( ! this.deleteHook ) {
			throw new Error( 'The \'delete\' hook is not defined.' );
		}

		return this.deleteHook( id );
	}
}
