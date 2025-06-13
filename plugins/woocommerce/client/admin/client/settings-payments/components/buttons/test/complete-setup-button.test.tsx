/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';
import { render, fireEvent } from '@testing-library/react';
import {
	PaymentGatewayProvider,
	PaymentsProviderState,
	PaymentsProviderOnboardingState,
} from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { CompleteSetupButton } from '..';

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );

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
					} as PaymentGatewayProvider
				}
				settingsHref="/settings"
				onboardingHref={ '' }
				gatewayHasRecommendedPaymentMethods={ false }
				installingPlugin={ null }
				setOnboardingModalOpen={ jest.fn() }
			/>
		);

		fireEvent.click( getByRole( 'button' ) );

		expect( recordEvent ).toHaveBeenCalledWith(
			'settings_payments_provider_complete_setup_click',
			{
				business_country: expect.any( String ),
				provider_id: 'test-gateway',
				provider_onboarding_started: true,
				provider_onboarding_completed: false,
				provider_onboarding_test_mode: false,
			}
		);
	} );
} );
