/**
 * A function that should transform data into a format to be consumed in a repository.
 */
export type TransformFn< T > = ( data: T ) => any;

/**
 * An interface for data that repositories interact with.
 */
export interface RepositoryData {
	/**
	 * Marks that the model was created.
	 *
	 * @param {*} data The data from the repository.
	 */
	onCreated( data: any ): void;
}

/**
 * An interface for repositories that perform CRUD actions.
 */
export interface Repository< T extends RepositoryData > {
	/**
	 * Uses the repository to create the given data.
	 *
	 * @param {*} data The data that we would like to create.
	 * @return {Promise} A promise that resolves to the data after creation.
	 */
	create( data: T ): Promise< T >;
}
