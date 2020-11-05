import { HTTPClient } from '../../../http';
import { CreateFn, ModelRepository } from '../../../framework/model-repository';
import { SimpleProduct } from '../../../models';
import { CreatesSimpleProducts, SimpleProductRepositoryParams } from '../../../models/products/simple-product';
import { ModelTransformer } from '../../../framework/model-transformer';
import { KeyChangeTransformation } from '../../../framework/transformations/key-change-transformation';

function fromServer( data: any ): SimpleProduct {
	if ( ! data.id ) {
		throw new Error( 'An invalid response was received.' );
	}

	const t = new ModelTransformer< SimpleProduct >(
		[
			new KeyChangeTransformation< SimpleProduct >( { regularPrice: 'regular_price' } ),
		],
	);

	return t.toModel( SimpleProduct, data );
}

function toServer( model: Partial< SimpleProduct > ): any {
	const t = new ModelTransformer< SimpleProduct >(
		[
			new KeyChangeTransformation< SimpleProduct >( { regularPrice: 'regular_price' } ),
		],
	);

	return Object.assign(
		{ type: 'simple' },
		t.fromModel( model ),
	);
}

function restCreate( httpClient: HTTPClient ): CreateFn< SimpleProductRepositoryParams > {
	return async ( properties ) => {
		const response = await httpClient.post(
			'/wc/v3/products',
			toServer( properties ),
		);

		return Promise.resolve( fromServer( response.data ) );
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
