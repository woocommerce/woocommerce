/**
 * External dependencies
 */
import { fireEvent, render, screen } from '@testing-library/react';
import type { ReactNode } from 'react';

/**
 * Internal dependencies
 */
import { BlockSettings } from '..';

jest.mock( '@wordpress/block-editor', () => ( {
	InspectorControls: ( { children }: { children: ReactNode } ) => children,
} ) );

describe( 'Cart and checkout block settings', () => {
	it( 'shows dark mode but not step numbers for the cart', () => {
		render(
			<BlockSettings
				attributes={ {} }
				setAttributes={ jest.fn() }
				blockType="cart"
			/>
		);

		expect(
			screen.getByLabelText( 'Dark mode inputs' )
		).toBeInTheDocument();
		expect(
			screen.queryByLabelText( 'Show form step numbers' )
		).toBeNull();
	} );

	it( 'allows checkout step numbers to be changed', () => {
		const setAttributes = jest.fn();
		render(
			<BlockSettings
				attributes={ { showFormStepNumbers: false } }
				setAttributes={ setAttributes }
				blockType="checkout"
			/>
		);

		fireEvent.click( screen.getByLabelText( 'Show form step numbers' ) );
		expect( setAttributes ).toHaveBeenCalledWith( {
			showFormStepNumbers: true,
		} );
		expect(
			screen.getByLabelText( 'Dark mode inputs' )
		).toBeInTheDocument();
	} );
} );
