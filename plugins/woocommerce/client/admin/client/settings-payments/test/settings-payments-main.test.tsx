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

		render(
			<Router>
				<SettingsPaymentsMain />
			</Router>
		);

		const morePaymentOptionsLink = screen.getByText(
			'More payment options'
		);

		// Verify the link has the correct href attribute for external navigation
		expect( morePaymentOptionsLink.closest( 'a' ) ).toHaveAttribute(
			'href',
			'https://woocommerce.com/product-category/woocommerce-extensions/payment-gateways/?utm_source=payments_recommendations'
		);

		// Verify the link opens in a new tab
		expect( morePaymentOptionsLink.closest( 'a' ) ).toHaveAttribute(
			'target',
			'_blank'
		);

		// Verify security attributes are present for external links
		expect( morePaymentOptionsLink.closest( 'a' ) ).toHaveAttribute(
			'rel',
			expect.stringContaining( 'noopener' )
		);

		expect( morePaymentOptionsLink.closest( 'a' ) ).toHaveAttribute(
			'rel',
			expect.stringContaining( 'noreferrer' )
		);
	} );
} );
