/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import '@testing-library/jest-dom/extend-expect';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { PayPal, PAYPAL_PLUGIN } from '../tasks/payments/methods/paypal';
import { getPaymentMethods } from '../tasks/payments/methods';
import { setMethodEnabledOption } from '../../task-list/tasks/payments';
import { GenericPaymentStep } from '../tasks/payments/generic-payment-step';

jest.mock( '@wordpress/api-fetch' );

describe( 'TaskList > Payments', () => {
	describe( 'Payments', () => {
		const optionName = 'woocommerce_mollie_payments_settings';

		it( 'does not update an option if the value is the same as the current one', async () => {
			const mockProps = {
				clearTaskStatusCache: jest.fn(),
				updateOptions: jest.fn(),
				options: {
					woocommerce_klarna_payments_settings: false,
					woocommerce_mollie_payments_settings: { enabled: 'yes' },
				},
			};

			await setMethodEnabledOption( optionName, 'yes', mockProps );
			expect( mockProps.updateOptions ).not.toHaveBeenCalled();
		} );

		it( 'does update an option if the value is different to the current one', async () => {
			const mockProps = {
				clearTaskStatusCache: jest.fn(),
				updateOptions: jest.fn(),
				options: {
					woocommerce_klarna_payments_settings: false,
					woocommerce_mollie_payments_settings: { enabled: 'no' },
				},
			};

			await setMethodEnabledOption( optionName, 'yes', mockProps );

			expect( mockProps.updateOptions ).toHaveBeenCalledWith( {
				woocommerce_mollie_payments_settings: { enabled: 'yes' },
			} );
		} );
	} );

	describe( 'Methods', () => {
		const params = {
			activePlugins: [],
			countryCode: 'SE',
			onboardingStatus: {},
			options: [],
			profileItems: { industry: [] },
		};

		it( 'includes Klarna Checkout for SE, NO, and FI', () => {
			[ 'SE', 'NO', 'FI' ].forEach( ( countryCode ) => {
				params.countryCode = countryCode;
				const methods = getPaymentMethods( params );
				expect(
					methods.some(
						( method ) => method.key === 'klarna_checkout'
					)
				).toBe( true );
			} );
		} );

		it( 'includes Klarna Payment for EU countries', () => {
			const supportedCountryCodes = [
				'DK',
				'DE',
				'AT',
				'NL',
				'CH',
				'BE',
				'SP',
				'PL',
				'FR',
				'IT',
				'GB',
			];
			supportedCountryCodes.forEach( ( countryCode ) => {
				params.countryCode = countryCode;
				const methods = getPaymentMethods( params );
				expect(
					methods.some( ( e ) => e.key === 'klarna_payments' )
				).toBe( true );
			} );
		} );

		describe( 'Mollie', () => {
			it( 'Detects the plugin is enabled based on the options passed', () => {
				const mollieParams = {
					...params,
					options: {
						woocommerce_mollie_payments_settings: {
							enabled: 'yes',
						},
					},
				};

				const mollieMethod = getPaymentMethods( mollieParams ).find(
					( method ) => method.key === 'mollie'
				);

				expect( mollieMethod.isEnabled ).toBe( true );
			} );

			it( 'is enabled for supported countries', () => {
				[
					'FR',
					'DE',
					'GB',
					'AT',
					'CH',
					'ES',
					'IT',
					'PL',
					'FI',
					'NL',
					'BE',
				].forEach( ( countryCode ) => {
					const methods = getPaymentMethods( {
						...params,
						countryCode,
					} );

					expect(
						methods.filter( ( method ) => method.key === 'mollie' )
							.length
					).toBe( 1 );
				} );
			} );
		} );

		it( 'is marked as `isConfigured` if the plugin is active', () => {
			expect(
				getPaymentMethods( {
					...params,
					activePlugins: [ 'mollie-payments-for-woocommerce' ],
				} ).find( ( method ) => method.key === 'mollie' ).isConfigured
			).toBe( true );
		} );

		describe( 'MercadoPago', () => {
			it( 'Is enabled for supported countries', () => {
				[ 'AR', 'BR', 'CL', 'CO', 'MX', 'PE', 'UY' ].forEach(
					( countryCode ) => {
						params.countryCode = countryCode;
						const methods = getPaymentMethods( params );
						expect(
							methods.some(
								( method ) => method.key === 'mercadopago'
							)
						).toBe( true );
					}
				);
			} );

			it( 'Detects whether the plugin is enabled based on the received options', () => {
				const mercadoPagoParams = {
					...params,
					options: {
						...params.options,
						'woocommerce_woo-mercado-pago-basic_settings': {
							enabled: 'yes',
						},
					},
				};

				const mercadoPagoMethod = getPaymentMethods(
					mercadoPagoParams
				).find( ( method ) => method.key === 'mercadopago' );

				expect( mercadoPagoMethod.isEnabled ).toBe( true );
			} );
		} );

		it( 'If the plugin is active `mercadopago` is marked as `isConfigured`', () => {
			expect(
				getPaymentMethods( {
					...params,
					activePlugins: [ 'woocommerce-mercadopago' ],
				} ).find( ( method ) => method.key === 'mercadopago' )
					.isConfigured
			).toBe( true );
		} );
	} );

	describe( 'PayPal', () => {
		afterEach( () => jest.clearAllMocks() );

		const mockInstallStep = {
			isComplete: true,
			key: 'install',
			label: 'Install',
		};

		it( 'shows API credential inputs when "create account" opted out and OAuth fetch fails', async () => {
			apiFetch.mockResolvedValue( false );

			render(
				<PayPal
					activePlugins={ [
						'jetpack',
						PAYPAL_PLUGIN,
						'woocommerce-services',
					] }
					isRequestingOptions={ false }
					options={ {} }
					installStep={ mockInstallStep }
				/>
			);

			// Since the oauth response failed, we should have the API credentials form.
			expect(
				await screen.findByText( 'Proceed', { selector: 'button' } )
			).toBeDefined();
			expect(
				screen.queryByLabelText( 'Email address', {
					selector: 'input',
				} )
			).toBeDefined();
			expect(
				screen.getByLabelText( 'Merchant Id', { selector: 'input' } )
			).toBeDefined();
			expect(
				screen.getByLabelText( 'Client Id', { selector: 'input' } )
			).toBeDefined();
			expect(
				screen.getByLabelText( 'Secret Key', { selector: 'input' } )
			).toBeDefined();
		} );

		it( 'shows OAuth connect button', async () => {
			global.ppcp_onboarding = {
				reload: () => {},
			};
			const mockConnectUrl = 'https://connect.woocommerce.test/paypal';
			apiFetch.mockResolvedValue( {
				signupLink: mockConnectUrl,
			} );

			render(
				<PayPal
					activePlugins={ [ PAYPAL_PLUGIN ] }
					installStep={ mockInstallStep }
					isRequestingOptions={ false }
					options={ {} }
				/>
			);

			// Since the oauth response was mocked, we should have a "connect" button.
			const oauthButton = await screen.findByText( 'Connect', {
				selector: 'a',
			} );
			expect( oauthButton ).toBeDefined();
			expect( oauthButton.href ).toEqual( mockConnectUrl );
			expect( oauthButton.dataset.paypalButton ).toEqual( 'true' );
			expect( oauthButton.dataset.paypalOnboardButton ).toEqual( 'true' );
		} );
	} );

	describe( 'Payfast', () => {
		const params = {
			activePlugins: [],
			countryCode: 'ZA',
			onboardingStatus: {},
			options: [],
			profileItems: { industry: [] },
		};
		const mockInstallStep = {
			isComplete: true,
			key: 'install',
			label: 'Install',
		};
		it( 'Detects the plugin is enabled based on the options passed', () => {
			const payfastParams = {
				...params,
				options: {
					woocommerce_payfast_settings: {
						enabled: 'yes',
					},
				},
			};

			const payfastMethod = getPaymentMethods( payfastParams ).find(
				( method ) => method.key === 'payfast'
			);

			expect( payfastMethod.isEnabled ).toBe( true );
		} );

		it( 'is enabled for supported countries', () => {
			[ 'ZA' ].forEach( ( countryCode ) => {
				const methods = getPaymentMethods( {
					...params,
					countryCode,
				} );

				expect(
					methods.filter( ( method ) => method.key === 'payfast' )
						.length
				).toBe( 1 );
			} );
		} );

		it( 'shows API credential inputs', async () => {
			const payfastMethod = getPaymentMethods( params ).find(
				( method ) => method.key === 'payfast'
			);

			render(
				<GenericPaymentStep
					isRequestingOptions={ false }
					options={ {} }
					installStep={ mockInstallStep }
					methodConfig={ payfastMethod }
				/>
			);

			// Since the oauth response failed, we should have the API credentials form.
			expect(
				await screen.findByText( 'Proceed', { selector: 'button' } )
			).toBeDefined();
			expect(
				screen.getByLabelText( 'Merchant ID', { selector: 'input' } )
			).toBeDefined();
			expect(
				screen.getByLabelText( 'Merchant Key', { selector: 'input' } )
			).toBeDefined();
			expect(
				screen.getByLabelText( 'Passphrase', { selector: 'input' } )
			).toBeDefined();
		} );
	} );

	describe( 'Paystack', () => {
		const params = {
			activePlugins: [],
			countryCode: 'ZA',
			onboardingStatus: {},
			options: [],
			profileItems: { industry: [] },
		};
		const mockInstallStep = {
			isComplete: true,
			key: 'install',
			label: 'Install',
		};
		it( 'Detects the plugin is enabled based on the enabled gateways', () => {
			const paystackParams = {
				...params,
				onboardingStatus: {
					enabledPaymentGateways: [ 'paystack' ],
				},
			};

			const paystackMethod = getPaymentMethods( paystackParams ).find(
				( method ) => method.key === 'paystack'
			);

			expect( paystackMethod.isEnabled ).toBe( true );
		} );

		it( 'is enabled for supported countries', () => {
			[ 'ZA', 'GH', 'NG' ].forEach( ( countryCode ) => {
				const methods = getPaymentMethods( {
					...params,
					countryCode,
				} );

				expect(
					methods.filter( ( method ) => method.key === 'paystack' )
						.length
				).toBe( 1 );
			} );
		} );

		it( 'shows API credential inputs', async () => {
			const payfastMethod = getPaymentMethods( params ).find(
				( method ) => method.key === 'paystack'
			);

			render(
				<GenericPaymentStep
					isRequestingOptions={ false }
					options={ {} }
					installStep={ mockInstallStep }
					methodConfig={ payfastMethod }
				/>
			);

			// Since the oauth response failed, we should have the API credentials form.
			expect(
				await screen.findByText( 'Proceed', { selector: 'button' } )
			).toBeDefined();
			expect(
				screen.getByLabelText( 'Live Public Key', {
					selector: 'input',
				} )
			).toBeDefined();
			expect(
				screen.getByLabelText( 'Live Secret Key', {
					selector: 'input',
				} )
			).toBeDefined();
		} );
	} );
} );
