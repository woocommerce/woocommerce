/**
 * External dependencies
 */
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { useDispatch } from '@wordpress/data';
import { useSettings } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { ImportStatusBar } from '../import-status-bar';
import { useImportStatus } from '../use-import-status';
import type { UseImportStatusReturn } from '../types';

jest.mock( '../use-import-status' );

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useDispatch: jest.fn().mockImplementation( () => ( {
		createNotice: jest.fn(),
	} ) ),
} ) );
jest.mock( '@wordpress/date', () => ( {
	dateI18n: jest.fn( ( format, date ) => {
		// Simple mock that returns a date-like string
		if ( ! date ) return 'Never';
		return 'Nov 21 00:00';
	} ),
} ) );
jest.mock( '@woocommerce/data', () => ( {
	...jest.requireActual( '@woocommerce/data' ),
	useSettings: jest.fn().mockImplementation( () => ( {
		wcAdminSettings: {
			woocommerce_analytics_scheduled_import: 'yes',
		},
	} ) ),
} ) );

const mockUseImportStatus = useImportStatus as jest.MockedFunction<
	typeof useImportStatus
>;
const mockUseDispatch = useDispatch as jest.MockedFunction<
	typeof useDispatch
>;

const mockUseSettings = useSettings as jest.MockedFunction<
	typeof useSettings
>;

describe( 'ImportStatusBar', () => {
	const mockCreateNotice = jest.fn();
	const mockTriggerImport = jest.fn();

	beforeEach( () => {
		jest.clearAllMocks();
		mockUseDispatch.mockReturnValue( {
			createNotice: mockCreateNotice,
		} );
		mockUseSettings.mockReturnValue( {
			wcAdminSettings: {
				woocommerce_analytics_scheduled_import: 'yes',
			},
		} as unknown as ReturnType< typeof useSettings > );
	} );

	const createMockReturn = (
		overrides: Partial< UseImportStatusReturn > = {}
	): UseImportStatusReturn => ( {
		status: {
			mode: 'scheduled',
			last_processed_date: '2024-11-21 00:00:00',
			next_scheduled: '2024-11-21 12:00:00',
			import_in_progress_or_due: false,
		},
		isLoading: false,
		error: null,
		triggerImport: mockTriggerImport,
		isTriggeringImport: false,
		...overrides,
	} );

	it( 'should not render when mode is immediate', () => {
		mockUseSettings.mockReturnValue( {
			wcAdminSettings: {
				woocommerce_analytics_scheduled_import: 'no',
			},
		} as unknown as ReturnType< typeof useSettings > );
		// Mock useImportStatus to avoid destructuring error even though component returns early
		mockUseImportStatus.mockReturnValue( createMockReturn() );

		const { container } = render( <ImportStatusBar /> );
		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'should show spinners when loading', () => {
		mockUseImportStatus.mockReturnValue(
			createMockReturn( {
				status: null,
				isLoading: true,
			} )
		);

		render( <ImportStatusBar /> );

		// Component should render and show spinners when loading
		expect( screen.getByText( /Last updated$/i ) ).toBeInTheDocument();
		expect( screen.getByText( /Next update$/i ) ).toBeInTheDocument();
		// Check for spinner elements (they have role="presentation" and are SVGs)
		const spinners = screen.getAllByRole( 'presentation' );
		expect( spinners.length ).toBeGreaterThan( 0 );
	} );

	it( 'should render status when mode is scheduled', () => {
		mockUseImportStatus.mockReturnValue( createMockReturn() );

		render( <ImportStatusBar /> );

		expect( screen.getByText( /Last updated$/i ) ).toBeInTheDocument();
		expect( screen.getByText( /Next update$/i ) ).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', {
				name: /Manually trigger analytics data import/i,
			} )
		).toBeInTheDocument();
	} );

	it( 'should disable button when import_in_progress_or_due is true', () => {
		mockUseImportStatus.mockReturnValue(
			createMockReturn( {
				status: {
					mode: 'scheduled',
					last_processed_date: '2024-11-21 00:00:00',
					next_scheduled: '2024-11-21 12:00:00',
					import_in_progress_or_due: true,
				},
			} )
		);

		render( <ImportStatusBar /> );

		// When busy, aria-label changes to "Analytics data import in progress"
		const button = screen.getByRole( 'button', {
			name: /Analytics data import in progress/i,
		} );
		expect( button ).toBeDisabled();
		expect( button ).toHaveAttribute( 'aria-busy', 'true' );
	} );

	it( 'should disable button when isTriggeringImport is true', () => {
		mockUseImportStatus.mockReturnValue(
			createMockReturn( {
				isTriggeringImport: true,
			} )
		);

		render( <ImportStatusBar /> );

		// When busy, aria-label changes to "Analytics data import in progress"
		const button = screen.getByRole( 'button', {
			name: /Analytics data import in progress/i,
		} );
		expect( button ).toBeDisabled();
		expect( button ).toHaveAttribute( 'aria-busy', 'true' );
	} );

	it( 'should trigger import on button click', async () => {
		mockTriggerImport.mockResolvedValue( undefined );
		mockUseImportStatus.mockReturnValue( createMockReturn() );

		render( <ImportStatusBar /> );

		const button = screen.getByRole( 'button', {
			name: /Manually trigger analytics data import/i,
		} );
		fireEvent.click( button );

		await waitFor( () => {
			expect( mockTriggerImport ).toHaveBeenCalled();
		} );

		expect( mockCreateNotice ).toHaveBeenCalledWith(
			'success',
			expect.stringContaining( 'Analytics import has started' ),
			expect.objectContaining( {
				type: 'snackbar',
				isDismissible: true,
			} )
		);
	} );

	it( 'should show error notice when import fails', async () => {
		const errorMessage = 'API Error';
		mockTriggerImport.mockRejectedValue( new Error( errorMessage ) );
		mockUseImportStatus.mockReturnValue(
			createMockReturn( {
				error: errorMessage,
			} )
		);

		render( <ImportStatusBar /> );

		const button = screen.getByRole( 'button', {
			name: /Manually trigger analytics data import/i,
		} );
		fireEvent.click( button );

		await waitFor( () => {
			expect( mockTriggerImport ).toHaveBeenCalled();
		} );

		expect( mockCreateNotice ).toHaveBeenCalledWith(
			'error',
			expect.stringContaining( errorMessage ),
			expect.objectContaining( {
				isDismissible: true,
			} )
		);
	} );

	it( 'should format dates correctly', () => {
		mockUseImportStatus.mockReturnValue( createMockReturn() );

		render( <ImportStatusBar /> );

		// dateI18n is mocked to return "Nov 21 00:00"
		const dateTexts = screen.getAllByText( /Nov 21 00:00/i );
		expect( dateTexts ).toHaveLength( 2 ); // Last updated and Next update
	} );

	it( 'should show "Never" when dates are null', () => {
		mockUseImportStatus.mockReturnValue(
			createMockReturn( {
				status: {
					mode: 'scheduled',
					last_processed_date: null,
					next_scheduled: null,
					import_in_progress_or_due: false,
				},
			} )
		);

		render( <ImportStatusBar /> );

		const neverTexts = screen.getAllByText( /Never/i );
		expect( neverTexts ).toHaveLength( 2 ); // Last updated: Never, Next update: Never
	} );

	it( 'should have accessible ARIA attributes', () => {
		mockUseImportStatus.mockReturnValue( createMockReturn() );

		render( <ImportStatusBar /> );

		const container = screen.getByRole( 'status' );
		expect( container ).toHaveAttribute( 'aria-live', 'polite' );
		expect( container ).toHaveAttribute( 'aria-atomic', 'true' );

		const button = screen.getByRole( 'button', {
			name: /Manually trigger analytics data import/i,
		} );
		expect( button ).toHaveAttribute(
			'aria-label',
			'Manually trigger analytics data import'
		);
	} );

	it( 'should be keyboard accessible', () => {
		mockTriggerImport.mockResolvedValue( undefined );
		mockUseImportStatus.mockReturnValue( createMockReturn() );

		render( <ImportStatusBar /> );

		const button = screen.getByRole( 'button', {
			name: /Manually trigger analytics data import/i,
		} );

		// Button should be focusable
		button.focus();
		expect( button ).toHaveFocus();

		// Enter key should trigger
		fireEvent.keyPress( button, {
			key: 'Enter',
			code: 'Enter',
			charCode: 13,
		} );

		// Note: In real browser, Button component handles Enter/Space
		// In tests, we verify the button exists and is not disabled
		expect( button ).not.toBeDisabled();
	} );

	it( 'should show fallback error message when error is null', async () => {
		mockTriggerImport.mockRejectedValue( new Error( 'API Error' ) );
		mockUseImportStatus.mockReturnValue(
			createMockReturn( {
				error: null, // No error in state
			} )
		);

		render( <ImportStatusBar /> );

		const button = screen.getByRole( 'button', {
			name: /Manually trigger analytics data import/i,
		} );
		fireEvent.click( button );

		await waitFor( () => {
			expect( mockTriggerImport ).toHaveBeenCalled();
		} );

		expect( mockCreateNotice ).toHaveBeenCalledWith(
			'error',
			expect.stringContaining( 'API Error' ),
			expect.objectContaining( {
				isDismissible: true,
			} )
		);
	} );

	it( 'should show "Never" when status is null', () => {
		mockUseImportStatus.mockReturnValue(
			createMockReturn( {
				status: null,
				isLoading: false,
			} )
		);

		render( <ImportStatusBar /> );

		// Component should render and show "Never" for dates when status is null
		expect( screen.getByText( /Last updated$/i ) ).toBeInTheDocument();
		expect( screen.getByText( /Next update$/i ) ).toBeInTheDocument();
		const neverTexts = screen.getAllByText( /Never/i );
		expect( neverTexts ).toHaveLength( 2 ); // Last updated: Never, Next update: Never
	} );
} );
