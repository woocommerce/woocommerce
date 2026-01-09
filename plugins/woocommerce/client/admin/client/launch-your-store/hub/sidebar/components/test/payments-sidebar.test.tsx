/**
 * External dependencies
 */
import { render, screen, waitFor } from '@testing-library/react';
import React from 'react';

// Mock problematic imports before importing the module under test.
jest.mock(
	'@wordpress/edit-site/build-module/components/sidebar-navigation-item',
	() => ( {
		__esModule: true,
		default: ( {
			children,
			className,
		}: {
			children: React.ReactNode;
			className?: string;
		} ) => (
			<div data-testid="sidebar-navigation-item" className={ className }>
				{ children }
			</div>
		),
	} )
);

jest.mock( '@woocommerce/navigation', () => ( {
	getNewPath: jest.fn(),
	navigateTo: jest.fn(),
} ) );

jest.mock( '@wordpress/hooks', () => ( {
	applyFilters: jest.fn( ( _filter, value ) => value ),
} ) );

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );

jest.mock( '@woocommerce/onboarding', () => ( {
	accessTaskReferralStorage: jest.fn( () => ( {
		setWithExpiry: jest.fn(),
	} ) ),
	createStorageUtils: jest.fn( () => ( {
		getWithExpiry: jest.fn( () => [] ),
		setWithExpiry: jest.fn(),
	} ) ),
} ) );

jest.mock( '@woocommerce/settings', () => ( {
	getAdminLink: jest.fn( ( path ) => path ),
} ) );

jest.mock( '~/settings-payments/utils', () => ( {
	recordPaymentsOnboardingEvent: jest.fn(),
} ) );

jest.mock( '~/settings-payments/constants', () => ( {
	wooPaymentsOnboardingSessionEntryLYS: 'lys',
} ) );

// Create the mock function at module scope
const mockGetPaymentsTaskFromLysTasklist = jest.fn().mockResolvedValue( {
	id: 'payments',
	title: 'Set up payments',
	additionalData: {
		wooPaymentsIsInstalled: false,
	},
} );

// Mock the tasklist helper
jest.mock( '../../tasklist', () => ( {
	getPaymentsTaskFromLysTasklist: mockGetPaymentsTaskFromLysTasklist,
} ) );

// Mock the SidebarContainer
jest.mock( '../sidebar-container', () => ( {
	SidebarContainer: ( {
		children,
	}: {
		children: React.ReactNode;
		title: React.ReactNode;
		onMobileClose: () => void;
	} ) => <div data-testid="sidebar-container">{ children }</div>,
} ) );

// Mock the SiteHub
jest.mock( '~/customize-store/site-hub', () => ( {
	SiteHub: () => <div data-testid="site-hub">SiteHub</div>,
} ) );

// Mock the StepPlaceholder
jest.mock( '../step-placeholder', () => ( {
	StepPlaceholder: ( { rows }: { rows: number } ) => (
		<div data-testid="step-placeholder">Loading { rows } rows...</div>
	),
} ) );

// Mock the icons
jest.mock( '../icons', () => ( {
	taskIcons: {
		activePaymentStep: 'active-icon',
	},
	taskCompleteIcon: 'complete-icon',
} ) );

// Mock framer-motion
jest.mock( '@wordpress/components', () => ( {
	Button: ( {
		children,
		onClick,
	}: {
		children: React.ReactNode;
		onClick?: () => void;
	} ) => (
		<button data-testid="button" onClick={ onClick }>
			{ children }
		</button>
	),
	__experimentalItemGroup: ( {
		children,
	}: {
		children: React.ReactNode;
		className: string;
	} ) => <div data-testid="item-group">{ children }</div>,
	__unstableMotion: {
		div: ( {
			children,
		}: {
			children: React.ReactNode;
			initial?: object;
			animate?: object | string;
			exit?: object;
			transition?: object;
			className?: string;
		} ) => <div data-testid="motion-div">{ children }</div>,
	},
} ) );

// Mock clsx to properly handle objects and strings
jest.mock( 'clsx', () => ( ...args: unknown[] ) => {
	const classes: string[] = [];
	for ( const arg of args ) {
		if ( typeof arg === 'string' ) {
			classes.push( arg );
		} else if ( typeof arg === 'object' && arg !== null ) {
			for ( const [ key, value ] of Object.entries( arg ) ) {
				if ( value ) {
					classes.push( key );
				}
			}
		}
	}
	return classes.join( ' ' );
} );

// Mock values for context
const mockSetUpPaymentsContext = {
	isWooPaymentsActive: false,
	isWooPaymentsInstalled: false,
	wooPaymentsRecentlyActivated: false,
	setWooPaymentsRecentlyActivated: jest.fn(),
};

const mockOnboardingContext: {
	steps: Array< { id: string; label: string; status: string } >;
	currentStep: { id: string; label: string; status: string } | null;
	justCompletedStepId: string | null;
	isLoading: boolean;
	error: unknown;
	setCurrentStep: jest.Mock;
	goToStep: jest.Mock;
	goToNextStep: jest.Mock;
	goToPreviousStep: jest.Mock;
	completeStep: jest.Mock;
	setError: jest.Mock;
	clearError: jest.Mock;
} = {
	steps: [],
	currentStep: null,
	justCompletedStepId: null,
	isLoading: false,
	error: null,
	setCurrentStep: jest.fn(),
	goToStep: jest.fn(),
	goToNextStep: jest.fn(),
	goToPreviousStep: jest.fn(),
	completeStep: jest.fn(),
	setError: jest.fn(),
	clearError: jest.fn(),
};

// Mock the context hooks
jest.mock( '~/launch-your-store/data/setup-payments-context', () => ( {
	useSetUpPaymentsContext: () => mockSetUpPaymentsContext,
} ) );

jest.mock(
	'~/settings-payments/onboarding/providers/woopayments/data/onboarding-context',
	() => ( {
		useOnboardingContext: () => mockOnboardingContext,
	} )
);

/**
 * Internal dependencies
 */
import { PaymentsSidebar } from '../payments-sidebar';
import type { SidebarComponentProps } from '../../xstate';

// Mock props for the component - using partial type and casting
// since we're mocking most dependencies
const mockProps = {
	sendEventToSidebar: jest.fn(),
	sendEventToMainContent: jest.fn(),
	onMobileClose: jest.fn(),
	className: 'test-class',
	context: {
		externalUrl: null,
		mainContentMachineRef: {} as never, // Mock ref
		testOrderCount: 0,
		tasklist: {
			tasks: [],
		},
	},
} as unknown as SidebarComponentProps;

describe( 'PaymentsSidebar', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		// Reset context mock values.
		mockSetUpPaymentsContext.isWooPaymentsActive = false;
		mockSetUpPaymentsContext.isWooPaymentsInstalled = false;
		mockSetUpPaymentsContext.wooPaymentsRecentlyActivated = false;
		mockOnboardingContext.steps = [];
		mockOnboardingContext.currentStep = null;
		mockOnboardingContext.justCompletedStepId = null;
		mockOnboardingContext.isLoading = false;
		// Reset the tasklist mock to its default resolved value.
		mockGetPaymentsTaskFromLysTasklist.mockResolvedValue( {
			id: 'payments',
			title: 'Set up payments',
			additionalData: {
				wooPaymentsIsInstalled: false,
			},
		} );
	} );

	describe( 'InstallWooPaymentsStep visibility', () => {
		it( 'renders InstallWooPaymentsStep with isStepComplete=false when WooPayments is NOT active', () => {
			mockSetUpPaymentsContext.isWooPaymentsActive = false;

			render( <PaymentsSidebar { ...mockProps } /> );

			// Should show the Install step
			const sidebarItems = screen.getAllByTestId(
				'sidebar-navigation-item'
			);
			expect( sidebarItems ).toHaveLength( 1 );

			// The item should have the install-woopayments class but NOT is-complete.
			const installStep = sidebarItems[ 0 ];
			expect( installStep ).toHaveClass( 'install-woopayments' );
			expect( installStep ).not.toHaveClass( 'is-complete' );
		} );

		it( 'renders InstallWooPaymentsStep with isStepComplete=true when WooPayments IS active and NOT loading', () => {
			mockSetUpPaymentsContext.isWooPaymentsActive = true;
			mockOnboardingContext.isLoading = false;

			render( <PaymentsSidebar { ...mockProps } /> );

			// Should show the Install step as completed
			const sidebarItems = screen.getAllByTestId(
				'sidebar-navigation-item'
			);
			expect( sidebarItems.length ).toBeGreaterThanOrEqual( 1 );

			// The first item should be the install step with is-complete class
			const installStep = sidebarItems[ 0 ];
			expect( installStep ).toHaveClass( 'install-woopayments' );
			expect( installStep ).toHaveClass( 'is-complete' );
		} );

		it( 'shows loading placeholder when WooPayments IS active and IS loading', () => {
			mockSetUpPaymentsContext.isWooPaymentsActive = true;
			mockOnboardingContext.isLoading = true;

			render( <PaymentsSidebar { ...mockProps } /> );

			// Should show the placeholder
			expect(
				screen.getByTestId( 'step-placeholder' )
			).toBeInTheDocument();

			// Should NOT show the Install step
			expect(
				screen.queryByTestId( 'sidebar-navigation-item' )
			).not.toBeInTheDocument();
		} );

		it( 'displays "Install WooPayments" text when WooPayments is NOT installed', async () => {
			mockSetUpPaymentsContext.isWooPaymentsActive = false;

			// Mock the task to indicate WooPayments is not installed.
			mockGetPaymentsTaskFromLysTasklist.mockResolvedValue( {
				id: 'payments',
				title: 'Set up payments',
				additionalData: {
					wooPaymentsIsInstalled: false,
				},
			} );

			render( <PaymentsSidebar { ...mockProps } /> );

			// Wait for the async task to resolve and state to update.
			await waitFor( () => {
				const sidebarItems = screen.getAllByTestId(
					'sidebar-navigation-item'
				);
				expect( sidebarItems[ 0 ] ).toHaveTextContent(
					/Install.*WooPayments/i
				);
			} );
		} );

		it( 'displays "Enable WooPayments" text when WooPayments IS installed but not active', async () => {
			mockSetUpPaymentsContext.isWooPaymentsActive = false;
			mockSetUpPaymentsContext.isWooPaymentsInstalled = true;

			// Mock the task to indicate WooPayments is installed.
			mockGetPaymentsTaskFromLysTasklist.mockResolvedValue( {
				id: 'payments',
				title: 'Set up payments',
				additionalData: {
					wooPaymentsIsInstalled: true,
				},
			} );

			render( <PaymentsSidebar { ...mockProps } /> );

			// Wait for the async task to resolve and state to update.
			await waitFor( () => {
				const sidebarItems = screen.getAllByTestId(
					'sidebar-navigation-item'
				);
				expect( sidebarItems[ 0 ] ).toHaveTextContent(
					/Enable.*WooPayments/i
				);
			} );
		} );

		it( 'renders additional onboarding steps when WooPayments is active', () => {
			mockSetUpPaymentsContext.isWooPaymentsActive = true;
			mockOnboardingContext.isLoading = false;
			mockOnboardingContext.steps = [
				{
					id: 'connect',
					label: 'Connect with WordPress.com',
					status: 'pending',
				},
				{
					id: 'payment_methods',
					label: 'Choose your payment methods',
					status: 'pending',
				},
			];

			render( <PaymentsSidebar { ...mockProps } /> );

			// Should show Install step + 2 onboarding steps = 3 items
			const sidebarItems = screen.getAllByTestId(
				'sidebar-navigation-item'
			);
			expect( sidebarItems ).toHaveLength( 3 );

			// First should be Install step (completed)
			expect( sidebarItems[ 0 ] ).toHaveClass( 'install-woopayments' );
			expect( sidebarItems[ 0 ] ).toHaveClass( 'is-complete' );

			// Other steps should be present
			expect( sidebarItems[ 1 ] ).toHaveTextContent(
				'Connect with WordPress.com'
			);
			expect( sidebarItems[ 2 ] ).toHaveTextContent(
				'Choose your payment methods'
			);
		} );
	} );
} );
