import { AbstractProduct } from './abstract-product';
import { HTTPClient } from '../../http';
import { CreatesModels } from '../../framework/model-repository';
import { simpleProductRESTRepository } from '../../repositories/rest/simple-product';

/**
 * The simple product class.
 */
export class SimpleProduct extends AbstractProduct {
	public constructor( partial: Partial< SimpleProduct > = {} ) {
		super();
		Object.assign( this, partial );
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
