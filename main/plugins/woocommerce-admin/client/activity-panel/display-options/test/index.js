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
jest.mock( '@wordpress/data', () => {
	const originalModule = jest.requireActual( '@wordpress/data' );

	return {
		__esModule: true,
		...originalModule,
		useSelect: jest.fn().mockReturnValue( {
			defaultHomescreenLayout: 'single_column',
			taskListComplete: false,
			isTaskListHidde: false,
		} ),
	};
} );

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
		expect( getByText( 'Single column' ).parentNode ).toBeChecked();
		expect( getByText( 'Two columns' ).parentNode ).not.toBeChecked();

		fireEvent.click( getByText( 'Two columns' ).parentNode );

		expect( recordEvent ).toHaveBeenCalledWith(
			'homescreen_display_option',
			{
				display_option: 'two_columns',
			}
		);

		expect( updateUserPreferences ).toHaveBeenCalledWith( {
			homepage_layout: 'two_columns',
		} );
	} );
} );
