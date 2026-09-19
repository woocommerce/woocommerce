/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import React from 'react';

/**
 * Internal dependencies
 */
import type { OnboardingError } from '~/settings-payments/onboarding/types';
import { useOnboardingContext } from '../../../data/onboarding-context';
import { BusinessVerificationStep } from '../index';

// Mock all child components and dependencies.
jest.mock( '../../../data/onboarding-context', () => ( {
	useOnboardingContext: jest.fn(),
} ) );

jest.mock( '../../../components/header', () => ( {
	__esModule: true,
	// eslint-disable-next-line @typescript-eslint/no-unused-vars -- onClose is required by the component interface.
	default: ( { onClose }: { onClose: () => void } ) => (
		<div data-testid="step-header">Header</div>
	),
} ) );

jest.mock( '../data/business-verification-context', () => ( {
	BusinessVerificationContextProvider: ( {
		children,
		// eslint-disable-next-line @typescript-eslint/no-unused-vars -- initialData is required by the component interface.
		initialData,
	}: {
		children: React.ReactNode;
		initialData: Record< string, unknown >;
	} ) => <div data-testid="bv-context-provider">{ children }</div>,
} ) );

jest.mock( '../components/form', () => ( {
	OnboardingForm: ( { children }: { children: React.ReactNode } ) => (
		<div data-testid="onboarding-form">{ children }</div>
	),
} ) );

jest.mock( '../sections/business-details', () => ( {
	__esModule: true,
	default: () => <div data-testid="business-details">Business Details</div>,
} ) );

jest.mock( '../sections/embedded-kyc', () => ( {
	__esModule: true,
	default: () => <div data-testid="embedded-kyc">Embedded KYC</div>,
} ) );

jest.mock( '../sections/activate-payments', () => ( {
	__esModule: true,
	default: () => <div data-testid="activate-payments">Activate Payments</div>,
} ) );

jest.mock( '../components/stepper', () => ( {
	Stepper: ( { children }: { children: React.ReactNode } ) => (
		<div data-testid="stepper">{ children }</div>
	),
} ) );

jest.mock( '../components/step', () => ( {
	__esModule: true,
	default: ( {
		children,
		name,
	}: {
		children: React.ReactNode;
		name: string;
	} ) => <div data-testid={ `step-${ name }` }>{ children }</div>,
} ) );

jest.mock( '../utils', () => ( {
	getMccFromIndustry: jest.fn( () => 'mcc_code' ),
	getComingSoonShareKey: jest.fn( () => '' ),
} ) );

jest.mock( '~/settings-payments/utils', () => ( {
	recordPaymentsOnboardingEvent: jest.fn(),
} ) );

const mockUseOnboardingContext = useOnboardingContext as jest.Mock;

// Helper to create a mock context with configurable errors.
const createMockContext = (
	errors: OnboardingError[] = [],
	overrides: Record< string, unknown > = {}
) => ( {
	currentStep: {
		id: 'business_verification',
		status: 'not_started',
		context: {
			fields: {
				mccs_display_tree: [],
				location: 'US',
			},
			self_assessment: {},
			sub_steps: {
				business: { status: 'not_started' },
				embedded: { status: 'not_started' },
			},
			has_test_account: false,
			has_sandbox_account: false,
		},
		errors,
		...overrides,
	},
	closeModal: jest.fn(),
	sessionEntryPoint: 'settings',
} );

describe( 'BusinessVerificationStep', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		// Mock window.wcSettings.
		Object.defineProperty( window, 'wcSettings', {
			value: {
				siteTitle: 'Test Store',
				homeUrl: 'https://example.com',
			},
			writable: true,
		} );
	} );

	describe( 'Error Notice Rendering', () => {
		it( 'does not render error notice when there are no errors', () => {
			mockUseOnboardingContext.mockReturnValue( createMockContext( [] ) );

			const { container } = render( <BusinessVerificationStep /> );

			const notice = container.querySelector( '.is-error' );
			expect( notice ).not.toBeInTheDocument();
		} );

		it( 'renders error notice when errors exist', () => {
			const errors: OnboardingError[] = [
				{ message: 'Test error', code: 'test_error' },
			];
			mockUseOnboardingContext.mockReturnValue(
				createMockContext( errors )
			);

			const { container } = render( <BusinessVerificationStep /> );

			const notice = container.querySelector( '.is-error' );
			expect( notice ).toBeInTheDocument();
		} );

		it( 'renders a single error message', () => {
			const errors: OnboardingError[] = [
				{ message: 'Single error message', code: 'single_error' },
			];
			mockUseOnboardingContext.mockReturnValue(
				createMockContext( errors )
			);

			const { container } = render( <BusinessVerificationStep /> );

			// Query within the notice content to avoid matching a11y-speak region.
			const noticeContent = container.querySelector(
				'.components-notice__content'
			);
			expect( noticeContent ).toBeInTheDocument();
			expect( noticeContent?.textContent ).toContain(
				'Single error message'
			);
		} );

		it( 'renders multiple error messages when count is within limit', () => {
			const errors: OnboardingError[] = [
				{ message: 'First error', code: 'error_1' },
				{ message: 'Second error', code: 'error_2' },
				{ message: 'Third error', code: 'error_3' },
			];
			mockUseOnboardingContext.mockReturnValue(
				createMockContext( errors )
			);

			render( <BusinessVerificationStep /> );

			expect( screen.getByText( 'First error' ) ).toBeInTheDocument();
			expect( screen.getByText( 'Second error' ) ).toBeInTheDocument();
			expect( screen.getByText( 'Third error' ) ).toBeInTheDocument();
		} );

		it( 'renders summary when error count exceeds limit', () => {
			const errors: OnboardingError[] = [
				{ message: 'Error 1', code: 'error_1' },
				{ message: 'Error 2', code: 'error_2' },
				{ message: 'Error 3', code: 'error_3' },
				{ message: 'Error 4', code: 'error_4' },
			];
			mockUseOnboardingContext.mockReturnValue(
				createMockContext( errors )
			);

			render( <BusinessVerificationStep /> );

			// Should show summary instead of individual errors.
			expect(
				screen.getByText( '4 errors occurred during setup.' )
			).toBeInTheDocument();
			expect(
				screen.getByText( 'Something went wrong. Please try again.' )
			).toBeInTheDocument();
			// Individual errors should not be shown.
			expect( screen.queryByText( 'Error 1' ) ).not.toBeInTheDocument();
		} );

		it( 'renders fallback message when error message is empty', () => {
			// Backend may return empty message string when error details unavailable.
			const errors: OnboardingError[] = [
				{ message: '', code: 'no_message_error' },
			];
			mockUseOnboardingContext.mockReturnValue(
				createMockContext( errors )
			);

			const { container } = render( <BusinessVerificationStep /> );

			// There should be an error notice with the fallback message.
			const notice = container.querySelector( '.is-error' );
			expect( notice ).toBeInTheDocument();
			// Query within notice content to avoid a11y-speak region.
			const noticeContent = container.querySelector(
				'.components-notice__content'
			);
			expect( noticeContent?.textContent ).toContain(
				'Something went wrong. Please try again.'
			);
		} );

		it( 'renders fallback message when error message is whitespace only', () => {
			const errors: OnboardingError[] = [
				{ message: '   ', code: 'whitespace_message' },
			];
			mockUseOnboardingContext.mockReturnValue(
				createMockContext( errors )
			);

			const { container } = render( <BusinessVerificationStep /> );

			const notice = container.querySelector( '.is-error' );
			expect( notice ).toBeInTheDocument();
			// Query within notice content to avoid a11y-speak region.
			const noticeContent = container.querySelector(
				'.components-notice__content'
			);
			expect( noticeContent?.textContent ).toContain(
				'Something went wrong. Please try again.'
			);
		} );

		it( 'trims whitespace from error messages', () => {
			const errors: OnboardingError[] = [
				{
					message: '  Unique padded message  ',
					code: 'padded_message',
				},
			];
			mockUseOnboardingContext.mockReturnValue(
				createMockContext( errors )
			);

			const { container } = render( <BusinessVerificationStep /> );

			// Query within notice content to avoid a11y-speak region.
			const noticeContent = container.querySelector(
				'.components-notice__content'
			);
			expect( noticeContent?.textContent ).toContain(
				'Unique padded message'
			);
		} );

		it( 'renders error messages in paragraph elements', () => {
			const errors: OnboardingError[] = [
				{ message: 'Error with code', code: 'unique_code' },
			];
			mockUseOnboardingContext.mockReturnValue(
				createMockContext( errors )
			);

			const { container } = render( <BusinessVerificationStep /> );

			// The paragraph should be rendered within the notice content.
			const noticeContent = container.querySelector(
				'.components-notice__content'
			);
			expect( noticeContent ).toBeInTheDocument();
			const paragraphs = noticeContent?.querySelectorAll( 'p' );
			expect( paragraphs?.length ).toBe( 1 );
			expect( paragraphs?.[ 0 ].textContent ).toBe( 'Error with code' );
		} );

		it( 'handles errors array with undefined currentStep gracefully', () => {
			mockUseOnboardingContext.mockReturnValue( {
				currentStep: undefined,
				closeModal: jest.fn(),
				sessionEntryPoint: 'settings',
			} );

			const { container } = render( <BusinessVerificationStep /> );

			// Should not throw and should not render error notice.
			const notice = container.querySelector( '.is-error' );
			expect( notice ).not.toBeInTheDocument();
		} );

		it( 'handles empty errors array', () => {
			mockUseOnboardingContext.mockReturnValue( createMockContext( [] ) );

			const { container } = render( <BusinessVerificationStep /> );

			const notice = container.querySelector( '.is-error' );
			expect( notice ).not.toBeInTheDocument();
		} );
	} );

	describe( 'Basic Rendering', () => {
		it( 'renders the step header', () => {
			mockUseOnboardingContext.mockReturnValue( createMockContext( [] ) );

			render( <BusinessVerificationStep /> );

			expect( screen.getByTestId( 'step-header' ) ).toBeInTheDocument();
		} );

		it( 'renders the stepper component', () => {
			mockUseOnboardingContext.mockReturnValue( createMockContext( [] ) );

			render( <BusinessVerificationStep /> );

			expect( screen.getByTestId( 'stepper' ) ).toBeInTheDocument();
		} );

		it( 'renders business and embedded steps', () => {
			mockUseOnboardingContext.mockReturnValue( createMockContext( [] ) );

			render( <BusinessVerificationStep /> );

			expect( screen.getByTestId( 'step-business' ) ).toBeInTheDocument();
			expect( screen.getByTestId( 'step-embedded' ) ).toBeInTheDocument();
		} );

		it( 'renders activate step when user has test account', () => {
			mockUseOnboardingContext.mockReturnValue(
				createMockContext( [], {
					context: {
						fields: { mccs_display_tree: [], location: 'US' },
						self_assessment: {},
						sub_steps: {
							activate: { status: 'not_started' },
							business: { status: 'not_started' },
							embedded: { status: 'not_started' },
						},
						has_test_account: true,
						has_sandbox_account: false,
					},
				} )
			);

			render( <BusinessVerificationStep /> );

			expect( screen.getByTestId( 'step-activate' ) ).toBeInTheDocument();
		} );

		it( 'does not render activate step when user has no test account', () => {
			mockUseOnboardingContext.mockReturnValue(
				createMockContext( [], {
					context: {
						fields: { mccs_display_tree: [], location: 'US' },
						self_assessment: {},
						sub_steps: {
							business: { status: 'not_started' },
							embedded: { status: 'not_started' },
						},
						has_test_account: false,
						has_sandbox_account: false,
					},
				} )
			);

			render( <BusinessVerificationStep /> );

			expect(
				screen.queryByTestId( 'step-activate' )
			).not.toBeInTheDocument();
		} );
	} );
} );
