/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import FulfillmentDrawer from '../fulfillment-drawer';

jest.mock( '../../../fulfillments/new-fulfillment-form', () => () => (
	<div data-testid="new-fulfillment-form" />
) );
jest.mock( '../../../fulfillments/fulfillments-list', () => () => (
	<div data-testid="fulfillments-list" />
) );
jest.mock( '../fulfillment-drawer-header', () => () => (
	<div data-testid="fulfillment-drawer-header" />
) );
jest.mock( '../../../../context/drawer-context', () => ( {
	FulfillmentDrawerProvider: ( { children } ) => (
		<div data-testid="drawer-provider">{ children }</div>
	),
} ) );
jest.mock( '~/error-boundary', () => ( {
	ErrorBoundary: ( { children } ) => (
		<div data-testid="error-boundary">{ children }</div>
	),
} ) );

describe( 'FulfillmentDrawer', () => {
	it( 'renders the drawer with all components when open', () => {
		const { container } = render(
			<FulfillmentDrawer
				isOpen={ true }
				onClose={ jest.fn() }
				orderId={ 123 }
			/>
		);

		expect( screen.getByTestId( 'error-boundary' ) ).toBeInTheDocument();
		expect( screen.getByTestId( 'drawer-provider' ) ).toBeInTheDocument();
		expect(
			screen.getByTestId( 'fulfillment-drawer-header' )
		).toBeInTheDocument();
		expect(
			screen.getByTestId( 'new-fulfillment-form' )
		).toBeInTheDocument();
		expect( screen.getByTestId( 'fulfillments-list' ) ).toBeInTheDocument();
		expect( container.querySelector( '.is-open' ) ).toBeInTheDocument();
	} );

	it( 'renders the drawer as closed when isOpen is false', () => {
		const { container } = render(
			<FulfillmentDrawer
				isOpen={ false }
				onClose={ jest.fn() }
				orderId={ 123 }
			/>
		);

		expect( container.querySelector( '.is-closed' ) ).toBeInTheDocument();
	} );
} );
