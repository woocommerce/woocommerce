import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

/**
 * External dependencies
 */
import { render, screen, waitFor, fireEvent } from '@testing-library/react';
import { useDispatch } from '@wordpress/data';
import { itemsStore, reportsStore, useSettings } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import Settings from '../index';
import { SCHEDULED_IMPORT_SETTING_NAME } from '../config';

// Mock dependencies.
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
vi.mock( '@wordpress/data', async () => {
	const mock = {
		...( await vi.importActual( '@wordpress/data' ) ),
		useDispatch: vi.fn(),
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
vi.mock( '../config', () => {
	const mock = {
		config: {
			woocommerce_analytics_scheduled_import: {
				name: 'woocommerce_analytics_scheduled_import',
				label: 'Updates:',
				inputType: 'radio',
				options: [
					{
						label: 'Scheduled (recommended)',
						value: 'yes',
						description: 'Updates automatically every 12 hours.',
					},
					{
						label: 'Immediately',
						value: 'no',
						description:
							'Updates as soon as new data is available.',
					},
				],
				defaultValue: 'yes',
			},
		},
		SCHEDULED_IMPORT_SETTING_NAME: 'woocommerce_analytics_scheduled_import',
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
describe( 'Settings - Import Mode Modal', () => {
	const mockUpdateSettings = vi.fn();
	const mockPersistSettings = vi.fn();
	const mockUpdateAndPersistSettings = vi.fn();
	const mockInvalidateReportResolutions = vi.fn();
	const mockInvalidateItemResolutions = vi.fn();
	const mockCreateNotice = vi.fn();
	let settingsState;
	beforeEach( () => {
		vi.clearAllMocks();
		settingsState = {
			settingsError: false,
			isRequesting: false,
			isDirty: false,
			persistSettings: mockPersistSettings,
			updateAndPersistSettings: mockUpdateAndPersistSettings,
			updateSettings: mockUpdateSettings,
			wcAdminSettings: {
				[ SCHEDULED_IMPORT_SETTING_NAME ]: 'yes',
			},
		};
		useSettings.mockImplementation( () => settingsState );
		useDispatch.mockImplementation( ( store ) => {
			if ( store === 'core/notices' ) {
				return {
					createNotice: mockCreateNotice,
				};
			}
			return {
				invalidateResolutionForStoreSelector:
					store === reportsStore
						? mockInvalidateReportResolutions
						: mockInvalidateItemResolutions,
			};
		} );
		window.wpNavMenuUrlUpdate = vi.fn();
	} );
	afterEach( () => {
		delete window.wcAdminFeatures;
		delete window.wpNavMenuUrlUpdate;
		vi.restoreAllMocks();
	} );
	it( 'renders import mode radio control', () => {
		render( <Settings createNotice={ vi.fn() } query={ {} } /> );

		// Verify radio buttons are rendered.
		expect(
			screen.getByRole( 'radio', {
				name: /scheduled/i,
			} )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'radio', {
				name: /immediately/i,
			} )
		).toBeInTheDocument();

		// Verify scheduled is selected by default.
		expect(
			screen.getByRole( 'radio', {
				name: /scheduled/i,
			} )
		).toBeChecked();
	} );
	it( 'shows modal when switching from scheduled to immediate mode', async () => {
		render( <Settings createNotice={ vi.fn() } query={ {} } /> );

		// Find the "Immediately" radio button.
		const immediatelyRadio = screen.getByRole( 'radio', {
			name: /immediately/i,
		} );

		// Click the radio button.
		fireEvent.click( immediatelyRadio );

		// Modal should appear - WordPress Modal uses dialog role.
		expect( await screen.findByRole( 'dialog' ) ).toBeInTheDocument();
		expect( screen.getByText( /are you sure\?/i ) ).toBeInTheDocument();
		expect(
			screen.getByText(
				/immediate updates to analytics can impact your performance/i
			)
		).toBeInTheDocument();
	} );
	it( 'does not update setting when modal is cancelled', async () => {
		render( <Settings createNotice={ vi.fn() } query={ {} } /> );

		// Click "Immediately" radio button.
		const immediatelyRadio = screen.getByRole( 'radio', {
			name: /immediately/i,
		} );
		fireEvent.click( immediatelyRadio );

		// Wait for modal to appear.
		expect( await screen.findByRole( 'dialog' ) ).toBeInTheDocument();

		// Click Cancel button.
		const cancelButton = screen.getByRole( 'button', {
			name: /cancel/i,
		} );
		fireEvent.click( cancelButton );

		// Modal should close and setting should not be updated.
		await waitFor( () => {
			expect( screen.queryByRole( 'dialog' ) ).not.toBeInTheDocument();
		} );
		expect( mockUpdateSettings ).not.toHaveBeenCalled();
	} );
	it( 'updates setting when modal is confirmed', async () => {
		render( <Settings createNotice={ vi.fn() } query={ {} } /> );

		// Click "Immediately" radio button.
		const immediatelyRadio = screen.getByRole( 'radio', {
			name: /immediately/i,
		} );
		fireEvent.click( immediatelyRadio );

		// Wait for modal to appear.
		expect( await screen.findByRole( 'dialog' ) ).toBeInTheDocument();

		// Click Confirm button.
		const confirmButton = screen.getByRole( 'button', {
			name: /confirm/i,
		} );
		fireEvent.click( confirmButton );

		// Setting should be updated.
		expect( mockUpdateSettings ).toHaveBeenCalledWith( 'wcAdminSettings', {
			woocommerce_analytics_scheduled_import: 'no',
		} );
	} );
	it( 'does not show modal when switching from immediate to scheduled', async () => {
		// Set initial state to immediate mode.
		useSettings.mockReturnValue( {
			settingsError: false,
			isRequesting: false,
			isDirty: false,
			persistSettings: mockPersistSettings,
			updateAndPersistSettings: vi.fn(),
			updateSettings: mockUpdateSettings,
			wcAdminSettings: {
				woocommerce_analytics_scheduled_import: 'no',
			},
		} );
		render( <Settings createNotice={ vi.fn() } query={ {} } /> );

		// Click "Scheduled" radio button.
		const scheduledRadio = screen.getByRole( 'radio', {
			name: /scheduled/i,
		} );
		fireEvent.click( scheduledRadio );

		// Modal should NOT appear.
		expect( screen.queryByRole( 'dialog' ) ).not.toBeInTheDocument();

		// Setting should be updated immediately.
		expect( mockUpdateSettings ).toHaveBeenCalledWith( 'wcAdminSettings', {
			woocommerce_analytics_scheduled_import: 'yes',
		} );
	} );
	it( 'invalidates report resolutions only after settings are saved', () => {
		const { rerender } = render(
			<Settings createNotice={ vi.fn() } query={ {} } />
		);
		fireEvent.click(
			screen.getByRole( 'button', {
				name: /save settings/i,
			} )
		);
		expect( mockPersistSettings ).toHaveBeenCalled();
		expect( mockInvalidateReportResolutions ).not.toHaveBeenCalled();
		expect( mockInvalidateItemResolutions ).not.toHaveBeenCalled();
		settingsState = {
			...settingsState,
			isRequesting: true,
		};
		rerender( <Settings createNotice={ vi.fn() } query={ {} } /> );
		expect( mockInvalidateReportResolutions ).not.toHaveBeenCalled();
		expect( mockInvalidateItemResolutions ).not.toHaveBeenCalled();
		settingsState = {
			...settingsState,
			isRequesting: false,
		};
		rerender( <Settings createNotice={ vi.fn() } query={ {} } /> );
		expect( useDispatch ).toHaveBeenCalledWith( 'core/notices' );
		expect( useDispatch ).toHaveBeenCalledWith( reportsStore );
		expect( useDispatch ).toHaveBeenCalledWith( itemsStore );
		expect( mockInvalidateReportResolutions ).toHaveBeenNthCalledWith(
			1,
			'getReportItems'
		);
		expect( mockInvalidateReportResolutions ).toHaveBeenNthCalledWith(
			2,
			'getReportStats'
		);
		expect( mockInvalidateItemResolutions ).toHaveBeenCalledWith(
			'getItems'
		);
	} );
	it( 'does not invalidate report resolutions when saving fails', () => {
		const { rerender } = render(
			<Settings createNotice={ vi.fn() } query={ {} } />
		);
		settingsState = {
			...settingsState,
			isRequesting: true,
		};
		rerender( <Settings createNotice={ vi.fn() } query={ {} } /> );
		settingsState = {
			...settingsState,
			isRequesting: false,
			settingsError: true,
		};
		rerender( <Settings createNotice={ vi.fn() } query={ {} } /> );
		expect( mockInvalidateReportResolutions ).not.toHaveBeenCalled();
		expect( mockInvalidateItemResolutions ).not.toHaveBeenCalled();
		expect( mockCreateNotice ).toHaveBeenCalledWith(
			'error',
			'There was an error saving your settings. Please try again.'
		);
	} );
	it( 'invalidates report resolutions after resetting defaults', () => {
		vi.spyOn( window, 'confirm' ).mockReturnValue( true );
		const { rerender } = render(
			<Settings createNotice={ vi.fn() } query={ {} } />
		);
		fireEvent.click(
			screen.getByRole( 'button', {
				name: /reset defaults/i,
			} )
		);
		expect( mockUpdateAndPersistSettings ).toHaveBeenCalledWith(
			'wcAdminSettings',
			{
				[ SCHEDULED_IMPORT_SETTING_NAME ]: 'yes',
			}
		);
		expect( mockInvalidateReportResolutions ).not.toHaveBeenCalled();
		expect( mockInvalidateItemResolutions ).not.toHaveBeenCalled();
		settingsState = {
			...settingsState,
			isRequesting: true,
		};
		rerender( <Settings createNotice={ vi.fn() } query={ {} } /> );
		settingsState = {
			...settingsState,
			isRequesting: false,
		};
		rerender( <Settings createNotice={ vi.fn() } query={ {} } /> );
		expect( mockInvalidateReportResolutions ).toHaveBeenCalledWith(
			'getReportItems'
		);
		expect( mockInvalidateReportResolutions ).toHaveBeenCalledWith(
			'getReportStats'
		);
		expect( mockInvalidateItemResolutions ).toHaveBeenCalledWith(
			'getItems'
		);
	} );
} );
