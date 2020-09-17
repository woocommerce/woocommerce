import { BuildOptions, DeepPartial, Factory } from 'fishery';
import { GeneratorFnOptions } from 'fishery/dist/types';

export class AsyncFactory< T, I = any > extends Factory< T, I > {
	private readonly creator: ( model: T ) => Promise< T >;

	/**
	 * Creates a new factory instance.
	 *
	 * @param {Function} generator The factory's generator function.
	 * @param {Function} creator The factory's creation function.
	 */
	public constructor( generator: ( opts: GeneratorFnOptions< T, I > ) => T, creator: ( model: T ) => Promise< T > ) {
		super( generator );
		this.creator = creator;
	}

	/**
	 * Create an object using your factory
	 *
	 * @param {DeepPartial}  params The parameters that should populate the object.
	 * @param {BuildOptions} options The options to be used in the builder.
	 * @return {Promise} Resolves to the created model.
	 */
	public create( params?: DeepPartial< T >, options?: BuildOptions< T, I > ): Promise< T > {
		const model = this.build( params, options );
		return this.creator( model );
	}

	/**
	 * Create an array of objects using your factory
	 *
	 * @param {number}       number The number of models to create.
	 * @param {DeepPartial}  params The parameters that should populate the object.
	 * @param {BuildOptions} options The options to be used in the builder.
	 * @return {Promise} Resolves to the created models.
	 */
	public createList( number: number, params?: DeepPartial< T >, options?: BuildOptions< T, I > ): Promise< T[] > {
		const models = this.buildList( number, params, options );
		const promises: Promise< T >[] = [];
		for ( const model of models ) {
			promises.push( this.create( model ) );
		}

		return Promise.all( promises );
	}
}
