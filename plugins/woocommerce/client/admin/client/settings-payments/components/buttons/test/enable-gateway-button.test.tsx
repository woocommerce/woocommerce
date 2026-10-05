import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
const {
	mockCreateErrorNotice,
	mockTogglePaymentGateway,
	mockInvalidateResolutionForStoreSelector,
} = vi.hoisted( () => {
	const mockCreateErrorNotice = vi.fn();
	const mockTogglePaymentGateway = vi.fn();
	const mockInvalidateResolutionForStoreSelector = vi.fn();
	return {
		mockCreateErrorNotice,
		mockTogglePaymentGateway,
		mockInvalidateResolutionForStoreSelector,
	};
} );

/**
 * External dependencies
 */
import { fireEvent, render, waitFor } from '@testing-library/react';
import type {
	PaymentGatewayProvider,
	PaymentsProviderState,
} from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { EnableGatewayButton } from '../enable-gateway-button';
vi.mock( '@woocommerce/data', () => {
	const mock = {
		paymentSettingsStore: {},
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
		dispatch: vi.fn( () => ( {
			createErrorNotice: mockCreateErrorNotice,
		} ) ),
		useDispatch: vi.fn( () => ( {
			togglePaymentGateway: mockTogglePaymentGateway,
			invalidateResolutionForStoreSelector:
				mockInvalidateResolutionForStoreSelector,
		} ) ),
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
		recordPaymentsProviderEvent: vi.fn(),
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
		wooPaymentsOnboardingSessionEntrySettings: 'settings',
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
const gatewayProvider = {
	id: 'test-gateway',
	title: 'Test Gateway',
	state: {
		enabled: false,
		account_connected: true,
		needs_setup: true,
		test_mode: false,
		dev_mode: false,
	} as PaymentsProviderState,
	_suggestion_id: 'test-suggestion',
	_type: 'gateway',
} as PaymentGatewayProvider;
describe( 'EnableGatewayButton', () => {
	beforeEach( () => {
		mockTogglePaymentGateway.mockResolvedValue( {
			data: 'needs_setup',
		} );
		Object.defineProperty( window, 'woocommerce_admin', {
			value: {
				ajax_url: '/wp-admin/admin-ajax.php',
				nonces: {
					gateway_toggle: 'test-nonce',
				},
			},
			writable: true,
		} );
	} );
	afterEach( () => {
		vi.clearAllMocks();
	} );
	it( 'shows an actionable setup message when a connected gateway still needs setup', async () => {
		const { getByRole } = render(
			<EnableGatewayButton
				gatewayProvider={ gatewayProvider }
				settingsHref="/settings/test-gateway"
				onboardingHref="/onboard/test-gateway"
				isOffline={ false }
				gatewayHasRecommendedPaymentMethods={ false }
				installingPlugin={ null }
			/>
		);
		fireEvent.click(
			getByRole( 'link', {
				name: 'Enable',
			} )
		);
		await waitFor( () => {
			expect( mockCreateErrorNotice ).toHaveBeenCalledWith(
				expect.stringContaining( 'Test Gateway' ),
				expect.objectContaining( {
					type: 'snackbar',
					explicitDismiss: true,
					actions: expect.arrayContaining( [
						expect.objectContaining( {
							label: 'Manage',
							url: '/settings/test-gateway',
						} ),
					] ),
				} )
			);
		} );
	} );
	it.each( [
		[ 'empty', '' ],
		[ 'null', null ],
	] )(
		'falls back to a generic setup message when the gateway title is %s',
		async ( _case, title ) => {
			const gatewayProviderWithoutTitle = {
				...gatewayProvider,
				title,
			} as unknown as PaymentGatewayProvider;
			const { getByRole } = render(
				<EnableGatewayButton
					gatewayProvider={ gatewayProviderWithoutTitle }
					settingsHref="/settings/test-gateway"
					onboardingHref="/onboard/test-gateway"
					isOffline={ false }
					gatewayHasRecommendedPaymentMethods={ false }
					installingPlugin={ null }
				/>
			);
			fireEvent.click(
				getByRole( 'link', {
					name: 'Enable',
				} )
			);
			await waitFor( () => {
				expect( mockCreateErrorNotice ).toHaveBeenCalledWith(
					expect.stringContaining( 'this payment method' ),
					expect.objectContaining( {
						type: 'snackbar',
						explicitDismiss: true,
						actions: expect.arrayContaining( [
							expect.objectContaining( {
								label: 'Manage',
								url: '/settings/test-gateway',
							} ),
						] ),
					} )
				);
			} );
		}
	);
} );
