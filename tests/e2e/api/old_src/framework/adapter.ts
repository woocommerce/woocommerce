import { Model } from './model';

/**
 * An interface for implementing adapters to create models.
 */
export interface Adapter< T extends Model > {
	/**
	 * Creates a model or array of models using a service..
	 *
	 * @param {Model|Model[]} model The model or array of models to create.
	 * @return {Promise} Resolves to the created input model or array of models.
	 */
	create( model: T ): Promise< T >;
	create( model: T[] ): Promise< T[]>;
	create( model: T | T[] ): Promise< T > | Promise< T[]>;
}
