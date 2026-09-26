/**
 * External dependencies
 */
import { render, screen, waitFor } from '@testing-library/react';
import React from 'react';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { useOnboardingContext } from '../../../data/onboarding-context';
import TestAccountStep from '../index';

jest.mock( '@wordpress/api-fetch', () => jest.fn() );

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

jest.mock( '@woocommerce/onboarding', () => {
	const MockLoader = ( { children }: { children: React.ReactNode } ) => (
		<div data-testid="loader">{ children }</div>
	);
	MockLoader.Layout = ( { children }: { children: React.ReactNode } ) => (
		<div>{ children }</div>
	);
	MockLoader.Illustration = ( {
		children,
	}: {
		children: React.ReactNode;
	} ) => <div>{ children }</div>;
	MockLoader.Title = ( { children }: { children: React.ReactNode } ) => (
		<div>{ children }</div>
	);
	// eslint-disable-next-line @typescript-eslint/no-unused-vars -- progress is required by the component interface.
	MockLoader.ProgressBar = ( { progress }: { progress: number } ) => (
		<div data-testid="progress-bar" />
	);
	MockLoader.Sequence = ( { children }: { children: React.ReactNode } ) => (
		<div>{ children }</div>
	);
	return { Loader: MockLoader };
} );

jest.mock( '@woocommerce/navigation', () => ( {
	navigateTo: jest.fn(),
	getNewPath: jest.fn( () => '' ),
} ) );

jest.mock( '~/settings-payments/utils', () => ( {
	recordPaymentsOnboardingEvent: jest.fn(),
} ) );

jest.mock( '~/settings-payments/components/modals', () => ( {
	WooPaymentsResetAccountModal: () => null,
} ) );

jest.mock( '~/utils/admin-settings', () => ( {
	WC_ASSET_URL: '',
} ) );

const mockUseOnboardingContext = useOnboardingContext as jest.Mock;
const mockApiFetch = apiFetch as jest.MockedFunction< typeof apiFetch >;

const createMockContext = ( overrides: Record< string, unknown > = {} ) => ( {
	currentStep: {
		id: 'test_account',
		status: 'not_started',
		actions: {
			init: { href: 'https://example.com/init' },
			check: { href: 'https://example.com/check' },
		},
	},
	closeModal: jest.fn(),
	setJustCompletedStepId: jest.fn(),
	sessionEntryPoint: 'settings',
	setSnackbar: jest.fn(),
	navigateToNextStep: jest.fn(),
	...overrides,
} );

describe( 'TestAccountStep', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'advances to the next step and shows a snackbar on a non-recoverable init error', async () => {
		const context = createMockContext();
		mockUseOnboardingContext.mockReturnValue( context );
		mockApiFetch.mockRejectedValue( {
			code: 'woocommerce_woopayments_onboarding_test_account_non_recoverable_error',
			message:
				'A test account could not be created, but onboarding can continue without it.',
		} );

		render( <TestAccountStep /> );

		await waitFor( () => {
			expect( context.navigateToNextStep ).toHaveBeenCalled();
		} );
		expect( context.setSnackbar ).toHaveBeenCalledWith(
			expect.objectContaining( { show: true } )
		);
		expect(
			screen.queryByRole( 'button', { name: /try again/i } )
		).not.toBeInTheDocument();
	} );

	it( 'shows the error notice with a retry action for other init errors', async () => {
		const context = createMockContext();
		mockUseOnboardingContext.mockReturnValue( context );
		mockApiFetch.mockRejectedValue( {
			code: 'some_other_error',
			message: 'Something went wrong.',
		} );

		render( <TestAccountStep /> );

		expect(
			await screen.findByText( 'Something went wrong.', {
				selector: 'p',
			} )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: /try again/i } )
		).toBeInTheDocument();
		expect( context.navigateToNextStep ).not.toHaveBeenCalled();
		expect( context.setSnackbar ).not.toHaveBeenCalled();
	} );
} );
