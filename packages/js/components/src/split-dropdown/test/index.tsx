/**
 * External dependencies
 */
import { createEvent, fireEvent, render } from '@testing-library/react';
import { createElement } from '@wordpress/element';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { SplitDropdown } from '..';

describe( 'SplitDropdown', () => {
	test( 'it renders correctly collapsed', () => {
		const { getByText, queryByText, queryAllByRole } = render(
			<SplitDropdown>
				<Button>Default Action</Button>
				<Button className="test-collapsed-button">
					Secondary Action
				</Button>
				<Button className="test-collapsed-button">
					Tertiary Action
				</Button>
			</SplitDropdown>
		);
		// should have a default action button
		expect( getByText( 'Default Action' ) ).toBeInTheDocument();

		// should not yet have any of the dropdown actions
		expect( queryByText( 'Secondary Action' ) ).not.toBeInTheDocument();
		expect( queryByText( 'Tertiary Action' ) ).not.toBeInTheDocument();

		// should have a toggle button
		const toggleButton = queryAllByRole( 'button' ).pop();
		expect( toggleButton ).toBeInTheDocument();

		// should display other actions after toggle is clicked.
		if ( toggleButton !== undefined ) {
			const toggleClick = createEvent.click( toggleButton, {} );
			fireEvent( toggleButton, toggleClick );
		}
		expect( getByText( 'Secondary Action' ) ).toBeInTheDocument();
		expect( getByText( 'Tertiary Action' ) ).toBeInTheDocument();
	} );
	test( 'it does not render the toggle where there are no items for the dropdown menu', () => {
		const { container } = render(
			<SplitDropdown>
				<Button>Only Action</Button>
			</SplitDropdown>
		);

		// should have no toggle
		expect(
			container.getElementsByClassName(
				'woocommerce-split-dropdown__toggle'
			)?.length
		).toBe( 0 );
	} );
} );
