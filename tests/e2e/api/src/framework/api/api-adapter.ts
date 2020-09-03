import { APIResponse, APIService } from './api-service';
import { Model } from '../model';
import { Adapter } from '../adapter';

/**
 * A callback for transforming models into an API request body.
 *
 * @callback APITransformerFn
 * @param {Model} model The model that we want to transform.
 * @return {*} The structured request data for the API.
 */
export type APITransformerFn<T extends Model> = ( model: T ) => any;

/**
 * A class used for creating data models using a supplied API endpoint.
 */
export class APIAdapter<T extends Model> implements Adapter<T> {
	private readonly endpoint: string;
	private readonly transformer: APITransformerFn<T>;
	private apiService: APIService | null;

	public constructor( endpoint: string, transformer: APITransformerFn<T> ) {
		this.endpoint = endpoint;
		this.transformer = transformer;
		this.apiService = null;
	}

	/**
	 * Sets the API service that the adapter should use for creation actions.
	 *
	 * @param {APIService|null} service The new API service for the adapter to use.
	 */
	public setAPIService( service: APIService | null ): void {
		this.apiService = service;
	}

	/**
	 * Creates a model or array of models using the API service.
	 *
	 * @param {Model|Model[]} model The model or array of models to create.
	 * @return {Promise} Resolves to the created input model or array of models.
	 */
	public create( model: T ): Promise<T>;
	public create( model: T[] ): Promise<T[]>;
	public create( model: T | T[] ): Promise<T> | Promise<T[]> {
		if ( ! this.apiService ) {
			throw new Error( 'An API service must be registered for the adapter to work.' );
		}

		if ( Array.isArray( model ) ) {
			return this.createList( model );
		}

		return this.createSingle( model );
	}

	/**
	 * Creates a single model using the API service.
	 *
	 * @param {Model} model The model to create.
	 * @return {Promise} Resolves to the created input model.
	 */
	private async createSingle( model: T ): Promise<T> {
		return this.apiService!.post(
			this.endpoint,
			this.transformer( model ),
		).then( ( data: APIResponse ) => {
			model.setID( data.data.id );
			return model;
		} );
	}

	/**
	 * Creates an array of models using the API service.
	 *
	 * @param {Model[]} models The array of models to create.
	 * @return {Promise} Resolves to the array of created input models.
	 */
	private async createList( models: T[] ): Promise<T[]> {
		const promises: Promise<T>[] = [];
		for ( const model of models ) {
			promises.push( this.createSingle( model ) );
		}

		return Promise.all( promises );
	}
}
