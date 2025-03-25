/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';
import { render, fireEvent } from '@testing-library/react';
import { createMemoryHistory } from 'history';
import { unstable_HistoryRouter as HistoryRouter } from 'react-router-dom';

/**
 * Internal dependencies
 */
import { SettingsPaymentsMethods } from '..';

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );

describe( 'SettingsPaymentsMethods', () => {
	it( 'should fire wcpay_settings_payment_methods_pageview event on load', () => {
		const history = createMemoryHistory();
		history.push( '/payment-methods' );

		render(
			<HistoryRouter history={ history }>
				<SettingsPaymentsMethods />
			</HistoryRouter>
		);

		expect( recordEvent ).toHaveBeenCalledWith(
			'wcpay_settings_payment_methods_pageview'
		);
	} );

	it( 'should fire wcpay_settings_payment_methods_continue event on Continue button click', () => {
		const history = createMemoryHistory();
		history.push( '/payment-methods' );

		const { getByText } = render(
			<HistoryRouter history={ history }>
				<SettingsPaymentsMethods />
			</HistoryRouter>
		);

		const continueButton = getByText( 'Continue' );
		fireEvent.click( continueButton );

		expect( recordEvent ).toHaveBeenCalledWith(
			'wcpay_settings_payment_methods_continue',
			{
				selected_payment_methods: expect.any( String ),
				deselected_payment_methods: expect.any( String ),
			}
		);
	} );
} );
