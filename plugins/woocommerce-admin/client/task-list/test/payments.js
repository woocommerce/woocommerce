/**
 * External dependencies
 */
import { render, screen, waitFor } from '@testing-library/react';
import user from '@testing-library/user-event';
import '@testing-library/jest-dom/extend-expect';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { PayPal } from '../tasks/payments/paypal';

jest.mock( '@wordpress/api-fetch' );

describe( 'TaskList > Payments', () => {
	describe( 'PayPal', () => {
		afterEach( () => jest.clearAllMocks() );

		const mockInstallStep = {
			isComplete: true,
			key: 'install',
			label: 'Install',
		};

		it( 'shows "create account" when Jetpack and WCS are connected', async () => {
			const mockConnectUrl = 'https://connect.woocommerce.test/paypal';
			apiFetch.mockResolvedValue( {
				connectUrl: mockConnectUrl,
			} );

			render(
				<PayPal
					activePlugins={ [
						'jetpack',
						'woocommerce-gateway-paypal-express-checkout',
						'woocommerce-services',
					] }
					installStep={ mockInstallStep }
					isJetpackConnected
					wcsTosAccepted
				/>
			);

			// By default, the "create account" is checked.
			expect(
				screen.getByLabelText( 'Create a PayPal account for me', {
					selector: 'input',
				} )
			).toBeChecked();
			expect(
				screen.getByLabelText( 'Email address', { selector: 'input' } )
			).toBeDefined();
			expect(
				screen.getByText( 'Create account', { selector: 'button' } )
			).toBeDefined();

			// The email input should disappear when "create account" is unchecked.
			user.click(
				screen.getByLabelText( 'Create a PayPal account for me', {
					selector: 'input',
				} )
			);
			expect(
				screen.queryByLabelText( 'Email address', {
					selector: 'input',
				} )
			).toBeNull();

			// Since the oauth response was mocked, we should have a "connect" button.
			const oauthButton = await screen.findByText( 'Connect', {
				selector: 'a',
			} );
			expect( oauthButton ).toBeDefined();
			expect( oauthButton.href ).toEqual( mockConnectUrl );
		} );

		it( 'requires WCS to have TOS accepted to show "create account"', async () => {
			const mockConnectUrl = 'https://connect.woocommerce.test/paypal';
			apiFetch.mockResolvedValue( {
				connectUrl: mockConnectUrl,
			} );

			render(
				<PayPal
					activePlugins={ [
						'jetpack',
						'woocommerce-gateway-paypal-express-checkout',
						'woocommerce-services',
					] }
					installStep={ mockInstallStep }
					isJetpackConnected
					wcsTosAccepted={ false }
				/>
			);

			// Verify "create account" isn't shown.
			expect(
				screen.queryByLabelText( 'Create a PayPal account for me', {
					selector: 'input',
				} )
			).toBeNull();

			// Since the oauth response was mocked, we should have a "connect" button.
			const oauthButton = await screen.findByText( 'Connect', {
				selector: 'a',
			} );
			expect( oauthButton ).toBeDefined();
			expect( oauthButton.href ).toEqual( mockConnectUrl );
		} );

		it( 'validates "create account" form and persists PayPal options', async () => {
			const mockConnectUrl = 'https://connect.woocommerce.test/paypal';
			apiFetch.mockResolvedValue( {
				connectUrl: mockConnectUrl,
			} );

			const mockUpdateOptions = jest
				.fn()
				.mockResolvedValue( { success: true } );
			const mockCreateNotice = jest.fn();
			const mockMarkConfigured = jest.fn();
			const mockOptions = {
				woocommerce_ppec_paypal_settings: {
					test: 'yes',
				},
			};

			render(
				<PayPal
					activePlugins={ [
						'jetpack',
						'woocommerce-gateway-paypal-express-checkout',
						'woocommerce-services',
					] }
					installStep={ mockInstallStep }
					isJetpackConnected
					wcsTosAccepted
					options={ mockOptions }
					createNotice={ mockCreateNotice }
					markConfigured={ mockMarkConfigured }
					updateOptions={ mockUpdateOptions }
				/>
			);

			const createButton = screen.getByText( 'Create account', {
				selector: 'button',
			} );
			const emailInput = screen.getByLabelText( 'Email address', {
				selector: 'input',
			} );

			// Verify empty emails are invalid.
			user.click( createButton );
			expect(
				await screen.findByText( 'Please enter a valid email address' )
			).toBeDefined();
			expect( mockUpdateOptions ).not.toHaveBeenCalled();

			// Verify non-empty email validation.
			await user.type( emailInput, 'not an email' );
			user.click( createButton );
			expect(
				await screen.findByText( 'Please enter a valid email address' )
			).toBeDefined();
			expect( mockUpdateOptions ).not.toHaveBeenCalled();

			// Submit a good email.
			user.clear( emailInput );
			await user.type( emailInput, 'owner@store.com' );
			user.click( createButton );
			expect(
				screen.queryByText( 'Please enter a valid email address' )
			).toBeNull();

			// Trick to wait for the async code to call updateOption().
			await waitFor( () =>
				expect( mockUpdateOptions ).toHaveBeenCalledTimes( 1 )
			);

			// Verify the persisted options.
			expect( mockUpdateOptions ).toHaveBeenCalledWith( {
				woocommerce_ppec_paypal_settings: {
					email: 'owner@store.com',
					enabled: 'yes',
					reroute_requests: 'yes',
					test: 'yes', // Makes sure we're extending the retrieved settings.
				},
			} );
		} );

		it( 'shows API credential inputs when "create account" opted out and OAuth fetch fails', async () => {
			apiFetch.mockResolvedValue( false );

			render(
				<PayPal
					activePlugins={ [
						'jetpack',
						'woocommerce-gateway-paypal-express-checkout',
						'woocommerce-services',
					] }
					installStep={ mockInstallStep }
					isJetpackConnected
					wcsTosAccepted
				/>
			);

			// The email input should disappear when "create account" is unchecked.
			user.click(
				screen.getByLabelText( 'Create a PayPal account for me', {
					selector: 'input',
				} )
			);
			expect(
				screen.queryByLabelText( 'Email address', {
					selector: 'input',
				} )
			).toBeNull();

			// Since the oauth response failed, we should have the API credentials form.
			expect(
				await screen.findByText( 'Proceed', { selector: 'button' } )
			).toBeDefined();
			expect(
				screen.getByLabelText( 'API Username', { selector: 'input' } )
			).toBeDefined();
			expect(
				screen.getByLabelText( 'API Password', { selector: 'input' } )
			).toBeDefined();
		} );

		it( 'shows OAuth connect button', async () => {
			const mockConnectUrl = 'https://connect.woocommerce.test/paypal';
			apiFetch.mockResolvedValue( {
				connectUrl: mockConnectUrl,
			} );

			render(
				<PayPal
					activePlugins={ [
						'woocommerce-gateway-paypal-express-checkout',
					] }
					installStep={ mockInstallStep }
				/>
			);

			// Verify the "create account" option is absent.
			expect(
				screen.queryByLabelText( 'Create a PayPal account for me', {
					selector: 'input',
				} )
			).toBeNull();

			// Since the oauth response was mocked, we should have a "connect" button.
			const oauthButton = await screen.findByText( 'Connect', {
				selector: 'a',
			} );
			expect( oauthButton ).toBeDefined();
			expect( oauthButton.href ).toEqual( mockConnectUrl );
		} );

		it( 'shows API credential inputs when OAuth fetch fails', async () => {
			apiFetch.mockResolvedValue( false );

			render(
				<PayPal
					activePlugins={ [
						'woocommerce-gateway-paypal-express-checkout',
					] }
					installStep={ mockInstallStep }
				/>
			);

			// Verify the "create account" option is absent.
			expect(
				screen.queryByLabelText( 'Create a PayPal account for me', {
					selector: 'input',
				} )
			).toBeNull();

			// Since the oauth response failed, we should have the API credentials form.
			expect(
				await screen.findByText( 'Proceed', { selector: 'button' } )
			).toBeDefined();
			expect(
				screen.getByLabelText( 'API Username', { selector: 'input' } )
			).toBeDefined();
			expect(
				screen.getByLabelText( 'API Password', { selector: 'input' } )
			).toBeDefined();
		} );
	} );
} );
