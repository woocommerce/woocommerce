/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { useSelect, useDispatch } from '@wordpress/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import ShippingRecommendations from '../shipping-recommendations';
import { SHIPPING_RECOMMENDATIONS_DISMISS_OPTION } from '../shipping-recommendations-utils';

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn(),
	useDispatch: jest.fn(),
} ) );
jest.mock( '~/components/tracked-link/tracked-link', () => ( {
	TrackedLink: ( { textProps }: { textProps?: { className?: string } } ) => (
		<span className={ textProps?.className }>
			the WooCommerce Marketplace
		</span>
	),
} ) );
jest.mock( '../../settings-recommendations/dismissable-list', () => ( {
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
			{ ! isDismissed && children }
		</div>
	),
	DismissableListHeading: ( { children }: { children: React.ReactNode } ) =>
		children,
} ) );
jest.mock( '~/guided-tours/shipping-tour', () => ( {
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
} ) );
jest.mock( '../woocommerce-shipping-item', () => () => (
	<div>WooCommerce Shipping</div>
) );
jest.mock( '../shipstation-item', () => () => <div>ShipStation</div> );
jest.mock( '../packlink-item', () => () => <div>Packlink PRO</div> );
jest.mock( '../../lib/notices', () => ( {
	createNoticesFromResponse: () => null,
} ) );
jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );

const defaultSelectReturn = {
	getActivePlugins: () => [],
	getInstalledPlugins: () => [],
	getSettings: () => ( {
		general: {
			woocommerce_default_country: 'US',
		},
	} ),
	getProfileItems: () => ( {} ),
	getOption: jest.fn().mockReturnValue( 'no' ),
	hasFinishedResolution: jest.fn().mockReturnValue( true ),
};

const mockSelect = ( overrides: Record< string, unknown > = {} ) => {
	( useSelect as jest.Mock ).mockImplementation( ( fn ) =>
		fn( () => ( {
			...defaultSelectReturn,
			...overrides,
		} ) )
	);
};

describe( 'ShippingRecommendations', () => {
	beforeEach( () => {
		mockSelect();
		( useDispatch as jest.Mock ).mockReturnValue( {
			installPlugins: () => Promise.resolve(),
			activatePlugins: () => Promise.resolve(),
		} );
		( recordEvent as jest.Mock ).mockClear();
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
			getSettings: () => ( { general: {} } ),
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
