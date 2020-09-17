import { Model } from '../models/model';

type CreateFn< T > = ( model: T ) => Promise< T >;
type ReadFn< T, P > = ( params: P ) => Promise< T >;
type UpdateFn< T > = ( model: T ) => Promise< T >;
type DeleteFn< T > = ( model: T ) => Promise< boolean >;

/**
 * The standard parameters for reading a model.
 */
interface DefaultReadParams {
	id: number;
}

/**
 * An interface for repositories that perform CRUD actions.
 */
export class ModelRepository< T extends Model, ReadParams = DefaultReadParams > {
	private readonly createHook: CreateFn< T >;
	private readonly readHook: ReadFn< T, ReadParams >;
	private readonly updateHook: UpdateFn< T >;
	private readonly deleteHook: DeleteFn< T >;

	/**
	 * Creates a new repository instance.
	 *
	 * @param {Function} createHook The hook for model creation.
	 * @param {Function} readHook The hook for model reading.
	 * @param {Function} updateHook The hook for model updating.
	 * @param {Function} deleteHook The hook for model deletion.
	 */
	public constructor(
		createHook: CreateFn< T >,
		readHook: ReadFn< T, ReadParams >,
		updateHook: UpdateFn< T >,
		deleteHook: DeleteFn< T >,
	) {
		this.createHook = createHook;
		this.readHook = readHook;
		this.updateHook = updateHook;
		this.deleteHook = deleteHook;
	}

	/**
	 * Creates the given model.
	 *
	 * @param {*} model The model that we would like to create.
	 * @return {Promise} A promise that resolves to the model after creation.
	 */
	public create( model: T ): Promise< T > {
		return this.createHook( model );
	}

	/**
	 * Reads the given model.
	 *
	 * @param {Object} params The parameters to help with reading the model.
	 * @return {Promise} A promise that resolves to the model.
	 */
	public read( params: ReadParams ): Promise< T > {
		return this.readHook( params );
	}

	/**
	 * Updates the given model.
	 *
	 * @param {*} model The model we want to update.
	 * @return {Promise} A promise that resolves to the model after updating.
	 */
	public update( model: T ): Promise< T > {
		return this.updateHook( model );
	}

	/**
	 * Deletes the given model.
	 *
	 * @param {*} model The model we want to delete.
	 * @return {Promise} A promise that resolves to "true" on success.
	 */
	public delete( model: T ): Promise< boolean > {
		return this.deleteHook( model );
	}
}
