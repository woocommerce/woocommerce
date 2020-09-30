import { HTTPClient } from '../../../http';
import { CreateFn, CreatesModels, ModelRepository } from '../../../framework/model-repository';
import { SimpleProduct } from '../../../models';

/**
 * Creates a callback for REST model creation.
 *
 * @param {HTTPClient} httpClient The HTTP client for requests.
 * @return {CreateFn<SimpleProduct>} The callback for creating models via the REST API.
 */
function restCreate( httpClient: HTTPClient ): CreateFn< SimpleProduct > {
	return async ( properties ) => {
		const response = await httpClient.post(
			'/wc/v3/products',
			{
				type: 'simple',
				name: properties.name,
				regular_price: properties.regularPrice,
			},
		);

		return Promise.resolve( new SimpleProduct( {
			id: response.data.id,
			name: response.data.name,
			regularPrice: response.data.regular_price,
		} ) );
	};
}

/**
 * Creates a new ModelRepository instance for interacting with models via the REST API.
 *
 * @param {HTTPClient} httpClient The HTTP client for the REST requests to be made using.
 * @return {CreatesModels<SimpleProduct>} A repository for interacting with models via the REST API.
 */
export function simpleProductRESTRepository( httpClient: HTTPClient ): CreatesModels< SimpleProduct > {
	return new ModelRepository(
		null,
		restCreate( httpClient ),
		null,
		null,
		null,
	);
}
