import { HTTPClient } from '../../../http';
import { CreateFn, ModelRepository } from '../../../framework/model-repository';
import { SimpleProduct } from '../../../models';
import { CreatesSimpleProducts, SimpleProductRepositoryParams } from '../../../models/products/simple-product';
import { ModelTransformer } from '../../../framework/model-transformer';
import { KeyChangeTransformation } from '../../../framework/transformations/key-change-transformation';
import { AddPropertyTransformation } from '../../../framework/transformations/add-property-transformation';

function createTransformer(): ModelTransformer< SimpleProduct > {
	return new ModelTransformer(
		[
			new AddPropertyTransformation( {}, { type: 'simple' } ),
			new KeyChangeTransformation< SimpleProduct >( { regularPrice: 'regular_price' } ),
		],
	);
}

function restCreate(
	httpClient: HTTPClient,
	transformer: ModelTransformer< SimpleProduct >,
): CreateFn< SimpleProductRepositoryParams > {
	return async ( properties ) => {
		const response = await httpClient.post(
			'/wc/v3/products',
			transformer.fromModel( properties ),
		);

		return Promise.resolve( transformer.toModel( SimpleProduct, response.data ) );
	};
}

/**
 * Creates a new ModelRepository instance for interacting with models via the REST API.
 *
 * @param {HTTPClient} httpClient The HTTP client for the REST requests to be made using.
 * @return {CreatesSimpleProducts} The created repository.
 */
export function simpleProductRESTRepository( httpClient: HTTPClient ): CreatesSimpleProducts {
	const transformer = createTransformer();

	return new ModelRepository(
		null,
		restCreate( httpClient, transformer ),
		null,
		null,
		null,
	);
}
