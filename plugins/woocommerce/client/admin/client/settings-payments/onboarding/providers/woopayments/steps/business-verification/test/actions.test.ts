/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { createEmbeddedKycSession } from '../utils/actions';

jest.mock( '@wordpress/api-fetch', () => jest.fn() );

const mockApiFetch = apiFetch as jest.Mock;

describe( 'business verification actions', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		mockApiFetch.mockResolvedValue( {
			session: {
				clientSecret: 'test-secret',
				expiresAt: 123,
				accountId: 'acct_test',
				isLive: false,
				accountCreated: true,
				publishableKey: 'pk_test',
				locale: 'en_US',
			},
		} );
	} );

	it( 'omits company structure when it is unset', async () => {
		await createEmbeddedKycSession(
			{
				country: 'JP',
				business_type: 'company',
				'company.structure': undefined,
				mcc: 'digital_products__books',
			},
			'/wc/v3/payments/onboarding/kyc/session',
			'settings'
		);

		expect( mockApiFetch ).toHaveBeenCalledWith( {
			url: '/wc/v3/payments/onboarding/kyc/session',
			method: 'POST',
			data: {
				self_assessment: {
					country: 'JP',
					business_type: 'company',
					mcc: 'digital_products__books',
				},
				source: 'settings',
			},
		} );
	} );
} );
