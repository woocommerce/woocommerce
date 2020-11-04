import { AbstractProduct } from './abstract-product';
import { HTTPClient } from '../../http';
import { simpleProductRESTRepository } from '../../repositories/rest/products/simple-product';
import { CreatesModels, ModelRepositoryParams } from '../../framework/model-repository';

/**
 * The parameters embedded in this generic can be used in the ModelRepository in order to give
 * type-safety in an incredibly granular way.
 */
export type SimpleProductRepositoryParams = ModelRepositoryParams< SimpleProduct, never, never, 'regularPrice' >;

/**
 * An interface for creating simple products using the repository.
 *
 * @typedef CreatesSimpleProducts
 * @alias CreatesModels.<SimpleProduct>
 */
export type CreatesSimpleProducts = CreatesModels< SimpleProductRepositoryParams >;

/**
 * A simple product object.
 */
export class SimpleProduct extends AbstractProduct {
	/**
	 * Creates a new simple product instance with the given properties
	 *
	 * @param {Object} properties The properties to set in the object.
	 */
	public constructor( properties?: Partial< SimpleProduct > ) {
		super();
		Object.assign( this, properties );
	}

	/**
	 * Creates a model repository configured for communicating via the REST API.
	 *
	 * @param {HTTPClient} httpClient The client for communicating via HTTP.
	 */
	public static restRepository( httpClient: HTTPClient ): ReturnType< typeof simpleProductRESTRepository > {
		return simpleProductRESTRepository( httpClient );
	}
}
