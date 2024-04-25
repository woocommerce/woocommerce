/**
 * External dependencies
 */
import { waitFor, render, fireEvent } from '@testing-library/react';
import { useSelect } from '@wordpress/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { PaymentsBannerWrapper } from '../payment-settings-banner';

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn(),
} ) );
jest.mock( '@woocommerce/explat' );
jest.mock( '@woocommerce/tracks', () => ( { recordEvent: jest.fn() } ) );

const paymentsBannerShouldBe = async ( status: 'hidden' | 'visible' ) => {
	const { container } = render( <PaymentsBannerWrapper /> );

	await waitFor( () => {
		container.querySelector( '.wcpay-payments-settings-banner' );
	} );

	const banner = expect(
		container.querySelector( '.wcpay-payments-settings-banner' )
	);

	return status === 'visible'
		? banner.toBeInTheDocument()
		: banner.not.toBeInTheDocument();
};

const whenWcPay = ( {
	supported,
	activated,
	installed,
}: {
	supported: boolean;
	activated: boolean;
	installed: boolean;
} ) => {
	( useSelect as jest.Mock ).mockReturnValue( {
		installedPaymentGateways: [
			installed ? { id: 'woocommerce_payments', enabled: activated } : {},
		],
		paymentGatewaySuggestions: supported
			? [ { id: 'woocommerce_payments:us' } ]
			: [],
		hasFinishedResolution: true,
	} );
};

describe( 'Payment Settings Banner', () => {
	it( 'should render the banner if woocommerce payments is supported but setup not completed', async () => {
		expect.assertions( 1 );

		whenWcPay( { supported: true, activated: false, installed: true } );

		await paymentsBannerShouldBe( 'visible' );
	} );

	it( 'should not render anything if woocommerce payments is not supported', async () => {
		expect.assertions( 1 );

		whenWcPay( { supported: false, activated: false, installed: false } );

		await paymentsBannerShouldBe( 'hidden' );
	} );

	it( 'should not render anything if woocommerce payments is setup', async () => {
		expect.assertions( 1 );

		whenWcPay( { supported: true, activated: true, installed: true } );

		await paymentsBannerShouldBe( 'hidden' );
	} );

	it( 'should record track when clicking the action button', async () => {
		whenWcPay( { supported: true, activated: false, installed: true } );

		const { getByText } = render( <PaymentsBannerWrapper /> );
		fireEvent.click( getByText( 'Finish setup' ) );

		expect( recordEvent ).toHaveBeenCalledWith(
			'settings_payments_banner_connect_click'
		);
	} );
} );
