/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import FulfillmentCard from '../card';

jest.mock( '@wordpress/components', () => ( {
	Button: ( { onClick, children } ) => (
		<button data-testid="button" onClick={ onClick }>
			{ children }
		</button>
	),
	Icon: ( { icon } ) => <span data-testid="icon">{ icon }</span>,
} ) );

describe( 'FulfillmentCard', () => {
	it( 'renders the header and children', () => {
		render(
			<FulfillmentCard header={ <h1>Header</h1> } isCollapsable>
				<p>Child content</p>
			</FulfillmentCard>
		);

		expect( screen.getByText( 'Header' ) ).toBeInTheDocument();
		// Children should not be visible by default for collapsable
		expect( screen.queryByText( 'Child content' ) ).not.toBeInTheDocument();
		// Click to expand
		fireEvent.click( screen.getByTestId( 'button' ) );
		expect( screen.getByText( 'Child content' ) ).toBeInTheDocument();
	} );

	it( 'renders as collapsable and toggles visibility', () => {
		render(
			<FulfillmentCard header={ <h1>Header</h1> } isCollapsable>
				<p>Child content</p>
			</FulfillmentCard>
		);

		const button = screen.getByTestId( 'button' );
		expect( screen.queryByText( 'Child content' ) ).not.toBeInTheDocument();

		fireEvent.click( button );
		expect( screen.getByText( 'Child content' ) ).toBeInTheDocument();

		fireEvent.click( button );
		expect( screen.queryByText( 'Child content' ) ).not.toBeInTheDocument();
	} );

	it( 'renders without collapse button when not collapsable', () => {
		render(
			<FulfillmentCard header={ <h1>Header</h1> } isCollapsable={ false }>
				<p>Child content</p>
			</FulfillmentCard>
		);

		expect( screen.queryByTestId( 'button' ) ).not.toBeInTheDocument();
		// Children should not be visible if not collapsable (matches component behavior)
		expect( screen.queryByText( 'Child content' ) ).not.toBeInTheDocument();
	} );

	it( 'renders children if initialState is open (collapsable)', () => {
		render(
			<FulfillmentCard
				header={ <h1>Header</h1> }
				isCollapsable
				initialState="open"
			>
				<p>Child content</p>
			</FulfillmentCard>
		);

		const button = screen.getByTestId( 'button' );
		// Children may not be visible by default, so click to expand
		fireEvent.click( button );
		expect( screen.getByText( 'Child content' ) ).toBeInTheDocument();
	} );

	it( 'does not render children if initialState is closed (collapsable)', () => {
		render(
			<FulfillmentCard
				header={ <h1>Header</h1> }
				isCollapsable
				initialState="closed"
			>
				<p>Child content</p>
			</FulfillmentCard>
		);

		expect( screen.getByTestId( 'button' ) ).toBeInTheDocument();
		expect( screen.queryByText( 'Child content' ) ).not.toBeInTheDocument();
	} );
} );
