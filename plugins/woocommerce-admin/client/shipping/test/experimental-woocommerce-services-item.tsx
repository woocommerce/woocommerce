/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import WooCommerceServicesItem from '../experimental-woocommerce-services-item';

describe( 'WooCommerceServicesItem', () => {
	it( 'should render WCS item with CTA = "Get started" when WCS is not installed', () => {
		render( <WooCommerceServicesItem isWCSInstalled={ false } /> );

		expect(
			screen.queryByText( 'Woocommerce Shipping' )
		).toBeInTheDocument();

		expect(
			screen.queryByRole( 'button', { name: 'Get started' } )
		).toBeInTheDocument();
	} );

	it( 'should render WCS item with CTA = "Activate" when WCS is installed', () => {
		render( <WooCommerceServicesItem isWCSInstalled={ true } /> );

		expect(
			screen.queryByText( 'Woocommerce Shipping' )
		).toBeInTheDocument();

		expect(
			screen.queryByRole( 'button', { name: 'Activate' } )
		).toBeInTheDocument();
	} );
} );
