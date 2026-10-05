import { describe, expect, test, vi, type Mock } from 'vitest';

/**
 * External dependencies
 */
import { render, screen, fireEvent, act } from '@testing-library/react';
import { TaskType } from '@woocommerce/data';
import userEvent from '@testing-library/user-event';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { ShippingRecommendation as _ShippingRecommendation } from '../shipping-recommendation';
import { ShippingRecommendationProps, TaskProps } from '../types';
import { redirectToWCSSettings } from '../utils';
vi.mock( '../../tax/utils', () => {
	const mock = {
		hasCompleteAddress: vi.fn().mockReturnValue( true ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../utils', () => {
	const mock = {
		redirectToWCSSettings: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@woocommerce/components', async () => {
	const originalModule = await vi.importActual( '@woocommerce/components' );
	return ( ( mock ) => ( {
		default: mock,
		...mock,
	} ) )( {
		__esModule: true,
		...originalModule,
		Plugins: vi.fn().mockReturnValue( <div>MockedPlugins</div> ),
		Spinner: vi.fn().mockReturnValue( null ),
	} );
} );
vi.mock( '@wordpress/data', async () => {
	const mock = {
		...( await vi.importActual( '@wordpress/data' ) ),
		useDispatch: vi.fn().mockImplementation( () => {
			return {
				createNotice: vi.fn(),
				installAndActivatePlugins: vi.fn(),
			};
		} ),
		useSelect: vi.fn().mockImplementation( ( fn ) =>
			fn( () => ( {
				getActivePlugins: vi.fn().mockReturnValue( [] ),
				getInstalledPlugins: vi.fn().mockReturnValue( [] ),
				isPluginsRequesting: vi.fn().mockReturnValue( false ),
				getSettings: () => ( {
					general: {
						woocommerce_default_country: 'US',
					},
				} ),
				getCountries: () => [],
				getLocales: () => [],
				getLocale: () => 'en',
				hasFinishedResolution: () => true,
				getOption: ( key: string ) => {
					return {
						wcshipping_options: {
							tos_accepted: true,
						},
						woocommerce_setup_jetpack_opted_in: 1,
					}[ key ];
				},
			} ) )
		),
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
vi.mock( '~/utils/features', () => {
	const mock = {
		isFeatureEnabled: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
const taskProps: TaskProps = {
	onComplete: () => {},
	query: {},
	task: {
		id: 'shipping-recommendation',
	} as TaskType,
};
const ShippingRecommendation = ( props: ShippingRecommendationProps ) => {
	return <_ShippingRecommendation { ...taskProps } { ...props } />;
};
describe( 'ShippingRecommendation', () => {
	test( 'should show plugins step when woocommerce-shipping is not installed and activated', () => {
		const { getByText } = render(
			<ShippingRecommendation
				isJetpackConnected={ false }
				isResolving={ false }
				activePlugins={ [ 'foo' ] }
			/>
		);
		expect( getByText( 'MockedPlugins' ) ).toBeInTheDocument();
	} );
	test( 'should show connect step when WooCommerce Shipping is activated but not yet connected', () => {
		const { getByRole } = render(
			<ShippingRecommendation
				isJetpackConnected={ false }
				isResolving={ false }
				activePlugins={ [ 'woocommerce-shipping' ] }
			/>
		);
		expect(
			getByRole( 'button', {
				name: 'Connect',
			} )
		).toBeInTheDocument();
	} );
	test( 'should show "complete task" button when WooCommerce Shipping is activated and Jetpack is connected', () => {
		const { getByRole } = render(
			<ShippingRecommendation
				isJetpackConnected={ true }
				isResolving={ false }
				activePlugins={ [ 'woocommerce-shipping' ] }
			/>
		);
		expect(
			getByRole( 'button', {
				name: 'Complete task',
			} )
		).toBeInTheDocument();
	} );
	test( 'should automatically be redirected when all steps are completed', () => {
		render(
			<ShippingRecommendation
				isJetpackConnected={ true }
				isResolving={ false }
				activePlugins={ [ 'woocommerce-shipping' ] }
			/>
		);
		expect( redirectToWCSSettings ).toHaveBeenCalled();
	} );
	test( 'should allow location step to be manually navigated', async () => {
		const { getByText } = render(
			<ShippingRecommendation
				isJetpackConnected={ true }
				isResolving={ false }
				activePlugins={ [] }
			/>
		);
		await act( async () => {
			await userEvent.click( getByText( 'Set store location' ) );
		} );
		expect( getByText( 'Address' ) ).toBeInTheDocument();
	} );
	test( 'should trigger event tasklist_shipping_recommendation_visit_marketplace_click when clicking the WooCommerce Marketplace link', () => {
		render( <ShippingRecommendation /> );
		fireEvent.click( screen.getByText( 'the WooCommerce Marketplace' ) );
		expect( recordEvent ).toHaveBeenCalledWith(
			'tasklist_shipping_recommendation_visit_marketplace_click',
			{}
		);
	} );
	test( 'should navigate to the marketplace when clicking the WooCommerce Marketplace link', async () => {
		const { isFeatureEnabled } = await import( '~/utils/features' );
		( isFeatureEnabled as Mock ).mockReturnValue( true );
		const mockLocation = {
			href: 'test',
		} as Location;
		mockLocation.href = 'test';
		Object.defineProperty( global.window, 'location', {
			value: mockLocation,
		} );
		render( <ShippingRecommendation /> );
		fireEvent.click( screen.getByText( 'the WooCommerce Marketplace' ) );
		expect( mockLocation.href ).toContain(
			'admin.php?page=wc-admin&tab=extensions&path=/extensions&category=shipping'
		);
	} );
} );
