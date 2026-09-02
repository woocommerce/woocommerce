/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { useShippingData } from '@woocommerce/base-context/hooks';
import { useCheckoutBlockContext } from '@woocommerce/blocks/checkout/context';
import FrontendBlock from '../frontend';

let mockNeedsShipping = true;

jest.mock( '@woocommerce/block-settings', () => {
	const settings = {
		shippingEnabled: true,
		shippingMethodsExist: true,
	};
	(
		globalThis as typeof globalThis & {
			checkoutShippingMethodTestSettings: typeof settings;
		}
	 ).checkoutShippingMethodTestSettings = settings;

	return {
		...jest.requireActual( '@woocommerce/block-settings' ),
		get SHIPPING_ENABLED() {
			return settings.shippingEnabled;
		},
		get SHIPPING_METHODS_EXIST() {
			return settings.shippingMethodsExist;
		},
		LOCAL_PICKUP_ENABLED: true,
	};
} );

jest.mock( '@woocommerce/settings', () => ( {
	...jest.requireActual( '@woocommerce/settings' ),
	getSetting: jest.fn( ( key, defaultValue ) =>
		key === 'collectableMethodIds' ? [ 'pickup_location' ] : defaultValue
	),
} ) );

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useDispatch: jest.fn(),
	useSelect: jest.fn(),
} ) );

jest.mock( '@woocommerce/base-context/hooks', () => ( {
	useShippingData: jest.fn(),
} ) );

jest.mock( '@woocommerce/blocks/checkout/context', () => ( {
	useCheckoutBlockContext: jest.fn(),
} ) );

const shippingRates = [
	{
		shipping_rates: [
			{
				method_id: 'flat_rate',
				price: '500',
				taxes: '0',
			},
			{
				method_id: 'pickup_location',
				price: '0',
				taxes: '0',
			},
		],
	},
];

const getBlockSettings = () =>
	(
		globalThis as typeof globalThis & {
			checkoutShippingMethodTestSettings: {
				shippingEnabled: boolean;
				shippingMethodsExist: boolean;
			};
		}
	 ).checkoutShippingMethodTestSettings;

const renderFrontendBlock = () =>
	render(
		<FrontendBlock
			title="Delivery"
			description=""
			showPrice={ false }
			showIcon={ false }
			shippingText="Ship"
			localPickupText="Pickup"
		>
			<div />
		</FrontendBlock>
	);

describe( 'Checkout shipping method FrontendBlock', () => {
	beforeEach( () => {
		getBlockSettings().shippingEnabled = true;
		getBlockSettings().shippingMethodsExist = true;
		mockNeedsShipping = true;

		( useCheckoutBlockContext as jest.Mock ).mockReturnValue( {
			showFormStepNumbers: false,
		} );
		( useShippingData as jest.Mock ).mockImplementation( () => ( {
			needsShipping: mockNeedsShipping,
			isCollectable: true,
			shippingRates,
		} ) );
		( useSelect as jest.Mock ).mockImplementation( ( mapSelect ) =>
			mapSelect( () => ( {
				isProcessing: () => false,
				prefersCollection: () => false,
				getShippingRates: () => shippingRates,
			} ) )
		);
		( useDispatch as jest.Mock ).mockReturnValue( {
			setPrefersCollection: jest.fn(),
			selectShippingRate: jest.fn(),
			setValidationErrors: jest.fn(),
			clearValidationError: jest.fn(),
		} );
	} );

	it( 'renders the Ship and Pickup choices when every topology guard passes', () => {
		renderFrontendBlock();

		expect(
			screen.getByRole( 'radiogroup', { name: 'Shipping method' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'radio', { name: 'Ship' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'radio', { name: 'Pickup' } )
		).toBeInTheDocument();
	} );

	it.each( [
		[
			'ordinary shipping methods do not exist',
			() => {
				getBlockSettings().shippingMethodsExist = false;
			},
		],
		[
			'store shipping is disabled',
			() => {
				getBlockSettings().shippingEnabled = false;
			},
		],
		[
			'the cart does not need shipping',
			() => {
				mockNeedsShipping = false;
			},
		],
	] )( 'does not render when %s', ( _, disableGuard ) => {
		disableGuard();

		renderFrontendBlock();

		expect(
			screen.queryByRole( 'radiogroup', { name: 'Shipping method' } )
		).not.toBeInTheDocument();
	} );
} );
