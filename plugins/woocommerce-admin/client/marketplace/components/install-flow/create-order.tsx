/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { Subscription } from '../my-subscriptions/types';

type CreateOrderSuccessResponse = {
	success: true;
	data: {
		subscription: Subscription;
	};
};

type CreateOrderErrorResponse = {
	success: false;
	data: {
		code: string;
		message: string;
		redirect_location?: string;
	};
};

type CreateOrderResponse =
	| CreateOrderSuccessResponse
	| CreateOrderErrorResponse;

function createOrder( productId: number ): Promise< CreateOrderResponse > {
	return apiFetch( {
		path: '/wc/v3/marketplace/create-order',
		method: 'POST',
		headers: {
			'Content-Type': 'application/json',
		},
		body: JSON.stringify( { product_id: productId } ),
	} );
}

export {
	createOrder,
	CreateOrderResponse,
	CreateOrderSuccessResponse,
	CreateOrderErrorResponse,
};
