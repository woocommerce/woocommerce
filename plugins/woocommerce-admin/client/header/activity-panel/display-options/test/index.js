/**
 * External dependencies
 */
import { render, fireEvent } from '@testing-library/react';
import { useUserPreferences } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { DisplayOptions } from '../';

jest.mock( '@woocommerce/tracks', () => ( { recordEvent: jest.fn() } ) );
jest.mock( '@woocommerce/data', () => ( {
	...jest.requireActual( '@woocommerce/data' ),
	useUserPreferences: jest
		.fn()
		.mockReturnValue( { updateUserPreferences: jest.fn() } ),
} ) );

describe( 'Activity Panel - Homescreen Display Options', () => {
	it( 'correctly tracks opening the options', () => {
		const { getByRole } = render( <DisplayOptions /> );

		fireEvent.click( getByRole( 'button', { name: 'Display options' } ) );

		expect( recordEvent ).toHaveBeenCalledWith(
			'homescreen_display_click'
		);
	} );

	it( 'correctly updates the homepage layout option', () => {
		const updateUserPreferences = jest.fn();
		useUserPreferences.mockReturnValue( {
			updateUserPreferences,
			homepage_layout: '',
		} );
		const { getByText, getByRole } = render( <DisplayOptions /> );

		fireEvent.click( getByRole( 'button', { name: 'Display options' } ) );

		// Verify the default of two columns.
		expect(
			getByText( 'Single column', { selector: 'button' } )
		).not.toBeChecked();
		expect(
			getByText( 'Two columns', { selector: 'button' } )
		).toBeChecked();

		fireEvent.click( getByText( 'Single column', { selector: 'button' } ) );

		expect( recordEvent ).toHaveBeenCalledWith(
			'homescreen_display_option',
			{
				display_option: 'single_column',
			}
		);

		expect( updateUserPreferences ).toHaveBeenCalledWith( {
			homepage_layout: 'single_column',
		} );
	} );
} );
