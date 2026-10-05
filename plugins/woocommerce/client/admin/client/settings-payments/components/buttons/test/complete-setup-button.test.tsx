import { describe, expect, it, vi } from 'vitest';

/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';
import { render, fireEvent } from '@testing-library/react';
import {
	PaymentGatewayProvider,
	PaymentsProviderState,
	PaymentsProviderOnboardingState,
	PluginData,
} from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { CompleteSetupButton } from '..';
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
describe( 'CompleteSetupButton', () => {
	it( 'should record settings_payments_provider_complete_setup_click event on click of the button', () => {
		const { getByRole } = render(
			<CompleteSetupButton
				gatewayProvider={
					{
						id: 'test-gateway',
						state: {
							enabled: true,
							account_connected: false,
							needs_setup: true,
							test_mode: false,
							dev_mode: false,
						} as PaymentsProviderState,
						onboarding: {
							state: {
								started: true,
								completed: false,
								test_mode: false,
							} as PaymentsProviderOnboardingState,
						},
						plugin: {
							slug: 'test-plugin',
							file: 'test-file',
							status: 'installed',
						} as PluginData,
						_suggestion_id: 'test-suggestion',
						_type: 'gateway',
					} as PaymentGatewayProvider
				}
				settingsHref="/settings"
				onboardingHref={ '' }
				gatewayHasRecommendedPaymentMethods={ false }
				installingPlugin={ null }
				setOnboardingModalOpen={ vi.fn() }
			/>
		);
		fireEvent.click( getByRole( 'button' ) );
		expect( recordEvent ).toHaveBeenCalledWith(
			'settings_payments_provider_complete_setup_click',
			expect.objectContaining( {
				provider_id: 'test-gateway',
				suggestion_id: 'test-suggestion',
			} )
		);
	} );
} );
