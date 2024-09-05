/**
 * External dependencies
 */
import { render, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { MagicButton } from './magic-button';

describe( 'MagicButton', () => {
	it( 'renders correctly', () => {
		const { getByText } = render(
			<MagicButton onClick={ () => {} } label="Test Button" />
		);

		expect( getByText( 'Test Button' ) ).toBeInTheDocument();
	} );

	it( 'calls onClick prop when clicked', () => {
		const handleClick = jest.fn();

		const { getByText } = render(
			<MagicButton onClick={ handleClick } label="Test Button" />
		);

		fireEvent.click( getByText( 'Test Button' ) );

		expect( handleClick ).toHaveBeenCalledTimes( 1 );
	} );
} );
