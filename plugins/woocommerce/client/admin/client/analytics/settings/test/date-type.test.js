/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';
import { useSettings } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import Settings from '../index';
import { config } from '../config';

jest.mock( '@woocommerce/data', () => ( {
	useSettings: jest.fn(),
} ) );

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );

jest.mock( '../historical-data', () => ( {
	__esModule: true,
	default: () => <div>Historical Data</div>,
} ) );

jest.mock( '../default-date', () => ( {
	__esModule: true,
	default: () => <div>Default Date</div>,
} ) );

describe( 'Analytics settings - date type', () => {
	const mockUpdateAndPersistSettings = jest.fn();

	beforeEach( () => {
		jest.clearAllMocks();

		useSettings.mockReturnValue( {
			settingsError: false,
			isRequesting: false,
			isDirty: false,
			persistSettings: jest.fn(),
			updateAndPersistSettings: mockUpdateAndPersistSettings,
			updateSettings: jest.fn(),
			wcAdminSettings: {
				woocommerce_date_type: 'date_completed',
			},
		} );
	} );

	afterEach( () => {
		jest.restoreAllMocks();
	} );

	it( 'defines date_paid as the default value, matching reports behavior', () => {
		expect( config.woocommerce_date_type.defaultValue ).toBe( 'date_paid' );
	} );

	it( 'renders the date type selector with the saved value', () => {
		render( <Settings createNotice={ jest.fn() } query={ {} } /> );

		expect( screen.getByRole( 'combobox' ) ).toHaveValue(
			'date_completed'
		);
	} );

	it( 'resets the date type to date_paid when resetting to defaults', () => {
		jest.spyOn( window, 'confirm' ).mockReturnValue( true );

		render( <Settings createNotice={ jest.fn() } query={ {} } /> );

		fireEvent.click(
			screen.getByRole( 'button', { name: /reset defaults/i } )
		);

		expect( mockUpdateAndPersistSettings ).toHaveBeenCalledWith(
			'wcAdminSettings',
			expect.objectContaining( {
				woocommerce_date_type: 'date_paid',
			} )
		);
	} );
} );
