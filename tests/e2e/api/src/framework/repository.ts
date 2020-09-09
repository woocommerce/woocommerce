import { Model } from '../models/model';

/**
 * An interface for repositories that perform CRUD actions.
 */
export interface Repository< T extends Model > {
	/**
	 * Uses the repository to create the given data.
	 *
	 * @param {*} data The data that we would like to create.
	 * @return {Promise} A promise that resolves to the data after creation.
	 */
	create( data: T ): Promise< T >;
}
