import { beforeEach, describe, expect, it, vi, type Mock } from 'vitest';

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
vi.mock( '../../../data/onboarding-context', () => {
	const mock = {
		useOnboardingContext: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../../../components/header', () => {
	const mock = {
		__esModule: true,
		// eslint-disable-next-line @typescript-eslint/no-unused-vars -- onClose is required by the component interface.
		default: ( { onClose }: { onClose: () => void } ) => (
			<div data-testid="step-header">Header</div>
		),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../data/business-verification-context', () => {
	const mock = {
		BusinessVerificationContextProvider: ( {
			children,
			// eslint-disable-next-line @typescript-eslint/no-unused-vars -- initialData is required by the component interface.
			initialData,
		}: {
			children: React.ReactNode;
			initialData: Record< string, unknown >;
		} ) => <div data-testid="bv-context-provider">{ children }</div>,
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../components/form', () => {
	const mock = {
		OnboardingForm: ( { children }: { children: React.ReactNode } ) => (
			<div data-testid="onboarding-form">{ children }</div>
		),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../sections/business-details', () => {
	const mock = {
		__esModule: true,
		default: () => (
			<div data-testid="business-details">Business Details</div>
		),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../sections/embedded-kyc', () => {
	const mock = {
		__esModule: true,
		default: () => <div data-testid="embedded-kyc">Embedded KYC</div>,
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../sections/activate-payments', () => {
	const mock = {
		__esModule: true,
		default: () => (
			<div data-testid="activate-payments">Activate Payments</div>
		),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../components/stepper', () => {
	const mock = {
		Stepper: ( { children }: { children: React.ReactNode } ) => (
			<div data-testid="stepper">{ children }</div>
		),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../components/step', () => {
	const mock = {
		__esModule: true,
		default: ( {
			children,
			name,
		}: {
			children: React.ReactNode;
			name: string;
		} ) => <div data-testid={ `step-${ name }` }>{ children }</div>,
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../utils', () => {
	const mock = {
		getMccFromIndustry: vi.fn( () => 'mcc_code' ),
		getComingSoonShareKey: vi.fn( () => '' ),
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
const mockUseOnboardingContext = useOnboardingContext as Mock;

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
				business: {
					status: 'not_started',
				},
				embedded: {
					status: 'not_started',
				},
			},
			has_test_account: false,
			has_sandbox_account: false,
		},
		errors,
		...overrides,
	},
	closeModal: vi.fn(),
	sessionEntryPoint: 'settings',
} );
describe( 'BusinessVerificationStep', () => {
	beforeEach( () => {
		vi.clearAllMocks();
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
				{
					message: 'Test error',
					code: 'test_error',
				},
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
				{
					message: 'Single error message',
					code: 'single_error',
				},
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
				{
					message: 'First error',
					code: 'error_1',
				},
				{
					message: 'Second error',
					code: 'error_2',
				},
				{
					message: 'Third error',
					code: 'error_3',
				},
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
				{
					message: 'Error 1',
					code: 'error_1',
				},
				{
					message: 'Error 2',
					code: 'error_2',
				},
				{
					message: 'Error 3',
					code: 'error_3',
				},
				{
					message: 'Error 4',
					code: 'error_4',
				},
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
				{
					message: '',
					code: 'no_message_error',
				},
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
				{
					message: '   ',
					code: 'whitespace_message',
				},
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
				{
					message: 'Error with code',
					code: 'unique_code',
				},
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
				closeModal: vi.fn(),
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
						fields: {
							mccs_display_tree: [],
							location: 'US',
						},
						self_assessment: {},
						sub_steps: {
							activate: {
								status: 'not_started',
							},
							business: {
								status: 'not_started',
							},
							embedded: {
								status: 'not_started',
							},
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
						fields: {
							mccs_display_tree: [],
							location: 'US',
						},
						self_assessment: {},
						sub_steps: {
							business: {
								status: 'not_started',
							},
							embedded: {
								status: 'not_started',
							},
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
