/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { useSelect } from '@wordpress/data';
import { paymentSettingsStore, pluginsStore } from '@woocommerce/data';
import type { ReactNode } from 'react';

/**
 * Internal dependencies
 */
import {
	SetUpPaymentsProvider,
	useSetUpPaymentsContext,
} from './setup-payments-context';

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn(),
} ) );

jest.mock( '@woocommerce/data', () => ( {
	paymentSettingsStore: 'paymentSettingsStore',
	pluginsStore: 'pluginsStore',
} ) );

jest.mock( '@woocommerce/navigation', () => ( {
	getNewPath: jest.fn( () => 'native-woopayments-onboarding-url' ),
} ) );

jest.mock( '~/woopayments/onboarding', () => ( {
	LYSPaymentsSteps: [],
	OnboardingProvider: ( { children }: { children: ReactNode } ) => (
		<div data-testid="onboarding-provider">{ children }</div>
	),
} ) );

const ContextReader = () => {
	const { isWooPaymentsActive, isWooPaymentsInstalled } =
		useSetUpPaymentsContext();

	return (
		<div>
			<span data-testid="is-active">
				{ isWooPaymentsActive ? 'true' : 'false' }
			</span>
			<span data-testid="is-installed">
				{ isWooPaymentsInstalled ? 'true' : 'false' }
			</span>
		</div>
	);
};

describe( 'SetUpPaymentsProvider', () => {
	beforeEach( () => {
		( useSelect as jest.Mock ).mockImplementation( ( selector ) =>
			selector( ( store: unknown ) => {
				if ( store === paymentSettingsStore ) {
					return {
						isFetching: () => false,
						getPaymentProviders: () => [
							{
								id: 'woocommerce_payments',
								onboarding: {
									type: 'native_in_context',
									_links: {
										onboard: {
											href: 'native-onboarding-url',
										},
									},
								},
							},
						],
					};
				}

				if ( store === pluginsStore ) {
					return {
						getActivePlugins: () => [],
						getInstalledPlugins: () => [],
					};
				}

				return {};
			} )
		);
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	it( 'should expose native WooPayments as active and installed without the legacy plugin', () => {
		render(
			<SetUpPaymentsProvider closeModal={ jest.fn() }>
				<ContextReader />
			</SetUpPaymentsProvider>
		);

		expect( screen.getByTestId( 'is-active' ) ).toHaveTextContent(
			'true'
		);
		expect( screen.getByTestId( 'is-installed' ) ).toHaveTextContent(
			'true'
		);
		expect( screen.getByTestId( 'onboarding-provider' ) ).toBeInTheDocument();
	} );
} );
