/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';
import { render, fireEvent } from '@testing-library/react';
import { MemoryRouter as Router } from 'react-router-dom';
import {
	PaymentGatewayProvider,
	PaymentsProviderState,
	PaymentsProviderOnboardingState,
	PluginData,
} from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { SettingsButton } from '..';

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );

describe( 'SettingsButton', () => {
	it( 'should record settings_payments_provider_manage_click event on click of the button', () => {
		const { getByRole } = render(
			<Router>
				<SettingsButton
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
					settingsHref={ '' }
				/>
			</Router>
		);
		fireEvent.click( getByRole( 'button', { name: 'Manage' } ) );
		expect( recordEvent ).toHaveBeenCalledWith(
			'settings_payments_provider_manage_click',
			expect.objectContaining( {
				business_country: expect.any( String ),
				provider_id: 'test-gateway',
			} )
		);
	} );
} );
