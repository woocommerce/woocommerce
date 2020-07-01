import { Adapter } from './adapter';
import { Model } from '../models/model';
import { ModelFactory } from './model-factory';

type Registry<T> = { [key: string ]: T };

/**
 * The types of adapters that can be stored in the registry.
 *
 * @typedef AdapterTypes
 * @property {string} API      "api"
 * @property {string} Database "database"
 * @property {string} Custom   "custom"
 */
export enum AdapterTypes {
	API = 'api',
	Database = 'database',
	Custom = 'custom'
}

/**
 * A registry that allows for us to easily manage all of our factories and related state.
 */
export class ModelRegistry {
	private readonly registry: Registry<ModelFactory<any>> = {};
	private readonly adapters: { [key in AdapterTypes]: Registry<Adapter<any>> } = {
		api: {},
		database: {},
		custom: {},
	};

	/**
	 * Registers a factory for the class.
	 *
	 * @param {Function}     modelClass The class of model we're registering the factory for.
	 * @param {ModelFactory} factory The factory that we're registering.
	 */
	public registerFactory<T extends Model>( modelClass: new () => T, factory: ModelFactory<T> ): void {
		if ( this.registry.hasOwnProperty( modelClass.name ) ) {
			throw new Error( 'A factory of this type has already been registered for the model class.' );
		}

		this.registry[ modelClass.name ] = factory;
	}

	/**
	 * Fetches a factory that was registered for the class.
	 *
	 * @param {Function} modelClass The class of model for the factory we're fetching.
	 */
	public getFactory<T extends Model>( modelClass: new () => T ): ModelFactory<T> | null {
		if ( this.registry.hasOwnProperty( modelClass.name ) ) {
			return this.registry[ modelClass.name ];
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
	public registerAdapter<T extends Model>( modelClass: new () => T, type: AdapterTypes, adapter: Adapter<T> ): void {
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
	public getAdapter<T extends Model>( modelClass: new () => T, type: AdapterTypes ): Adapter<T> | null {
		if ( this.adapters[ type ].hasOwnProperty( modelClass.name ) ) {
			return this.adapters[ type ][ modelClass.name ];
		}

		return null;
	}
}
