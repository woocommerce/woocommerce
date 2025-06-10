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
			<FulfillmentCard header={ <h1>Header</h1> }>
				<p>Child content</p>
			</FulfillmentCard>
		);

		expect( screen.getByText( 'Header' ) ).toBeInTheDocument();
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
		expect( screen.getByText( 'Child content' ) ).toBeInTheDocument();
	} );
} );
