import { beforeEach, describe, expect, it, vi } from 'vitest';

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
import { isTaskListActive } from '../../../hooks/use-tasklists-state';
import { isFeatureEnabled } from '~/utils/features';
vi.mock( '@woocommerce/tracks', () => {
	const mock = {
		recordEvent: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../../../hooks/use-tasklists-state', () => {
	const mock = {
		isTaskListActive: vi.fn().mockReturnValue( false ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '~/utils/features', () => {
	const mock = {
		isFeatureEnabled: vi.fn().mockReturnValue( true ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@woocommerce/data', async () => {
	const mock = {
		...( await vi.importActual( '@woocommerce/data' ) ),
		useUserPreferences: vi.fn().mockReturnValue( {
			updateUserPreferences: vi.fn(),
		} ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@wordpress/data', async () => {
	const originalModule = await vi.importActual( '@wordpress/data' );
	return ( ( mock ) => ( {
		default: mock,
		...mock,
	} ) )( {
		__esModule: true,
		...originalModule,
		useSelect: vi.fn().mockReturnValue( {
			defaultHomescreenLayout: 'single_column',
			taskListComplete: false,
			isTaskListHidde: false,
		} ),
	} );
} );
describe( 'Activity Panel - Homescreen Display Options', () => {
	beforeEach( () => {
		vi.clearAllMocks();
		isTaskListActive.mockReturnValue( false );
		isFeatureEnabled.mockReturnValue( true );
		useUserPreferences.mockReturnValue( {
			updateUserPreferences: vi.fn(),
		} );
	} );
	it( 'correctly tracks opening the options', () => {
		const { getByRole } = render( <DisplayOptions /> );
		fireEvent.click(
			getByRole( 'button', {
				name: 'Display options',
			} )
		);
		expect( recordEvent ).toHaveBeenCalledWith(
			'homescreen_display_click'
		);
	} );
	it( 'correctly updates the homepage layout option', () => {
		const updateUserPreferences = vi.fn();
		useUserPreferences.mockReturnValue( {
			updateUserPreferences,
			homepage_layout: '',
		} );
		const { getByText, getByRole } = render( <DisplayOptions /> );
		fireEvent.click(
			getByRole( 'button', {
				name: 'Display options',
			} )
		);

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
	it( 'does not render when setup is active and analytics is disabled', () => {
		isTaskListActive.mockReturnValue( true );
		isFeatureEnabled.mockReturnValue( false );
		const { queryByRole } = render( <DisplayOptions /> );
		expect(
			queryByRole( 'button', {
				name: 'Display options',
			} )
		).toBeNull();
	} );
} );
