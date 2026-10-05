import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

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
vi.mock( '@woocommerce/data', async () => {
	const mock = {
		...( await vi.importActual( '@woocommerce/data' ) ),
		useSettings: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
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
vi.mock( '../historical-data', () => {
	const mock = {
		__esModule: true,
		default: () => <div>Historical Data</div>,
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../default-date', () => {
	const mock = {
		__esModule: true,
		default: () => <div>Default Date</div>,
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
describe( 'Analytics settings - date type', () => {
	const mockUpdateAndPersistSettings = vi.fn();
	beforeEach( () => {
		vi.clearAllMocks();
		useSettings.mockReturnValue( {
			settingsError: false,
			isRequesting: false,
			isDirty: false,
			persistSettings: vi.fn(),
			updateAndPersistSettings: mockUpdateAndPersistSettings,
			updateSettings: vi.fn(),
			wcAdminSettings: {
				woocommerce_date_type: 'date_completed',
			},
		} );
	} );
	afterEach( () => {
		vi.restoreAllMocks();
	} );
	it( 'defines date_paid as the default value, matching reports behavior', () => {
		expect( config.woocommerce_date_type.defaultValue ).toBe( 'date_paid' );
	} );
	it( 'renders the date type selector with the saved value', () => {
		const errorSpy = vi
			.spyOn( console, 'error' )
			.mockImplementation( () => {} );
		render( <Settings createNotice={ vi.fn() } query={ {} } /> );
		expect( screen.getByRole( 'combobox' ) ).toHaveValue(
			'date_completed'
		);
		expect( errorSpy ).toHaveBeenCalledWith(
			'Warning: Failed %s type: %s%s',
			'prop',
			expect.stringContaining( 'Invalid prop `helpText`' ),
			expect.any( String )
		);
	} );
	it( 'resets the date type to date_paid when resetting to defaults', () => {
		vi.spyOn( window, 'confirm' ).mockReturnValue( true );
		render( <Settings createNotice={ vi.fn() } query={ {} } /> );
		fireEvent.click(
			screen.getByRole( 'button', {
				name: /reset defaults/i,
			} )
		);
		expect( mockUpdateAndPersistSettings ).toHaveBeenCalledWith(
			'wcAdminSettings',
			expect.objectContaining( {
				woocommerce_date_type: 'date_paid',
			} )
		);
	} );
} );
