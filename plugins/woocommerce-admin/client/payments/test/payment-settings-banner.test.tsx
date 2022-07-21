/**
 * External dependencies
 */
import { waitFor, render } from '@testing-library/react';
import { useSelect } from '@wordpress/data';
import { loadExperimentAssignment } from '@woocommerce/explat';

/**
 * Internal dependencies
 */
import { PaymentsBannerWrapper } from '../payment-settings-banner';

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn(),
} ) );
jest.mock( '@woocommerce/explat' );

const whenExperimentAssigned = (
	experimentVariation: 'treatment' | 'control'
) =>
	( loadExperimentAssignment as jest.Mock ).mockImplementation( () => {
		return Promise.resolve( { variationName: experimentVariation } );
	} );

const paymentsBannerShouldBe = async ( status: 'hidden' | 'visible' ) => {
	const { container } = render( <PaymentsBannerWrapper /> );

	await waitFor( () => {
		container.querySelector( '.woocommerce-recommended-payments-banner' );
	} );

	const banner = expect(
		container.querySelector( '.woocommerce-recommended-payments-banner' )
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

		whenExperimentAssigned( 'treatment' );

		await paymentsBannerShouldBe( 'visible' );
	} );

	it( 'should not render the banner if treatment is control', async () => {
		expect.assertions( 1 );

		whenWcPay( { supported: true, activated: false, installed: true } );

		whenExperimentAssigned( 'control' );

		await paymentsBannerShouldBe( 'hidden' );
	} );

	it( 'should not render anything if woocommerce payments is not supported', async () => {
		expect.assertions( 1 );

		whenWcPay( { supported: false, activated: false, installed: false } );

		whenExperimentAssigned( 'treatment' );

		await paymentsBannerShouldBe( 'hidden' );
	} );

	it( 'should not render anything if woocommerce payments is setup', async () => {
		expect.assertions( 1 );

		whenWcPay( { supported: true, activated: true, installed: true } );

		whenExperimentAssigned( 'treatment' );

		await paymentsBannerShouldBe( 'hidden' );
	} );
} );
