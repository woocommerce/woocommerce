import { beforeEach, describe, expect, it, vi, type Mock } from 'vitest';
const {
	mockGetPaymentsTaskFromLysTasklist,
	mockSetUpPaymentsContext,
	mockOnboardingContext,
} = vi.hoisted( () => {
	const mockGetPaymentsTaskFromLysTasklist = vi.fn().mockResolvedValue( {
		id: 'payments',
		title: 'Set up payments',
		additionalData: {
			wooPaymentsIsInstalled: false,
		},
	} );
	const mockSetUpPaymentsContext = {
		isWooPaymentsActive: false,
		isWooPaymentsInstalled: false,
		wooPaymentsRecentlyActivated: false,
		setWooPaymentsRecentlyActivated: vi.fn(),
	};
	const mockOnboardingContext = {
		steps: [],
		currentStep: null,
		justCompletedStepId: null,
		isLoading: false,
		error: null,
		setCurrentStep: vi.fn(),
		goToStep: vi.fn(),
		goToNextStep: vi.fn(),
		goToPreviousStep: vi.fn(),
		completeStep: vi.fn(),
		setError: vi.fn(),
		clearError: vi.fn(),
	};
	return {
		mockGetPaymentsTaskFromLysTasklist,
		mockSetUpPaymentsContext,
		mockOnboardingContext,
	};
} );

/**
 * External dependencies
 */
import { render, screen, waitFor, act } from '@testing-library/react';
import React from 'react';

// Mock problematic imports before importing the module under test.
vi.mock(
	'@wordpress/edit-site/build-module/components/sidebar-navigation-item',
	() => {
		const mock = {
			__esModule: true,
			default: ( {
				children,
				className,
			}: {
				children: React.ReactNode;
				className?: string;
			} ) => (
				<div
					data-testid="sidebar-navigation-item"
					className={ className }
				>
					{ children }
				</div>
			),
		};
		return Object.defineProperties(
			{
				default: mock,
			},
			Object.getOwnPropertyDescriptors( mock )
		);
	}
);
vi.mock( '@woocommerce/navigation', () => {
	const mock = {
		getNewPath: vi.fn(),
		navigateTo: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@wordpress/hooks', () => {
	const mock = {
		applyFilters: vi.fn( ( _filter, value ) => value ),
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
vi.mock( '@woocommerce/onboarding', () => {
	const mock = {
		accessTaskReferralStorage: vi.fn( () => ( {
			setWithExpiry: vi.fn(),
		} ) ),
		createStorageUtils: vi.fn( () => ( {
			getWithExpiry: vi.fn( () => [] ),
			setWithExpiry: vi.fn(),
		} ) ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@woocommerce/settings', () => {
	const mock = {
		getAdminLink: vi.fn( ( path ) => path ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '~/settings-payments/utils', () => {
	const mock = {
		recordPaymentsOnboardingEvent: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '~/settings-payments/constants', () => {
	const mock = {
		wooPaymentsOnboardingSessionEntryLYS: 'lys',
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );

// Create the mock function at module scope

// Mock the tasklist helper
vi.mock( '../../tasklist', () => {
	const mock = {
		getPaymentsTaskFromLysTasklist: mockGetPaymentsTaskFromLysTasklist,
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );

// Mock the SidebarContainer
vi.mock( '../sidebar-container', () => {
	const mock = {
		SidebarContainer: ( {
			children,
		}: {
			children: React.ReactNode;
			title: React.ReactNode;
			onMobileClose: () => void;
		} ) => <div data-testid="sidebar-container">{ children }</div>,
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );

// Mock the SiteHub
vi.mock( '~/customize-store/site-hub', () => {
	const mock = {
		SiteHub: () => <div data-testid="site-hub">SiteHub</div>,
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );

// Mock the StepPlaceholder
vi.mock( '../step-placeholder', () => {
	const mock = {
		StepPlaceholder: ( { rows }: { rows: number } ) => (
			<div data-testid="step-placeholder">Loading { rows } rows...</div>
		),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );

// Mock the icons
vi.mock( '../icons', () => {
	const mock = {
		taskIcons: {
			activePaymentStep: 'active-icon',
		},
		taskCompleteIcon: 'complete-icon',
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );

// Mock framer-motion
vi.mock( '@wordpress/components', () => {
	const mock = {
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
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );

// Mock clsx to properly handle objects and strings
vi.mock( 'clsx', () => {
	const mock = ( ...args: unknown[] ) => {
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
	};
	return {
		default: mock,
		...mock,
	};
} );

// Mock values for context

// Mock the context hooks
vi.mock( '~/launch-your-store/data/setup-payments-context', () => {
	const mock = {
		useSetUpPaymentsContext: () => mockSetUpPaymentsContext,
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock(
	'~/settings-payments/onboarding/providers/woopayments/data/onboarding-context',
	() => {
		const mock = {
			useOnboardingContext: () => mockOnboardingContext,
		};
		return Object.defineProperties(
			{
				default: mock,
			},
			Object.getOwnPropertyDescriptors( mock )
		);
	}
);

/**
 * Internal dependencies
 */
import { PaymentsSidebar } from '../payments-sidebar';
import type { SidebarComponentProps } from '../../xstate';

// Mock props for the component - using partial type and casting
// since we're mocking most dependencies
const mockProps = {
	sendEventToSidebar: vi.fn(),
	sendEventToMainContent: vi.fn(),
	onMobileClose: vi.fn(),
	className: 'test-class',
	context: {
		externalUrl: null,
		mainContentMachineRef: {} as never,
		// Mock ref
		testOrderCount: 0,
		tasklist: {
			tasks: [],
		},
	},
} as unknown as SidebarComponentProps;
describe( 'PaymentsSidebar', () => {
	beforeEach( () => {
		vi.clearAllMocks();
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
		it( 'renders InstallWooPaymentsStep with isStepComplete=false when WooPayments is NOT active', async () => {
			mockSetUpPaymentsContext.isWooPaymentsActive = false;
			await act( async () => {
				render( <PaymentsSidebar { ...mockProps } /> );

				// Should show the Install step
			} ); // Should show the Install step
			const sidebarItems = screen.getAllByTestId(
				'sidebar-navigation-item'
			);
			expect( sidebarItems ).toHaveLength( 1 );

			// The item should have the install-woopayments class but NOT is-complete.
			const installStep = sidebarItems[ 0 ];
			expect( installStep ).toHaveClass( 'install-woopayments' );
			expect( installStep ).not.toHaveClass( 'is-complete' );
		} );
		it( 'renders InstallWooPaymentsStep with isStepComplete=true when WooPayments IS active and NOT loading', async () => {
			mockSetUpPaymentsContext.isWooPaymentsActive = true;
			mockOnboardingContext.isLoading = false;
			await act( async () => {
				render( <PaymentsSidebar { ...mockProps } /> );

				// Should show the Install step as completed
			} ); // Should show the Install step as completed
			const sidebarItems = screen.getAllByTestId(
				'sidebar-navigation-item'
			);
			expect( sidebarItems.length ).toBeGreaterThanOrEqual( 1 );

			// The first item should be the install step with is-complete class
			const installStep = sidebarItems[ 0 ];
			expect( installStep ).toHaveClass( 'install-woopayments' );
			expect( installStep ).toHaveClass( 'is-complete' );
		} );
		it( 'shows loading placeholder when WooPayments IS active and IS loading', async () => {
			mockSetUpPaymentsContext.isWooPaymentsActive = true;
			mockOnboardingContext.isLoading = true;
			await act( async () => {
				render( <PaymentsSidebar { ...mockProps } /> );

				// Should show the placeholder
			} ); // Should show the placeholder
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
			await act( async () => {
				render( <PaymentsSidebar { ...mockProps } /> );

				// Wait for the async task to resolve and state to update.
			} ); // Wait for the async task to resolve and state to update.
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
			await act( async () => {
				render( <PaymentsSidebar { ...mockProps } /> );

				// Wait for the async task to resolve and state to update.
			} ); // Wait for the async task to resolve and state to update.
			await waitFor( () => {
				const sidebarItems = screen.getAllByTestId(
					'sidebar-navigation-item'
				);
				expect( sidebarItems[ 0 ] ).toHaveTextContent(
					/Enable.*WooPayments/i
				);
			} );
		} );
		it( 'renders additional onboarding steps when WooPayments is active', async () => {
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
			await act( async () => {
				render( <PaymentsSidebar { ...mockProps } /> );

				// Should show Install step + 2 onboarding steps = 3 items
			} ); // Should show Install step + 2 onboarding steps = 3 items
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
