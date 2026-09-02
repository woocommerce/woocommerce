/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useSettings } from '@woocommerce/data';
import { useState } from '@wordpress/element';
import { recordEvent } from '@woocommerce/tracks';

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

// Load only the real component modules used by Analytics Settings. Importing
// the package barrel eagerly loads unrelated search code whose linked
// dependency is not collectable in this package's Jest runtime.
jest.mock( '@woocommerce/components', () => ( {
	DateRangeFilterPicker: jest.requireActual(
		'@woocommerce/components/date-range-filter-picker'
	).default,
	ScrollTo: jest.requireActual( '@woocommerce/components/scroll-to' ).default,
	SectionHeader: jest.requireActual(
		'@woocommerce/components/section-header'
	).default,
} ) );

// The linked interpolation package has no resolvable emitted Babel runtime in
// this isolated Jest workspace. Analytics Settings only needs its mixed text
// result here; none of the setting controls under test depend on interpolation.
jest.mock( '@automattic/interpolate-components', () => ( {
	__esModule: true,
	default: ( { mixedString } ) => mixedString,
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
	const mockPersistSettings = jest.fn();
	const mockUpdateAndPersistSettings = jest.fn();
	const mockUpdateSettings = jest.fn();
	const initialSettings = {
		woocommerce_excluded_report_order_statuses: [
			'pending',
			'cancelled',
			'failed',
		],
		woocommerce_actionable_order_statuses: [ 'processing', 'on-hold' ],
		woocommerce_default_date_range: 'period=month&compare=previous_year',
		woocommerce_date_type: 'date_completed',
		woocommerce_analytics_scheduled_import: 'yes',
	};
	let currentSettings;
	let setHarnessSettings;
	let navigationQuerySnapshots;

	const SettingsHarness = ( props ) => {
		const [ settings, setSettings ] = useState( initialSettings );
		currentSettings = settings;
		setHarnessSettings = setSettings;

		return <Settings { ...props } />;
	};

	beforeEach( () => {
		jest.clearAllMocks();
		currentSettings = initialSettings;
		setHarnessSettings = null;
		navigationQuerySnapshots = [];
		window.wpNavMenuUrlUpdate = jest.fn( ( query ) => {
			navigationQuerySnapshots.push( { ...query } );
		} );
		mockUpdateSettings.mockImplementation( ( namespace, settings ) => {
			currentSettings = settings;
			if ( setHarnessSettings ) {
				setHarnessSettings( settings );
			}
		} );

		useSettings.mockImplementation( () => ( {
			settingsError: false,
			isRequesting: false,
			isDirty: false,
			persistSettings: mockPersistSettings,
			updateAndPersistSettings: mockUpdateAndPersistSettings,
			updateSettings: mockUpdateSettings,
			wcAdminSettings: currentSettings,
		} ) );
	} );

	afterEach( () => {
		jest.restoreAllMocks();
		delete window.wpNavMenuUrlUpdate;
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

	it( 'updates, saves, and resets analytics settings through real controls', async () => {
		jest.spyOn( window, 'confirm' ).mockReturnValue( true );
		const query = {
			period: 'last_month',
			compare: 'previous_year',
			before: '2022-01-31',
			after: '2022-01-01',
			interval: 'day',
			type: 'primary',
			unrelated: 'preserved',
		};

		render(
			<SettingsHarness createNotice={ jest.fn() } query={ query } />
		);

		await userEvent.click(
			screen.getAllByRole( 'checkbox', { name: 'On hold' } )[ 0 ]
		);
		await userEvent.click(
			screen.getAllByRole( 'checkbox', {
				name: 'Pending payment',
			} )[ 1 ]
		);
		await userEvent.click(
			screen.getAllByRole( 'checkbox', { name: 'Failed' } )[ 1 ]
		);

		await userEvent.selectOptions(
			screen.getByRole( 'combobox' ),
			'date_created'
		);

		const expectedSettings = {
			...initialSettings,
			woocommerce_excluded_report_order_statuses: [
				'pending',
				'cancelled',
				'failed',
				'on-hold',
			],
			woocommerce_actionable_order_statuses: [
				'processing',
				'on-hold',
				'pending',
				'failed',
			],
			woocommerce_default_date_range:
				'period=month&compare=previous_year',
			woocommerce_date_type: 'date_created',
		};

		expect( mockUpdateSettings ).toHaveBeenNthCalledWith(
			1,
			'wcAdminSettings',
			{
				...initialSettings,
				woocommerce_excluded_report_order_statuses: [
					'pending',
					'cancelled',
					'failed',
					'on-hold',
				],
			}
		);
		expect( mockUpdateSettings ).toHaveBeenNthCalledWith(
			2,
			'wcAdminSettings',
			{
				...initialSettings,
				woocommerce_excluded_report_order_statuses: [
					'pending',
					'cancelled',
					'failed',
					'on-hold',
				],
				woocommerce_actionable_order_statuses: [
					'processing',
					'on-hold',
					'pending',
				],
			}
		);
		expect( mockUpdateSettings ).toHaveBeenNthCalledWith(
			3,
			'wcAdminSettings',
			{
				...expectedSettings,
				woocommerce_date_type: 'date_completed',
			}
		);
		expect( mockUpdateSettings ).toHaveBeenNthCalledWith(
			4,
			'wcAdminSettings',
			expectedSettings
		);

		await userEvent.click(
			screen.getByRole( 'button', { name: 'Save settings' } )
		);

		expect( mockPersistSettings ).toHaveBeenCalledTimes( 1 );
		expect( recordEvent ).toHaveBeenNthCalledWith(
			1,
			'analytics_settings_save',
			expectedSettings
		);
		expect( window.wpNavMenuUrlUpdate ).toHaveBeenCalledTimes( 1 );
		expect( navigationQuerySnapshots ).toEqual( [
			{
				period: undefined,
				compare: undefined,
				before: undefined,
				after: undefined,
				interval: undefined,
				type: undefined,
				unrelated: 'preserved',
			},
		] );

		await userEvent.click(
			screen.getByRole( 'button', { name: 'Reset defaults' } )
		);

		const defaultSettings = Object.fromEntries(
			Object.entries( config ).map( ( [ name, setting ] ) => [
				name,
				setting.defaultValue,
			] )
		);
		expect( mockUpdateAndPersistSettings ).toHaveBeenCalledTimes( 1 );
		expect( mockUpdateAndPersistSettings ).toHaveBeenCalledWith(
			'wcAdminSettings',
			defaultSettings
		);
		expect( recordEvent ).toHaveBeenNthCalledWith(
			2,
			'analytics_settings_reset_defaults'
		);
	} );
} );
