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
import { navigateToUrl } from '../navigation';

const mockNavigate = jest.fn();
const mockNavigateToUrl = navigateToUrl as jest.Mock;

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );

jest.mock( 'react-router-dom', () => ( {
	...jest.requireActual( 'react-router-dom' ),
	useNavigate: () => mockNavigate,
} ) );

jest.mock( '../navigation', () => ( {
	navigateToUrl: jest.fn(),
} ) );

describe( 'SettingsButton', () => {
	const gatewayProvider = {
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
	} as PaymentGatewayProvider;

	afterEach( () => {
		jest.clearAllMocks();
	} );

	it( 'should record settings_payments_provider_manage_click event on click of the button', () => {
		const { getByRole } = render(
			<Router>
				<SettingsButton
					gatewayProvider={ gatewayProvider }
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

	it( 'routes provider settings URLs with a path query through React Router', () => {
		const { getByRole } = render(
			<Router>
				<SettingsButton
					gatewayProvider={ gatewayProvider }
					settingsHref={
						'https://example.com/wp-admin/admin.php?page=wc-settings&tab=checkout&path=%2Fthird-party%2Fsettings&from=settings-payments'
					}
				/>
			</Router>
		);

		fireEvent.click( getByRole( 'button', { name: 'Manage' } ) );

		expect( mockNavigate ).toHaveBeenCalledWith(
			'/wp-admin/admin.php?page=wc-settings&tab=checkout&path=%2Fthird-party%2Fsettings&from=settings-payments'
		);
		expect( mockNavigateToUrl ).not.toHaveBeenCalled();
	} );

	it( 'leaves non-Reactified provider settings URLs on full-page navigation', () => {
		const sectionSettingsHref =
			'http://localhost/wp-admin/admin.php?page=wc-settings&tab=checkout&section=third_party&from=settings-payments';
		window.history.pushState(
			{},
			'',
			'http://localhost/wp-admin/admin.php?page=wc-settings&tab=checkout'
		);

		const { getByRole } = render(
			<Router>
				<SettingsButton
					gatewayProvider={ gatewayProvider }
					settingsHref={ sectionSettingsHref }
				/>
			</Router>
		);

		fireEvent.click( getByRole( 'button', { name: 'Manage' } ) );

		expect( mockNavigate ).not.toHaveBeenCalled();
		expect( mockNavigateToUrl ).toHaveBeenCalledWith( sectionSettingsHref );
	} );
} );
