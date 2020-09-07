import { DeepPartial, Factory as BaseFactory, BuildOptions } from 'fishery';
import { Repository, RepositoryData } from './repository';
import { GeneratorFnOptions } from 'fishery/dist/types';

/**
 * A factory that can be used to create models using an adapter.
 */
export class RepositoryFactory< T extends RepositoryData, I = any > extends BaseFactory< T, I > {
	private repository: Repository< T > | null = null;

	public constructor( generator: ( opts: GeneratorFnOptions< T, I > ) => T ) {
		super( generator );
	}

	/**
	 * Sets the repository that the factory should use when creating data.
	 *
	 * @param {Repository|null} repository The repository to set.
	 */
	public setRepository( repository: Repository< T > | null ): void {
		this.repository = repository;
	}

	/**
	 * Create an object using your factory
	 *
	 * @param {DeepPartial}  params The parameters that should populate the object.
	 * @param {BuildOptions} options The options to be used in the builder.
	 * @return {Promise} Resolves to the created model.
	 */
	public create( params?: DeepPartial< T >, options?: BuildOptions< T, I > ): Promise< T > {
		if ( ! this.repository ) {
			throw new Error( 'The factory has no repository to create using.' );
		}

		const model = this.build( params, options );
		return this.repository.create( model );
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
		if ( ! this.repository ) {
			throw new Error( 'The factory has no repository to create using.' );
		}

		const models = this.buildList( number, params, options );
		const promises: Promise< T >[] = [];
		for ( const model of models ) {
			promises.push( this.repository.create( model ) );
		}

		return Promise.all( promises );
	}
}
