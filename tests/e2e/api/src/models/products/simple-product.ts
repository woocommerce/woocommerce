import { AbstractProduct } from './abstract-product';
import { HTTPClient } from '../../http';
import { CreatesModels } from '../../framework/model-repository';
import { simpleProductRESTRepository } from '../../repositories/rest/products/simple-product';

/**
 * A simple product object.
 */
export class SimpleProduct extends AbstractProduct {
	/**
	 * Creates a new simple product instance with the given properties
	 *
	 * @param {Object} properties The properties to set in the object.
	 */
	public constructor( properties: Partial< SimpleProduct > = {} ) {
		super();
		Object.assign( this, properties );
	}

	/**
	 * Creates a model repository configured for communicating via the REST API.
	 *
	 * @param {HTTPClient} httpClient The client for communicating via HTTP.
	 * @return {CreatesModels} The created repository.
	 */
	public static restRepository( httpClient: HTTPClient ): CreatesModels< SimpleProduct > {
		return simpleProductRESTRepository( httpClient );
	}
}
