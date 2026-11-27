/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { mockBrowserLocation } from '@woocommerce/internal-js-tests';

/**
 * Internal dependencies
 */
import AnalyticsError from '..';

describe( 'AnalyticsError', () => {
	const reloadMock = mockBrowserLocation( 'reload' );

	it( 'displays an error message', () => {
		render( <AnalyticsError /> );

		expect(
			screen.getByText(
				'There was an error getting your stats. Please try again.'
			)
		).toBeInTheDocument();
	} );

	it( 'shows reload button', () => {
		render( <AnalyticsError /> );

		expect(
			screen.getByRole( 'button', { name: 'Reload' } )
		).toBeInTheDocument();
	} );

	it( 'refreshes the page when Reload Page button is clicked', async () => {
		render( <AnalyticsError /> );

		await userEvent.click( screen.getByText( 'Reload' ) );

		expect( reloadMock ).toHaveBeenCalled();
	} );

	it( 'should match snapshot', () => {
		const { container } = render( <AnalyticsError /> );
		expect( container ).toMatchSnapshot();
	} );
} );
