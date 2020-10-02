import { HTTPClient } from '../../../http';
import { CreateFn, ModelRepository } from '../../../framework/model-repository';
import { SimpleProduct } from '../../../models';
import { CreatesSimpleProducts, SimpleProductRepositoryParams } from '../../../models/products/simple-product';

function restCreate( httpClient: HTTPClient ): CreateFn< SimpleProductRepositoryParams > {
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
 * @return {CreatesSimpleProducts} The created repository.
 */
export function simpleProductRESTRepository( httpClient: HTTPClient ): CreatesSimpleProducts {
	return new ModelRepository(
		null,
		restCreate( httpClient ),
		null,
		null,
		null,
	);
}
