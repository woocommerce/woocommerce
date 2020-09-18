import { HTTPClient } from '../../http';
import { CreateFn, ModelRepository } from '../../framework/model-repository';
import { SimpleProduct } from '../../models';

/**
 * Creates a callback for REST model creation.
 *
 * @param {HTTPClient} httpClient The HTTP client for requests.
 * @return {Function} The callback for creating models via the REST API.
 */
function restCreate( httpClient: HTTPClient ): CreateFn< SimpleProduct > {
	return async ( properties ) => {
		const response = await httpClient.post(
			'/wc/v3/products',
			{
				name: properties.name,
			},
		);

		return Promise.resolve( new SimpleProduct( {
			id: response.data.id,
			name: response.data.name,
		} ) );
	};
}

/**
 * Creates a new ModelRepository instance for interacting with models via the REST API.
 *
 * @param {HTTPClient} httpClient The HTTP client for the REST requests to be made using.
 * @return {ModelRepository} A repository for interacting with models via the REST API.
 */
export function simpleProductRESTRepository( httpClient: HTTPClient ): ModelRepository< SimpleProduct > {
	return new ModelRepository(
		restCreate( httpClient ),
		null,
		null,
		null,
	);
}
