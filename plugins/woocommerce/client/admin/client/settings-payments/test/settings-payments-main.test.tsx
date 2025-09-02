/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';
import { render, fireEvent, screen } from '@testing-library/react';
import { MemoryRouter as Router } from 'react-router-dom';

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
		render(
			<Router>
				<SettingsPaymentsMain />
			</Router>
		);

		expect( recordEvent ).toHaveBeenCalledWith(
			'settings_payments_pageview',
			expect.objectContaining( {
				business_country: expect.any( String ),
			} )
		);
	} );

	it( 'should trigger event recommendations_other_options when clicking the more payment options link', () => {
		render(
			<Router>
				<SettingsPaymentsMain />
			</Router>
		);

		fireEvent.click( screen.getByText( 'More payment options' ) );

		expect( recordEvent ).toHaveBeenCalledWith(
			'settings_payments_recommendations_other_options',
			expect.objectContaining( {
				available_payment_methods: expect.any( String ),
				business_country: expect.any( String ),
			} )
		);
	} );

	it( 'should navigate to the marketplace when clicking the more payment options link', () => {
		const { isFeatureEnabled } = jest.requireMock( '~/utils/features' );
		( isFeatureEnabled as jest.Mock ).mockReturnValue( true );

		const mockLocation = {
			href: 'test',
		} as Location;

		mockLocation.href = 'test';
		Object.defineProperty( global.window, 'location', {
			value: mockLocation,
		} );

		render(
			<Router>
				<SettingsPaymentsMain />
			</Router>
		);

		fireEvent.click( screen.getByText( 'More payment options' ) );

		expect( mockLocation.href ).toContain(
			'admin.php?page=wc-admin&tab=extensions&path=/extensions&category=payment-gateways'
		);
	} );
} );
