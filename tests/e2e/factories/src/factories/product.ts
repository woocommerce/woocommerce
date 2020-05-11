import { Factory } from 'fishery';
import { Product } from '../models';
import apiFetch from '@wordpress/api-fetch';

export default Factory.define< Product >( ( { params, onCreate } ) => {
	onCreate( async ( product ) => {
		return apiFetch( {
			url: '/wc/v3/products',
			method: 'POST',
			data: {
				type: 'simple',
				name: product.Name,
				regular_price: product.RegularPrice,
			},
		} ).then( ( data: any ) => {
			product.onCreated( data.id );
			return product;
		} );
	} );

	return new Product( params );
} );
