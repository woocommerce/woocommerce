import { AbstractProduct } from './abstract-product';
import { HTTPClient } from '../../http';
import { ModelRepository } from '../../framework/model-repository';
import { AsyncFactory } from '../../framework/async-factory';
import { simpleProductFactory } from '../../factories/simple-product';
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
	 * @return {ModelRepository} The created repository.
	 */
	public static restRepository( httpClient: HTTPClient ): ModelRepository< SimpleProduct > {
		return simpleProductRESTRepository( httpClient );
	}

	/**
	 * Creates a new factory instance.
	 *
	 * @param {ModelRepository} repository The repository to use for creation.
	 * @return {AsyncFactory} The new factory instance.
	 */
	public static factory( repository: ModelRepository< SimpleProduct > ): AsyncFactory< SimpleProduct > {
		return simpleProductFactory( repository.create );
	}
}
