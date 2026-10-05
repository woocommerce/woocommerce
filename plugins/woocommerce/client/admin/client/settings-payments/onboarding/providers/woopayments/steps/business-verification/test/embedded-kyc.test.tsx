import {
	afterEach,
	beforeEach,
	describe,
	expect,
	it,
	vi,
	type Mock,
	type MockedFunction,
} from 'vitest';

/**
 * External dependencies
 */
import { act, render, screen } from '@testing-library/react';
import React from 'react';

/**
 * Internal dependencies
 */
import EmbeddedKyc from '../sections/embedded-kyc';
import { useOnboardingContext } from '../../../data/onboarding-context';
import { useBusinessVerificationContext } from '../data/business-verification-context';
import { recordPaymentsOnboardingEvent } from '~/settings-payments/utils';
type MockEmbeddedAccountOnboardingProps = {
	onLoaderStart?: ( value: { elementTagName: string } ) => void;
	onLoadError?: ( value: {
		error: {
			type: string;
			message?: string;
		};
		elementTagName: string;
	} ) => void;
	onInitializationError?: ( failure: {
		reason: 'bad_session' | 'init_error';
		message: string;
		receivedKeys?: string[];
	} ) => void;
	[ key: string ]: unknown;
};
let mockEmbeddedAccountOnboardingProps: MockEmbeddedAccountOnboardingProps;
vi.mock( '../components/embedded', () => {
	const mock = {
		EmbeddedAccountOnboarding: (
			props: MockEmbeddedAccountOnboardingProps
		) => {
			mockEmbeddedAccountOnboardingProps = props;
			return <div data-testid="embedded-account-onboarding" />;
		},
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
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
vi.mock( '../data/business-verification-context', () => {
	const mock = {
		useBusinessVerificationContext: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../../../components/stripe-spinner', () => {
	const mock = {
		__esModule: true,
		default: () => <div data-testid="stripe-spinner" />,
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../utils/actions', () => {
	const mock = {
		finalizeEmbeddedKycSession: vi.fn(),
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
const mockUseBusinessVerificationContext =
	useBusinessVerificationContext as Mock;
const mockRecordPaymentsOnboardingEvent =
	recordPaymentsOnboardingEvent as MockedFunction<
		typeof recordPaymentsOnboardingEvent
	>;
const mockContexts = () => {
	mockUseBusinessVerificationContext.mockReturnValue( {
		data: {},
	} );
	mockUseOnboardingContext.mockReturnValue( {
		currentStep: {
			actions: {
				kyc_fallback: {
					href: 'https://example.com/fallback',
				},
				kyc_session_finish: {
					href: 'https://example.com/session/finish',
				},
			},
		},
		navigateToNextStep: vi.fn(),
		sessionEntryPoint: 'settings',
	} );
};
const getFailureNotice = (): HTMLElement => {
	const notice = screen
		.getByRole( 'link', {
			name: 'Learn more',
		} )
		.closest< HTMLElement >( '[tabindex="-1"]' );
	if ( ! notice ) {
		throw new Error( 'Expected focused failure notice.' );
	}
	return notice;
};
describe( 'EmbeddedKyc', () => {
	beforeEach( () => {
		vi.clearAllMocks();
		vi.useRealTimers();
		mockEmbeddedAccountOnboardingProps = {};
		mockContexts();
	} );
	afterEach( () => {
		vi.useRealTimers();
	} );
	it( 'announces the loading state while waiting for the embedded loader', () => {
		render( <EmbeddedKyc /> );
		expect( screen.getByRole( 'status' ) ).toHaveTextContent(
			'Loading onboarding…'
		);
	} );
	it( 'shows a unified error when the embedded loader does not start before the timeout', () => {
		vi.useFakeTimers();
		render( <EmbeddedKyc /> );
		act( () => {
			vi.advanceTimersByTime( 20000 );
		} );
		const notice = getFailureNotice();
		expect( notice ).toHaveTextContent(
			"We couldn't load this step. This can happen when your site's security or server settings block a required connection to Stripe. Check the setup requirements, or contact support if the error persists."
		);
		expect( notice ).toHaveFocus();
		expect(
			screen.getByRole( 'link', {
				name: 'Learn more',
			} )
		).toHaveAttribute(
			'href',
			'https://woocommerce.com/document/woopayments/testing-and-troubleshooting/#onboarding'
		);
		expect(
			screen.queryByTestId( 'embedded-account-onboarding' )
		).not.toBeInTheDocument();
		expect( mockRecordPaymentsOnboardingEvent ).toHaveBeenCalledWith(
			'woopayments_onboarding_modal_kyc_load_error',
			{
				reason: 'timeout',
				collect_payout_requirements: false,
				source: 'settings',
			}
		);
	} );
	it( 'clears the timeout when the embedded loader starts', () => {
		vi.useFakeTimers();
		render( <EmbeddedKyc collectPayoutRequirements /> );
		act( () => {
			mockEmbeddedAccountOnboardingProps.onLoaderStart?.( {
				elementTagName: 'connect-account-onboarding',
			} );
		} );
		act( () => {
			vi.advanceTimersByTime( 20000 );
		} );
		expect(
			screen.queryByRole( 'link', {
				name: 'Learn more',
			} )
		).not.toBeInTheDocument();
		expect(
			screen.getByTestId( 'embedded-account-onboarding' )
		).toBeInTheDocument();
		expect( mockRecordPaymentsOnboardingEvent ).toHaveBeenCalledWith(
			'woopayments_onboarding_modal_kyc_started_loading',
			{
				collect_payout_requirements: true,
				source: 'settings',
			}
		);
		expect( mockRecordPaymentsOnboardingEvent ).not.toHaveBeenCalledWith(
			'woopayments_onboarding_modal_kyc_load_error',
			expect.anything()
		);
	} );
	it( 'shows a unified error when Stripe reports a load error', () => {
		render( <EmbeddedKyc /> );
		act( () => {
			mockEmbeddedAccountOnboardingProps.onLoadError?.( {
				error: {
					type: 'api_connection_error',
					message: 'Stripe failed to load.',
				},
				elementTagName: 'connect-account-onboarding',
			} );
		} );
		expect(
			screen.getByRole( 'link', {
				name: 'Learn more',
			} )
		).toBeInTheDocument();
		expect( mockRecordPaymentsOnboardingEvent ).toHaveBeenCalledWith(
			'woopayments_onboarding_modal_kyc_load_error',
			{
				reason: 'load_error',
				error_type: 'api_connection_error',
				error_message: 'Stripe failed to load.',
				collect_payout_requirements: false,
				source: 'settings',
			}
		);
	} );
	it( 'shows HTTPS-specific copy when Stripe reports an invalid request', () => {
		render( <EmbeddedKyc /> );
		act( () => {
			mockEmbeddedAccountOnboardingProps.onLoadError?.( {
				error: {
					type: 'invalid_request_error',
					message: 'This application requires HTTPS.',
				},
				elementTagName: 'connect-account-onboarding',
			} );
		} );
		expect( getFailureNotice() ).toHaveTextContent(
			'Payment activation through our financial partner requires HTTPS and cannot be completed.'
		);
		expect( mockRecordPaymentsOnboardingEvent ).toHaveBeenCalledWith(
			'woopayments_onboarding_modal_kyc_load_error',
			{
				reason: 'load_error',
				error_type: 'invalid_request_error',
				error_message: 'This application requires HTTPS.',
				collect_payout_requirements: false,
				source: 'settings',
			}
		);
	} );
	it( 'shows a unified error when initialization fails', () => {
		render( <EmbeddedKyc /> );
		act( () => {
			mockEmbeddedAccountOnboardingProps.onInitializationError?.( {
				reason: 'bad_session',
				message: 'Unable to start onboarding.',
				receivedKeys: [ 'unexpected' ],
			} );
		} );
		expect(
			screen.getByRole( 'link', {
				name: 'Learn more',
			} )
		).toBeInTheDocument();
		expect( mockRecordPaymentsOnboardingEvent ).toHaveBeenCalledWith(
			'woopayments_onboarding_modal_kyc_load_error',
			{
				reason: 'bad_session',
				error_message: 'Unable to start onboarding.',
				received_keys: 'unexpected',
				collect_payout_requirements: false,
				source: 'settings',
			}
		);
	} );
	it( 'omits initialization error messages from analytics', () => {
		render( <EmbeddedKyc /> );
		act( () => {
			mockEmbeddedAccountOnboardingProps.onInitializationError?.( {
				reason: 'init_error',
				message: 'Network failed for https://internal.example/token.',
			} );
		} );
		expect( getFailureNotice() ).toHaveTextContent(
			"We couldn't load this step. This can happen when your site's security or server settings block a required connection to Stripe. Check the setup requirements, or contact support if the error persists."
		);
		expect( mockRecordPaymentsOnboardingEvent ).toHaveBeenCalledWith(
			'woopayments_onboarding_modal_kyc_load_error',
			{
				reason: 'init_error',
				collect_payout_requirements: false,
				source: 'settings',
			}
		);
	} );
	it( 'keeps the unified error when the loader starts after the timeout', () => {
		vi.useFakeTimers();
		render( <EmbeddedKyc /> );
		const onLoaderStart = mockEmbeddedAccountOnboardingProps.onLoaderStart;
		act( () => {
			vi.advanceTimersByTime( 20000 );
			onLoaderStart?.( {
				elementTagName: 'connect-account-onboarding',
			} );
		} );
		expect(
			screen.getByRole( 'link', {
				name: 'Learn more',
			} )
		).toBeInTheDocument();
		expect(
			screen.queryByTestId( 'embedded-account-onboarding' )
		).not.toBeInTheDocument();
		expect( mockRecordPaymentsOnboardingEvent ).toHaveBeenCalledTimes( 1 );
		expect( mockRecordPaymentsOnboardingEvent ).toHaveBeenCalledWith(
			'woopayments_onboarding_modal_kyc_load_error',
			expect.objectContaining( {
				reason: 'timeout',
			} )
		);
	} );
} );
