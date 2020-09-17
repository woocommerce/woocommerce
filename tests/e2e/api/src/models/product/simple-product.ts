import { AbstractProduct } from './abstract-product';
import * as faker from 'faker/locale/en';
import { HTTPClient } from '../../http';
import { ModelRepository } from '../../framework/model-repository';
import { AsyncFactory } from '../../framework/async-factory';

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
		return new ModelRepository(
			async ( model ) => {
				const response = await httpClient.post(
					'/wc/v3/products',
					{
						name: model.name,
					},
				);

				return Promise.resolve( new SimpleProduct( {
					id: response.data.id,
					name: response.data.name,
				} ) );
			},
			async ( params ) => {
				const response = await httpClient.get( '/wc/v3/products/' + params.id );

				const model = new SimpleProduct(
					{
						id: response.data.id,
						name: response.data.name,
					},
				);
				return Promise.resolve( model );
			},
			async ( model ) => {
				return httpClient.put(
					'/wc/v3/products/' + model.id,
					{
						id: model.id,
						name: model.name,
					},
				).then( () => model );
			},
			async ( model ) => {
				return httpClient.delete( '/wc/v3/products/' + model.id ).then( () => true );
			},
		);
	}

	/**
	 * Creates a new factory instance.
	 *
	 * @param {ModelRepository} repository The repository to use for creation.
	 * @return {AsyncFactory} The new factory instance.
	 */
	public static factory( repository: ModelRepository< SimpleProduct > ): AsyncFactory< SimpleProduct > {
		return new AsyncFactory< SimpleProduct >(
			( { params } ) => {
				return new SimpleProduct( {
					name: params.name ?? faker.commerce.productName(),
					regularPrice: params.regularPrice ?? faker.commerce.price(),
				} );
			},
			repository.create,
		);
	}
}
