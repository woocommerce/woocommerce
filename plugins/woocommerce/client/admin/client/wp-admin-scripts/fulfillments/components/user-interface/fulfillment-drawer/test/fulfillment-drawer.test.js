import { describe, expect, it, vi } from 'vitest';

/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import FulfillmentDrawer from '../fulfillment-drawer';
vi.mock( '../../../fulfillments/new-fulfillment-form', () => {
	const mock = () => <div data-testid="new-fulfillment-form" />;
	return {
		default: mock,
		...mock,
	};
} );
vi.mock( '../../../fulfillments/fulfillments-list', () => {
	const mock = () => <div data-testid="fulfillments-list" />;
	return {
		default: mock,
		...mock,
	};
} );
vi.mock( '../fulfillment-drawer-header', () => {
	const mock = () => <div data-testid="fulfillment-drawer-header" />;
	return {
		default: mock,
		...mock,
	};
} );
vi.mock( '../../../../context/drawer-context', () => {
	const mock = {
		FulfillmentDrawerProvider: ( { children } ) => (
			<div data-testid="drawer-provider">{ children }</div>
		),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '~/error-boundary', () => {
	const mock = {
		ErrorBoundary: ( { children } ) => (
			<div data-testid="error-boundary">{ children }</div>
		),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
describe( 'FulfillmentDrawer', () => {
	it( 'renders the drawer with all components when open', () => {
		const { container } = render(
			<FulfillmentDrawer
				isOpen={ true }
				onClose={ vi.fn() }
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
				onClose={ vi.fn() }
				orderId={ 123 }
			/>
		);
		expect( container.querySelector( '.is-closed' ) ).toBeInTheDocument();
	} );
} );
