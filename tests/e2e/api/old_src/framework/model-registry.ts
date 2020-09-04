import { Adapter } from './adapter';
import { Model } from './model';
import { ModelFactory } from './model-factory';

type Registry< T > = { [key: string ]: T };

/**
 * The types of adapters that can be stored in the registry.
 *
 * @typedef AdapterTypes
 * @property {string} API      "api"
 * @property {string} Custom   "custom"
 */
export enum AdapterTypes {
	API = 'api',
	Custom = 'custom'
}

/**
 * A registry that allows for us to easily manage all of our factories and related state.
 */
export class ModelRegistry {
	private readonly factories: Registry< ModelFactory< any >> = {};
	private readonly adapters: { [key in AdapterTypes]: Registry< Adapter< any >> } = {
		api: {},
		custom: {},
	};

	/**
	 * Registers a factory for the class.
	 *
	 * @param {Function}     modelClass The class of model we're registering the factory for.
	 * @param {ModelFactory} factory The factory that we're registering.
	 */
	public registerFactory< T extends Model >( modelClass: new () => T, factory: ModelFactory< T > ): void {
		if ( this.factories.hasOwnProperty( modelClass.name ) ) {
			throw new Error( 'A factory of this type has already been registered for the model class.' );
		}

		this.factories[ modelClass.name ] = factory;
	}

	/**
	 * Fetches a factory that was registered for the class.
	 *
	 * @param {Function} modelClass The class of model for the factory we're fetching.
	 */
	public getFactory< T extends Model >( modelClass: new () => T ): ModelFactory< T > | null {
		if ( this.factories.hasOwnProperty( modelClass.name ) ) {
			return this.factories[ modelClass.name ];
		}

		return null;
	}

	/**
	 * Registers an adapter for the class.
	 *
	 * @param {Function}     modelClass The class of model that we're registering the adapter for.
	 * @param {AdapterTypes} type The type of adapter that we're registering.
	 * @param {Adapter}      adapter The adapter that we're registering.
	 */
	public registerAdapter< T extends Model >( modelClass: new () => T, type: AdapterTypes, adapter: Adapter< T > ): void {
		if ( this.adapters[ type ].hasOwnProperty( modelClass.name ) ) {
			throw new Error( 'An adapter of this type has already been registered for the model class.' );
		}

		this.adapters[ type ][ modelClass.name ] = adapter;
	}

	/**
	 * Fetches an adapter registered for the class.
	 *
	 * @param {Function}     modelClass The class of the model for the adapter we're fetching.
	 * @param {AdapterTypes} type The type of adapter we're fetching.
	 */
	public getAdapter< T extends Model >( modelClass: new () => T, type: AdapterTypes ): Adapter< T > | null {
		if ( this.adapters[ type ].hasOwnProperty( modelClass.name ) ) {
			return this.adapters[ type ][ modelClass.name ];
		}

		return null;
	}

	/**
	 * Fetches all of the adapters of a given type from the registry.
	 *
	 * @param {AdapterTypes} type The type of adapters to fetch.
	 */
	public getAdapters( type: AdapterTypes ): Adapter< any >[] {
		return Object.values( this.adapters[ type ] );
	}

	/**
	 * Changes the adapter a factory is using.
	 *
	 * @param {Function} modelClass The class of the model factory we're changing.
	 * @param {AdapterTypes} type The type of adapter to set.
	 */
	public changeFactoryAdapter< T extends Model >( modelClass: new () => T, type: AdapterTypes ): void {
		const factory = this.getFactory( modelClass );
		if ( ! factory ) {
			throw new Error( 'No factory defined for this model class.' );
		}
		const adapter = this.getAdapter( modelClass, type );
		if ( ! adapter ) {
			throw new Error( 'No adapter of this type registered for this model class.' );
		}

		factory.setAdapter( adapter );
	}

	/**
	 * Changes the adapters of all factories to the given type or null if one is not registered for that type.
	 *
	 * @param {AdapterTypes} type The type of adapter to set.
	 */
	public changeAllFactoryAdapters( type: AdapterTypes ): void {
		for ( const key in this.factories ) {
			this.factories[ key ].setAdapter(
				this.adapters[ type ][ key ] || null,
			);
		}
	}
}
