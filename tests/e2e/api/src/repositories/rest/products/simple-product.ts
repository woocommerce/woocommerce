import { HTTPClient } from '../../../http';
import { CreateFn, DeleteFn, ListFn, ModelRepository, ReadFn, UpdateFn } from '../../../framework/model-repository';
import { SimpleProduct } from '../../../models';
import {
	CreatesSimpleProducts,
	DeletesSimpleProducts,
	ListsSimpleProducts,
	ReadsSimpleProducts,
	SimpleProductRepositoryParams,
	UpdatesSimpleProducts,
} from '../../../models/products/simple-product';
import { ModelTransformer } from '../../../framework/model-transformer';
import { createProductTransformer } from './shared';

function restList(
	httpClient: HTTPClient,
	transformer: ModelTransformer< SimpleProduct >,
): ListFn< SimpleProductRepositoryParams > {
	return async () => {
		const response = await httpClient.get( '/wc/v3/products' );

		const list: SimpleProduct[] = [];
		for ( const raw of response.data ) {
			list.push( transformer.toModel( SimpleProduct, raw ) );
		}

		return Promise.resolve( list );
	};
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

function restRead(
	httpClient: HTTPClient,
	transformer: ModelTransformer< SimpleProduct >,
): ReadFn< SimpleProductRepositoryParams > {
	return async ( id ) => {
		const response = await httpClient.get( '/wc/v3/products/' + id );
		return Promise.resolve( transformer.toModel( SimpleProduct, response.data ) );
	};
}

function restUpdate(
	httpClient: HTTPClient,
	transformer: ModelTransformer< SimpleProduct >,
): UpdateFn< SimpleProductRepositoryParams > {
	return async ( id, params ) => {
		const response = await httpClient.patch(
			'/wc/v3/products/' + id,
			transformer.fromModel( params ),
		);

		return Promise.resolve( transformer.toModel( SimpleProduct, response.data ) );
	};
}

function restDelete( httpClient: HTTPClient ): DeleteFn {
	return async ( id ) => {
		await httpClient.delete( '/wc/v3/products/' + id );
		return Promise.resolve( true );
	};
}

/**
 * Creates a new ModelRepository instance for interacting with models via the REST API.
 *
 * @param {HTTPClient} httpClient The HTTP client for the REST requests to be made using.
 * @return {
 * 	ListsSimpleProducts|
 * 	CreatesSimpleProducts|
 * 	ReadsSimpleProducts|
 * 	UpdatesSimpleProducts|
 * 	DeletesSimpleProducts
 * } The created repository.
 */
export function simpleProductRESTRepository( httpClient: HTTPClient ): ListsSimpleProducts
	& CreatesSimpleProducts
	& ReadsSimpleProducts
	& UpdatesSimpleProducts
	& DeletesSimpleProducts {
	const transformer = createProductTransformer( 'simple' );

	return new ModelRepository(
		restList( httpClient, transformer ),
		restCreate( httpClient, transformer ),
		restRead( httpClient, transformer ),
		restUpdate( httpClient, transformer ),
		restDelete( httpClient ),
	);
}
