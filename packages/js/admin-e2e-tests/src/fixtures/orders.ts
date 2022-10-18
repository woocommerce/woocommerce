/**
 * External dependencies
 */
import { Order } from '@woocommerce/api';
/**
 * Internal dependencies
 */
import { httpClient } from './http-client';

const repository = Order.restRepository( httpClient );

export async function createOrder( status = 'completed' ): Promise< Order > {
	// The repository can now be used to create models.
	return await repository.create( {
		paymentMethod: 'cod',
		status,
	} );
}

export async function removeAllOrders(): Promise< ( boolean | undefined )[] > {
	const products = await repository.list();
	return await Promise.all(
		products
			.map( ( pr ) => ( pr.id ? repository.delete( pr.id ) : undefined ) )
			.filter( ( pr ) => !! pr )
	);
}
