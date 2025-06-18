/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';
import { render, fireEvent, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { SettingsPaymentsMain } from '../settings-payments-main';

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );

jest.mock( '~/utils/features', () => ( {
	isFeatureEnabled: jest.fn(),
} ) );

describe( 'SettingsPaymentsMain', () => {
	it( 'should record settings_payments_pageview event on load', () => {
		render( <SettingsPaymentsMain /> );

		expect( recordEvent ).toHaveBeenCalledWith(
			'settings_payments_pageview',
			{
				business_country: expect.any( String ),
			}
		);
	} );

	it( 'should trigger event recommendations_other_options when clicking the WooCommerce Marketplace link', () => {
		render( <SettingsPaymentsMain /> );

		fireEvent.click( screen.getByText( 'the WooCommerce Marketplace' ) );

		expect( recordEvent ).toHaveBeenCalledWith(
			'settings_payments_recommendations_other_options',
			{
				available_payment_methods: expect.any( String ),
				business_country: expect.any( String ),
			}
		);
	} );

	it( 'should navigate to the marketplace when clicking the WooCommerce Marketplace link', () => {
		const { isFeatureEnabled } = jest.requireMock( '~/utils/features' );
		( isFeatureEnabled as jest.Mock ).mockReturnValue( true );

		const mockLocation = {
			href: 'test',
		} as Location;

		mockLocation.href = 'test';
		Object.defineProperty( global.window, 'location', {
			value: mockLocation,
		} );

		render( <SettingsPaymentsMain /> );

		fireEvent.click( screen.getByText( 'the WooCommerce Marketplace' ) );

		expect( mockLocation.href ).toContain(
			'admin.php?page=wc-admin&tab=extensions&path=/extensions&category=payment-gateways'
		);
	} );
} );
