/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import { BlockSettings } from '../index';

jest.mock( '@wordpress/block-editor', () => ( {
	...jest.requireActual( '@wordpress/block-editor' ),
	InspectorControls: jest.fn( ( { children } ) => <div>{ children }</div> ),
} ) );

describe( 'Cart and Checkout block settings', () => {
	it.each( [
		{ initialValue: false, expectedValue: true },
		{ initialValue: true, expectedValue: false },
	] )(
		'maps hasDarkControls=$initialValue to $expectedValue',
		async ( { initialValue, expectedValue } ) => {
			const user = userEvent.setup();
			const setAttributes = jest.fn();

			render(
				<BlockSettings
					attributes={ {
						hasDarkControls: initialValue,
						showFormStepNumbers: false,
					} }
					setAttributes={ setAttributes }
				/>
			);

			const darkModeToggle = screen.getByRole( 'checkbox', {
				name: 'Dark mode inputs',
			} );
			expect( darkModeToggle ).toHaveProperty( 'checked', initialValue );

			await user.click( darkModeToggle );

			expect( setAttributes ).toHaveBeenCalledTimes( 1 );
			expect( setAttributes ).toHaveBeenCalledWith( {
				hasDarkControls: expectedValue,
			} );
		}
	);
} );
