/**
 * Component tests for <UpdateAvailableChip> — RSM-140.
 *
 * The chip replaces the DataView auto-rendered "Updates" filter chip and
 * matches the design handoff: hidden at zero, count badge in blueberry,
 * aria-pressed for the active state.
 */

/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { UpdateAvailableChip } from '../settings-email-listing-update-chip';

jest.mock( '@wordpress/icons', () => ( {
	Icon: ( {
		icon,
		...rest
	}: { icon: unknown } & Record< string, unknown > ) => (
		<span data-testid="icon" { ...rest } />
	),
	starFilled: 'starFilled',
} ) );

describe( '<UpdateAvailableChip>', () => {
	it( 'renders nothing when count is 0', () => {
		const { container } = render(
			<UpdateAvailableChip
				count={ 0 }
				active={ false }
				onClick={ jest.fn() }
			/>
		);
		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'renders the localized "Update available" label and the count when count >= 1', () => {
		render(
			<UpdateAvailableChip
				count={ 3 }
				active={ false }
				onClick={ jest.fn() }
			/>
		);
		expect( screen.getByText( /update available/i ) ).toBeInTheDocument();
		expect( screen.getByText( '3' ) ).toBeInTheDocument();
	} );

	it( 'sets aria-pressed=false when not active', () => {
		render(
			<UpdateAvailableChip
				count={ 1 }
				active={ false }
				onClick={ jest.fn() }
			/>
		);
		expect(
			screen.getByRole( 'button', { name: /update available/i } )
		).toHaveAttribute( 'aria-pressed', 'false' );
	} );

	it( 'sets aria-pressed=true when active', () => {
		render(
			<UpdateAvailableChip
				count={ 2 }
				active={ true }
				onClick={ jest.fn() }
			/>
		);
		expect(
			screen.getByRole( 'button', { name: /update available/i } )
		).toHaveAttribute( 'aria-pressed', 'true' );
	} );

	it( 'calls onClick when clicked', () => {
		const onClick = jest.fn();
		render(
			<UpdateAvailableChip
				count={ 1 }
				active={ false }
				onClick={ onClick }
			/>
		);
		fireEvent.click(
			screen.getByRole( 'button', { name: /update available/i } )
		);
		expect( onClick ).toHaveBeenCalledTimes( 1 );
	} );
} );
