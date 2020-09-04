import { DeepPartial } from 'fishery';
import { Product } from './product';
import { AdapterTypes, ModelRegistry } from '../framework/model-registry';
import { ModelFactory } from '../framework/model-factory';
import { APIAdapter } from '../framework/api/api-adapter';
import faker from 'faker/locale/en';

export class SimpleProduct extends Product {
	public constructor( partial: DeepPartial< SimpleProduct > = {} ) {
		super( partial );
		Object.assign( this, partial );
	}
}

/**
 * Registers the simple product factory and adapters.
 *
 * @param {ModelRegistry} registry The registry to hold the model reference.
 */
export function registerSimpleProduct( registry: ModelRegistry ): void {
	if ( null !== registry.getFactory( SimpleProduct ) ) {
		return;
	}

	const factory = ModelFactory.define< SimpleProduct, any, ModelFactory< SimpleProduct >>(
		( { params } ) => {
			return new SimpleProduct(
				{
					name: params.name ?? faker.commerce.productName(),
					regularPrice: params.regularPrice ?? faker.commerce.price(),
				},
			);
		},
	);
	registry.registerFactory( SimpleProduct, factory );

	const apiAdapter = new APIAdapter< SimpleProduct >(
		'/wc/v3/products',
		( model ) => {
			return {
				type: 'simple',
				name: model.name,
				regular_price: model.regularPrice,
			};
		},
	);
	registry.registerAdapter( SimpleProduct, AdapterTypes.API, apiAdapter );
}
