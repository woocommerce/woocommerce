import { Product } from '../models/product';
import { APIAdapter } from '../adapters/api-adapter';
import { ModelFactory } from '../factories/model-factory';

const productFactory: ModelFactory<Product> = ModelFactory.define<Product, any, ModelFactory<Product>>( ( { params } ) => {
	return new Product( params );
} );

const productAPIAdapter = new APIAdapter<Product>(
	'/wc/v3/product',
	( model ) => {
		return {
			type: 'simple',
			name: model.Name,
			regular_price: model.RegularPrice,
		};
	},
);

export { productFactory, productAPIAdapter };
