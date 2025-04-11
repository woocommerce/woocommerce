/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';
import { render, fireEvent } from '@testing-library/react';
import {
	PaymentProviderState,
	PaymentProviderOnboardingState,
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
				gatewayId="test-gateway"
				gatewayState={
					{
						enabled: true,
						account_connected: false,
						needs_setup: true,
						test_mode: false,
						dev_mode: false,
					} as PaymentProviderState
				}
				onboardingState={
					{
						started: true,
						completed: false,
						test_mode: false,
					} as PaymentProviderOnboardingState
				}
				settingsHref="/settings"
				onboardingHref={ '' }
				gatewayHasRecommendedPaymentMethods={ false }
				installingPlugin={ null }
			/>
		);

		fireEvent.click( getByRole( 'button' ) );

		expect( recordEvent ).toHaveBeenCalledWith(
			'settings_payments_provider_complete_setup_click',
			{
				provider_id: 'test-gateway',
				onboarding_started: true,
				onboarding_completed: false,
				onboarding_test_mode: false,
			}
		);
	} );
} );
