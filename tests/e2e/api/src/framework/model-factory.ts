import { DeepPartial, Factory, BuildOptions } from 'fishery';
import { Model } from './model';
import { Adapter } from './adapter';

/**
 * A factory that can be used to create models using an adapter.
 */
export class ModelFactory<T extends Model, I = any> extends Factory<T, I> {
	private adapter: Adapter<T> | null = null;

	/**
	 * Sets the adapter that the factory will use to create models.
	 *
	 * @param {Adapter|null} adapter
	 */
	public setAdapter( adapter: Adapter<T> | null ): void {
		this.adapter = adapter;
	}

	/**
	 * Create an object using your factory
	 *
	 * @param {DeepPartial}  params The parameters that should populate the object.
	 * @param {BuildOptions} options The options to be used in the builder.
	 * @return {Promise} Resolves to the created model.
	 */
	public create( params?: DeepPartial<T>, options?: BuildOptions<T, I> ): Promise<T> {
		if ( ! this.adapter ) {
			throw new Error( 'The factory has no adapter to create using.' );
		}

		const model = this.build( params, options );
		return this.adapter.create( model );
	}

	/**
	 * Create an array of objects using your factory
	 *
	 * @param {number}       number The number of models to create.
	 * @param {DeepPartial}  params The parameters that should populate the object.
	 * @param {BuildOptions} options The options to be used in the builder.
	 * @return {Promise} Resolves to the created model.
	 */
	public createList( number: number, params?: DeepPartial<T>, options?: BuildOptions<T, I> ): Promise<T[]> {
		if ( ! this.adapter ) {
			throw new Error( 'The factory has no adapter to create using.' );
		}

		const model = this.buildList( number, params, options );
		return this.adapter.create( model );
	}
}
