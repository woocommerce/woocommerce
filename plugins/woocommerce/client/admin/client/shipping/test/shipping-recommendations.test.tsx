import { beforeEach, describe, expect, it, vi, type Mock } from 'vitest';

/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { speak } from '@wordpress/a11y';
import { useSelect, useDispatch } from '@wordpress/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import ShippingRecommendations from '../shipping-recommendations';
import { SHIPPING_RECOMMENDATIONS_DISMISS_OPTION } from '../shipping-recommendations-utils';
vi.mock( '@wordpress/data', async () => {
	const mock = {
		...( await vi.importActual( '@wordpress/data' ) ),
		useSelect: vi.fn(),
		useDispatch: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '~/components/tracked-link/tracked-link', () => {
	const mock = {
		TrackedLink: ( {
			textProps,
		}: {
			textProps?: {
				className?: string;
			};
		} ) => (
			<span className={ textProps?.className }>
				the WooCommerce Marketplace
			</span>
		),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../../settings-recommendations/dismissable-list', async () => {
	const { DismissableList } = await vi.importActual(
		'../../settings-recommendations/dismissable-list'
	);
	return ( ( mock ) => ( {
		default: mock,
		...mock,
	} ) )( {
		DismissableList: ( {
			children,
			isDismissed,
		}: {
			children: React.ReactNode;
			isDismissed?: boolean;
		} ) => (
			<div
				data-dismissed={ String( Boolean( isDismissed ) ) }
				data-testid="dismissable-list"
			>
				<DismissableList isDismissed={ isDismissed }>
					{ children }
				</DismissableList>
			</div>
		),
		DismissableListHeading: ( {
			children,
		}: {
			children: React.ReactNode;
		} ) => children,
	} );
} );
vi.mock( '~/guided-tours/shipping-tour', () => {
	const mock = {
		ShippingTour: ( {
			showShippingRecommendationsStep,
		}: {
			showShippingRecommendationsStep: boolean;
		} ) => (
			<div
				data-show-recommendations-step={ String(
					showShippingRecommendationsStep
				) }
				data-testid="shipping-tour"
			/>
		),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../woocommerce-shipping-item', () => {
	const mock = () => <div>WooCommerce Shipping</div>;
	return {
		default: mock,
		...mock,
	};
} );
vi.mock( '../shipstation-item', () => {
	const mock = () => <div>ShipStation</div>;
	return {
		default: mock,
		...mock,
	};
} );
vi.mock( '../packlink-item', () => {
	const mock = () => <div>Packlink PRO</div>;
	return {
		default: mock,
		...mock,
	};
} );
vi.mock( '../../lib/notices', () => {
	const mock = {
		createNoticesFromResponse: () => null,
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@woocommerce/tracks', () => {
	const mock = {
		recordEvent: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@wordpress/a11y', () => {
	const mock = {
		speak: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
const defaultSelectReturn = {
	getActivePlugins: () => [],
	getInstalledPlugins: () => [],
	getSettings: () => ( {
		general: {
			woocommerce_default_country: 'US',
		},
	} ),
	getProfileItems: () => ( {} ),
	getOption: vi.fn().mockReturnValue( 'no' ),
	hasFinishedResolution: vi.fn().mockReturnValue( true ),
};
const mockSelect = ( overrides: Record< string, unknown > = {} ) => {
	( useSelect as Mock ).mockImplementation( ( fn ) =>
		fn( () => ( {
			...defaultSelectReturn,
			...overrides,
		} ) )
	);
};
describe( 'ShippingRecommendations', () => {
	beforeEach( () => {
		mockSelect();
		( useDispatch as Mock ).mockReturnValue( {
			installPlugins: () => Promise.resolve(),
			activatePlugins: () => Promise.resolve(),
		} );
		( recordEvent as Mock ).mockClear();
		( speak as Mock ).mockClear();
	} );
	it( 'renders recommendations and the shipping tour recommendations step', () => {
		render( <ShippingRecommendations /> );
		expect(
			screen.queryByText( 'WooCommerce Shipping' )
		).toBeInTheDocument();
		expect( screen.queryByText( 'ShipStation' ) ).toBeInTheDocument();
		expect( screen.getByTestId( 'shipping-tour' ) ).toHaveAttribute(
			'data-show-recommendations-step',
			'true'
		);
		expect( recordEvent ).toHaveBeenCalledWith(
			'shipping_partner_impression',
			{
				context: 'settings',
				country: 'US',
				plugins:
					'woocommerce-shipping,woocommerce-shipstation-integration',
			}
		);
	} );
	it( 'waits for the dismissal option without remounting the shipping tour', () => {
		let hasDismissResolved = false;
		mockSelect( {
			hasFinishedResolution: ( selector: string ) =>
				selector === 'getOption' ? hasDismissResolved : true,
		} );
		const { rerender } = render( <ShippingRecommendations /> );
		const initialShippingTour = screen.getByTestId( 'shipping-tour' );
		expect(
			screen.queryByText( 'the WooCommerce Marketplace' )
		).not.toBeInTheDocument();
		expect(
			screen.queryByTestId( 'dismissable-list' )
		).not.toBeInTheDocument();
		expect( screen.getByTestId( 'shipping-tour' ) ).toHaveAttribute(
			'data-show-recommendations-step',
			'false'
		);
		expect( recordEvent ).not.toHaveBeenCalledWith(
			'shipping_partner_impression',
			expect.anything()
		);
		hasDismissResolved = true;
		rerender( <ShippingRecommendations /> );
		expect( screen.getByTestId( 'shipping-tour' ) ).toBe(
			initialShippingTour
		);
		expect( screen.getByTestId( 'dismissable-list' ) ).toBeInTheDocument();
		expect( recordEvent ).toHaveBeenCalledTimes( 1 );
		expect( recordEvent ).toHaveBeenCalledWith(
			'shipping_partner_impression',
			{
				context: 'settings',
				country: 'US',
				plugins:
					'woocommerce-shipping,woocommerce-shipstation-integration',
			}
		);
	} );
	it( 'does not render recommendations before the country settings resolve', () => {
		mockSelect( {
			getSettings: () => ( {
				general: {},
			} ),
			hasFinishedResolution: ( selector: string ) =>
				selector !== 'getSettings',
		} );
		render( <ShippingRecommendations /> );
		expect(
			screen.queryByText( 'the WooCommerce Marketplace' )
		).not.toBeInTheDocument();
		expect(
			screen.queryByTestId( 'dismissable-list' )
		).not.toBeInTheDocument();
		expect( screen.getByTestId( 'shipping-tour' ) ).toHaveAttribute(
			'data-show-recommendations-step',
			'false'
		);
		expect( recordEvent ).not.toHaveBeenCalledWith(
			'shipping_partner_impression',
			expect.anything()
		);
	} );
	it( 'keeps the dismissal wrapper mounted to restore focus and announce the change', () => {
		let dismissOption = 'no';
		mockSelect( {
			getOption: ( option: string ) =>
				option === SHIPPING_RECOMMENDATIONS_DISMISS_OPTION
					? dismissOption
					: undefined,
		} );
		const { rerender } = render( <ShippingRecommendations /> );
		const dismissalWrapper = document.querySelector(
			'.woocommerce-dismissable-list__wrapper'
		);
		expect( dismissalWrapper ).toBeInTheDocument();
		expect( screen.getByTestId( 'dismissable-list' ) ).toHaveAttribute(
			'data-dismissed',
			'false'
		);
		dismissOption = 'yes';
		rerender( <ShippingRecommendations /> );
		expect(
			document.querySelector( '.woocommerce-dismissable-list__wrapper' )
		).toBe( dismissalWrapper );
		expect( screen.getByTestId( 'dismissable-list' ) ).toHaveAttribute(
			'data-dismissed',
			'true'
		);
		expect( speak ).toHaveBeenCalledWith(
			'Recommendation hidden.',
			'assertive'
		);
		expect( dismissalWrapper ).toHaveFocus();
		expect(
			screen.getByText( 'the WooCommerce Marketplace' )
		).toBeInTheDocument();
	} );
	it( 'does not render recommendations before the product profile resolves', () => {
		mockSelect( {
			hasFinishedResolution: ( selector: string ) =>
				selector !== 'getProfileItems',
		} );
		render( <ShippingRecommendations /> );
		expect(
			screen.queryByText( 'the WooCommerce Marketplace' )
		).not.toBeInTheDocument();
		expect(
			screen.queryByTestId( 'dismissable-list' )
		).not.toBeInTheDocument();
		expect( screen.getByTestId( 'shipping-tour' ) ).toHaveAttribute(
			'data-show-recommendations-step',
			'false'
		);
		expect( recordEvent ).not.toHaveBeenCalledWith(
			'shipping_partner_impression',
			expect.anything()
		);
	} );
	it( 'renders the marketplace fallback when recommendations are dismissed', () => {
		mockSelect( {
			getOption: ( option: string ) =>
				option === SHIPPING_RECOMMENDATIONS_DISMISS_OPTION
					? 'yes'
					: undefined,
		} );
		render( <ShippingRecommendations /> );
		expect(
			screen.queryByText( 'WooCommerce Shipping' )
		).not.toBeInTheDocument();
		expect( screen.queryByText( 'ShipStation' ) ).not.toBeInTheDocument();
		expect(
			screen.queryByText( 'the WooCommerce Marketplace' )
		).toBeInTheDocument();
		expect( screen.getByTestId( 'dismissable-list' ) ).toHaveAttribute(
			'data-dismissed',
			'true'
		);
		expect( speak ).not.toHaveBeenCalled();
		expect( screen.getByTestId( 'shipping-tour' ) ).toHaveAttribute(
			'data-show-recommendations-step',
			'false'
		);
		expect( recordEvent ).not.toHaveBeenCalledWith(
			'shipping_partner_impression',
			expect.anything()
		);
	} );
	it( 'renders the marketplace fallback when there are no country recommendations', () => {
		mockSelect( {
			getSettings: () => ( {
				general: {
					woocommerce_default_country: 'JP',
				},
			} ),
		} );
		render( <ShippingRecommendations /> );
		expect(
			screen.queryByText( 'WooCommerce Shipping' )
		).not.toBeInTheDocument();
		expect(
			screen.queryByText( 'the WooCommerce Marketplace' )
		).toBeInTheDocument();
		expect( screen.getByTestId( 'shipping-tour' ) ).toHaveAttribute(
			'data-show-recommendations-step',
			'false'
		);
	} );
	it( 'renders the marketplace fallback for stores selling digital products only', () => {
		mockSelect( {
			getProfileItems: () => ( {
				product_types: [ 'downloads' ],
			} ),
		} );
		render( <ShippingRecommendations /> );
		expect(
			screen.queryByText( 'WooCommerce Shipping' )
		).not.toBeInTheDocument();
		expect(
			screen.queryByText( 'the WooCommerce Marketplace' )
		).toBeInTheDocument();
		expect( screen.getByTestId( 'shipping-tour' ) ).toHaveAttribute(
			'data-show-recommendations-step',
			'false'
		);
		expect( recordEvent ).not.toHaveBeenCalledWith(
			'shipping_partner_impression',
			expect.anything()
		);
	} );
} );
