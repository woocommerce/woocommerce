/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import * as baseContextHooks from '@woocommerce/base-context/hooks';

/**
 * Internal dependencies
 */
import PlaceOrderButton from '../index';

jest.mock( '@woocommerce/base-context/hooks', () => {
	const originalModule = jest.requireActual(
		'@woocommerce/base-context/hooks'
	);
	return {
		...originalModule,
		useCheckoutSubmit: jest.fn().mockReturnValue( {
			onSubmit: jest.fn(),
			isCalculating: false,
			isDisabled: false,
			waitingForProcessing: false,
			waitingForRedirect: false,
		} ),
	};
} );
describe( 'PlaceOrderButton', () => {
	it( `does not render the price and separator`, async () => {
		render( <PlaceOrderButton label="Place order" /> );
		expect( screen.getByText( /Place order/ ) ).toBeInTheDocument();
		expect( screen.queryByText( /&#36;0.00/ ) ).not.toBeInTheDocument();
	} );
	it( `renders the price and separator`, async () => {
		render( <PlaceOrderButton label="Place order" showPrice={ true } /> );
		expect( screen.getByText( /Place order/ ) ).toBeInTheDocument();
		expect( screen.getByText( /&#36;0.00/ ) ).toBeInTheDocument();
	} );
	it( `renders the spinner when processing`, async () => {
		jest.resetModules();
		baseContextHooks.useCheckoutSubmit.mockReturnValue( {
			onSubmit: jest.fn(),
			isCalculating: false,
			isDisabled: false,
			waitingForProcessing: true,
			waitingForRedirect: false,
		} );
		const { container } = render(
			<PlaceOrderButton label="Place order" showPrice={ true } />
		);
		expect( screen.queryByText( /Place order/ ) ).toHaveAttribute(
			'aria-hidden',
			'true'
		);
		expect(
			container.querySelector( '.wc-block-components-spinner' )
		).toBeInTheDocument();
	} );
} );
