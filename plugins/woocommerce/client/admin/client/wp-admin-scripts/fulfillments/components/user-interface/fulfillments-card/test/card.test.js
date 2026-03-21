/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import FulfillmentCard from '../card';

jest.mock( '@wordpress/components', () => ( {
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
		// Click header to expand
		fireEvent.click( screen.getByRole( 'button' ) );
		expect( screen.getByText( 'Child content' ) ).toBeInTheDocument();
	} );

	it( 'renders as collapsable and toggles visibility', () => {
		render(
			<FulfillmentCard header={ <h1>Header</h1> } isCollapsable>
				<p>Child content</p>
			</FulfillmentCard>
		);

		const header = screen.getByRole( 'button' );
		expect( screen.queryByText( 'Child content' ) ).not.toBeInTheDocument();

		fireEvent.click( header );
		expect( screen.getByText( 'Child content' ) ).toBeInTheDocument();

		fireEvent.click( header );
		expect( screen.queryByText( 'Child content' ) ).not.toBeInTheDocument();
	} );

	it( 'renders without clickable header when not collapsable', () => {
		render(
			<FulfillmentCard header={ <h1>Header</h1> } isCollapsable={ false }>
				<p>Child content</p>
			</FulfillmentCard>
		);

		expect( screen.queryByRole( 'button' ) ).not.toBeInTheDocument();
		// Children should not be visible if not collapsable (matches component behavior)
		expect( screen.queryByText( 'Child content' ) ).not.toBeInTheDocument();
	} );

	it( 'renders children if initialState is expanded (collapsable)', () => {
		render(
			<FulfillmentCard
				header={ <h1>Header</h1> }
				isCollapsable
				initialState="expanded"
			>
				<p>Child content</p>
			</FulfillmentCard>
		);

		// Children should be visible immediately when initialState is expanded
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

		expect( screen.getByRole( 'button' ) ).toBeInTheDocument();
		expect( screen.queryByText( 'Child content' ) ).not.toBeInTheDocument();
	} );

	it( 'supports keyboard interaction on collapsable header', () => {
		render(
			<FulfillmentCard header={ <h1>Header</h1> } isCollapsable>
				<p>Child content</p>
			</FulfillmentCard>
		);

		const header = screen.getByRole( 'button' );
		expect( screen.queryByText( 'Child content' ) ).not.toBeInTheDocument();

		fireEvent.keyUp( header, { key: 'Enter' } );
		expect( screen.getByText( 'Child content' ) ).toBeInTheDocument();

		fireEvent.keyUp( header, { key: ' ' } );
		expect( screen.queryByText( 'Child content' ) ).not.toBeInTheDocument();
	} );
} );
