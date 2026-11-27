/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import RoundedTotalInfo from '..';

const NOTE = 'Rounded to £5.98 from 3 units priced £1.992.';

describe( 'RoundedTotalInfo', () => {
	it( 'renders the total alone without a note', () => {
		const { container } = render(
			<RoundedTotalInfo note={ null }>
				<span>£5.97</span>
			</RoundedTotalInfo>
		);

		expect( container ).toHaveTextContent( '£5.97' );
		expect( screen.queryByRole( 'button' ) ).not.toBeInTheDocument();
	} );

	// The browser opens the popover from these attributes, and the shared wc-rounded-total-info script finds the
	// tooltip by them, so a wrong id or order means the note never shows.
	it( 'makes the total a button that opens the note in a popover right after it', () => {
		render(
			<RoundedTotalInfo note={ NOTE }>
				<span>£5.98</span>
			</RoundedTotalInfo>
		);

		const button = screen.getByRole( 'button', {
			name: '£5.98 Rounding details',
		} );
		const tooltip = button.nextElementSibling as HTMLElement;

		expect( button ).toHaveAttribute( 'aria-expanded', 'false' );
		expect( button ).toHaveAttribute( 'popovertargetaction', 'show' );
		expect( button.getAttribute( 'popovertarget' ) ).toBe( tooltip.id );
		expect( button ).toHaveAccessibleDescription( NOTE );
		expect( tooltip ).toHaveAttribute( 'popover', 'auto' );
		expect( tooltip ).toHaveAttribute( 'role', 'tooltip' );
		expect( tooltip ).toHaveTextContent( NOTE );
	} );
} );
