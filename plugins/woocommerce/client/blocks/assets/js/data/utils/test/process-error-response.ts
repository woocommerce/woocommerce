/**
 * External dependencies
 */
import { ApiErrorResponse } from '@woocommerce/types';
import { createNotice } from '@woocommerce/base-utils';

/**
 * Internal dependencies
 */
import {
	getNoticeContextFromErrorResponse,
	processErrorResponse,
} from '../process-error-response';

jest.mock( '@wordpress/notices', () => ( {
	createNotice: jest.fn(),
} ) );

jest.mock( '@woocommerce/base-utils', () => ( {
	...jest.requireActual( '@woocommerce/base-utils' ),
	createNotice: jest.fn(),
} ) );

const errorResponse: ApiErrorResponse = {
	code: 'rest_invalid_param',
	message: 'Invalid parameter(s): billing_address, shipping_address',
	data: {
		status: 400,
		params: {
			billing_address:
				'Please ensure your government ID matches the confirmation.',
			shipping_address:
				'Please ensure your government ID matches the confirmation.',
		},
		details: {
			billing_address: {
				code: 'gov_id_mismatch',
				message:
					'Please ensure your government ID matches the confirmation.',
				data: null,
			},
			shipping_address: {
				code: 'gov_id_mismatch',
				message:
					'Please ensure your government ID matches the confirmation.',
				data: null,
			},
		},
	},
};

describe( 'getNoticeContextFromErrorResponse', () => {
	it( 'should generate notice contexts and ids for the correct fields/errors', () => {
		const result = getNoticeContextFromErrorResponse( errorResponse );

		expect( result ).toEqual( [
			{
				context: 'wc/checkout/billing-address',
				id: 'billing_address_gov_id_mismatch',
			},
			{
				context: 'wc/checkout/shipping-address',
				id: 'shipping_address_gov_id_mismatch',
			},
		] );
	} );

	it( 'should override the context if one is passed', () => {
		const context = 'test_context';
		const result = getNoticeContextFromErrorResponse(
			errorResponse,
			context
		);
		expect( result[ 0 ].context ).toEqual( 'test_context' );
	} );
} );

describe( 'processErrorResponse', () => {
	it( 'should dismiss old notices and create new ones', () => {
		processErrorResponse( errorResponse );
		expect( createNotice ).toHaveBeenCalledTimes( 2 );
		expect( createNotice ).toHaveBeenCalledWith(
			'error',
			'Please ensure your government ID matches the confirmation.',
			{
				id: 'billing_address_gov_id_mismatch',
				context: 'wc/checkout/billing-address',
			}
		);

		expect( createNotice ).toHaveBeenCalledWith(
			'error',
			'Please ensure your government ID matches the confirmation.',
			{
				id: 'shipping_address_gov_id_mismatch',
				context: 'wc/checkout/shipping-address',
			}
		);
	} );
} );
