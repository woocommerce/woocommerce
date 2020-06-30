import { Factory } from 'fishery';
import { Product } from '../models/product';
import { APIAdapter } from '../adapters/api-adapter';

const productFactory = Factory.define<Product>( ( { params } ) => {
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
