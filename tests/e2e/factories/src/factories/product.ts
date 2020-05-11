import { Factory } from 'fishery';
import { Product } from '../models/product';
import apiFetch from '@wordpress/api-fetch';

export default Factory.define< Product >( ( { params, onCreate } ) => {
	onCreate( async ( product ) => {
		return apiFetch( {
			url: '/wc/v3/products',
			method: 'POST',
			data: {
				type: 'simple',
				name: product.Name,
			},
		} ).then( ( data: any ) => {
			product.onCreated( data.id );
			return product;
		} );
	} );

	return new Product( params );
} );
