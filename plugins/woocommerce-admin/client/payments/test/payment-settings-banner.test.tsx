/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { useSelect } from '@wordpress/data';
import { useExperiment } from '@woocommerce/explat';

/**
 * Internal dependencies
 */
import { PaymentsBannerWrapper } from '../payment-settings-banner';

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn(),
} ) );
jest.mock( '@woocommerce/explat' );

describe( 'Payment Settings Banner', () => {
	it( 'should render the banner if woocommerce payments is supported but setup not completed', () => {
		( useSelect as jest.Mock ).mockReturnValue( {
			installedPaymentGateways: [
				{ id: 'woocommerce_payments', enabled: false },
			],
			paymentGatewaySuggestions: [ { id: 'woocommerce_payments:us' } ],
		} );

		( useExperiment as jest.Mock ).mockImplementation( () => [
			false,
			{ variationName: 'treatment' },
		] );

		const { container } = render( <PaymentsBannerWrapper /> );
		expect(
			container.querySelector(
				'.woocommerce-recommended-payments-banner'
			)
		).toBeInTheDocument();
	} );

	it( 'should not render the banner if treatment is control', () => {
		( useSelect as jest.Mock ).mockReturnValue( {
			installedPaymentGateways: [
				{ id: 'woocommerce_payments', enabled: false },
			],
			paymentGatewaySuggestions: [ { id: 'woocommerce_payments:us' } ],
		} );

		( useExperiment as jest.Mock ).mockImplementation( () => [
			false,
			{ variationName: 'control' },
		] );

		const { container } = render( <PaymentsBannerWrapper /> );
		expect(
			container.querySelector(
				'.woocommerce-recommended-payments-banner'
			)
		).not.toBeInTheDocument();
	} );

	it( 'should not render anything if woocommerce payments is not supported', () => {
		( useSelect as jest.Mock ).mockReturnValue( {
			installedPaymentGateways: [],
			paymentGatewaySuggestions: [],
		} );

		const { container } = render( <PaymentsBannerWrapper /> );
		expect(
			container.querySelector(
				'.woocommerce-recommended-payments-banner'
			)
		).not.toBeInTheDocument();
	} );

	it( 'should not render anything if woocommerce payments is setup', () => {
		( useSelect as jest.Mock ).mockReturnValue( {
			installedPaymentGateways: [
				{ id: 'woocommerce_payments', enabled: true },
			],
			paymentGatewaySuggestions: [ { id: 'woocommerce_payments:us' } ],
		} );

		const { container } = render( <PaymentsBannerWrapper /> );
		expect(
			container.querySelector(
				'.woocommerce-recommended-payments-banner'
			)
		).not.toBeInTheDocument();
	} );
} );
