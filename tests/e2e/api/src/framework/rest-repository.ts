import { Repository, RepositoryData, TransformFn } from './repository';
import { HTTPClient, HTTPResponse } from '../http';

/**
 * An interface for describing the endpoints available to the repository.
 *
 * @typedef {Object} Endpoints
 * @property {string} create The creation endpoint.
 */
export interface Endpoints {
	create?: string;
}

/**
 * A repository for interacting with models via the REST API.
 */
export class RESTRepository< T extends RepositoryData > implements Repository< T > {
	private httpClient: HTTPClient | null = null;
	private readonly transformer: TransformFn< T >;
	private readonly endpoints: Endpoints;

	public constructor(
		transformer: TransformFn< T >,
		endpoints: Endpoints,
	) {
		this.transformer = transformer;
		this.endpoints = endpoints;
	}

	/**
	 * Sets the HTTP client that the repository should use for requests.
	 *
	 * @param {HTTPClient} httpClient The client to use.
	 */
	public setHTTPClient( httpClient: HTTPClient | null ): void {
		this.httpClient = httpClient;
	}

	/**
	 * Creates the data using the REST API.
	 *
	 * @param {*} data The model that we would like to create.
	 * @return {Promise} A promise that resolves to the model after creation.
	 */
	public async create( data: T ): Promise< T > {
		if ( ! this.httpClient ) {
			throw new Error( 'There is no HTTP client set to make the request.' );
		}
		if ( ! this.endpoints.create ) {
			throw new Error( 'There is no `create` endpoint defined.' );
		}

		const endpoint = this.endpoints.create.replace( /{[a-z]+}/i, this.replaceEndpointTokens( data ) );
		const transformed = this.transformer( data );

		return this.httpClient.post( endpoint, transformed )
			.then( ( response: HTTPResponse ) => {
				if ( response.status >= 400 ) {
					throw new Error( 'An error has occurred!' );
				}

				data.onCreated( response.data );

				return Promise.resolve( data );
			} );
	}

	/**
	 * Replaces the tokens in the endpoint according to properties of the data argument.
	 *
	 * @param {any} data The model to replace tokens using.
	 */
	private replaceEndpointTokens( data: any ): ( match: string ) => string {
		return ( match: string ) => data[ match ];
	}
}
