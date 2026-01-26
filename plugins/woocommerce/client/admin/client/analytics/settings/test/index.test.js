/**
 * External dependencies
 */
import { render, screen, waitFor, fireEvent } from '@testing-library/react';
import { useSettings } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import Settings from '../index';
import { SCHEDULED_IMPORT_SETTING_NAME } from '../config';

// Mock dependencies.
jest.mock( '@woocommerce/data', () => ( {
	useSettings: jest.fn(),
} ) );

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );

// Enable the feature flag before mocking config.
window.wcAdminFeatures = {
	'analytics-scheduled-import': true,
};

jest.mock( '../config', () => ( {
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
					description: 'Updates as soon as new data is available.',
				},
			],
			defaultValue: 'yes',
		},
	},
	SCHEDULED_IMPORT_SETTING_NAME: 'woocommerce_analytics_scheduled_import',
} ) );

jest.mock( '../historical-data', () => ( {
	__esModule: true,
	default: () => <div>Historical Data</div>,
} ) );

describe( 'Settings - Import Mode Modal', () => {
	const mockUpdateSettings = jest.fn();
	const mockPersistSettings = jest.fn();

	beforeEach( () => {
		jest.clearAllMocks();

		useSettings.mockReturnValue( {
			settingsError: false,
			isRequesting: false,
			isDirty: false,
			persistSettings: mockPersistSettings,
			updateAndPersistSettings: jest.fn(),
			updateSettings: mockUpdateSettings,
			wcAdminSettings: {
				[ SCHEDULED_IMPORT_SETTING_NAME ]: 'yes',
			},
		} );

		// Mock window.wcAdminFeatures.
		window.wcAdminFeatures = {
			'analytics-scheduled-import': true,
		};
	} );

	afterEach( () => {
		delete window.wcAdminFeatures;
	} );

	it( 'renders import mode radio control', () => {
		render( <Settings createNotice={ jest.fn() } query={ {} } /> );

		// Verify radio buttons are rendered.
		expect(
			screen.getByRole( 'radio', { name: /scheduled/i } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'radio', { name: /immediately/i } )
		).toBeInTheDocument();

		// Verify scheduled is selected by default.
		expect(
			screen.getByRole( 'radio', { name: /scheduled/i } )
		).toBeChecked();
	} );

	it( 'shows modal when switching from scheduled to immediate mode', async () => {
		render( <Settings createNotice={ jest.fn() } query={ {} } /> );

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
		render( <Settings createNotice={ jest.fn() } query={ {} } /> );

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
		render( <Settings createNotice={ jest.fn() } query={ {} } /> );

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
			updateAndPersistSettings: jest.fn(),
			updateSettings: mockUpdateSettings,
			wcAdminSettings: {
				woocommerce_analytics_scheduled_import: 'no',
			},
		} );

		render( <Settings createNotice={ jest.fn() } query={ {} } /> );

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
} );
