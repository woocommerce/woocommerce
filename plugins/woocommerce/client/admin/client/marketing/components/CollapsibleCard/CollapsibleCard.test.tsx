/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import { CollapsibleCard } from './CollapsibleCard';

const header = 'Card header';
const body = 'Card body';

describe( 'CollapsibleCard component', () => {
	it( 'should render a card that can be expanded or collapsed when the card header is clicked', async () => {
		render( <CollapsibleCard header={ header }>{ body }</CollapsibleCard> );

		// Card is expanded by default.
		expect( screen.queryByText( header ) ).toBeInTheDocument();
		expect( screen.queryByText( body ) ).toBeInTheDocument();

		// Click on card header to collapsed the card.
		await userEvent.click( screen.getByText( header ) );

		// Card body should not be there.
		expect( screen.queryByText( body ) ).not.toBeInTheDocument();
	} );

	it( 'should render a card that is collapsed by default when `initialCollapsed` is set', async () => {
		render(
			<CollapsibleCard initialCollapsed header={ header }>
				{ body }
			</CollapsibleCard>
		);

		// Card is collapsed by default.
		expect( screen.queryByText( header ) ).toBeInTheDocument();
		expect( screen.queryByText( body ) ).not.toBeInTheDocument();
	} );
} );
