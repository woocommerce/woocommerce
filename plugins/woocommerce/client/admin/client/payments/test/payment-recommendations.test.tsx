import {
	afterEach,
	beforeEach,
	describe,
	expect,
	it,
	vi,
	type Mock,
} from 'vitest';

/**
 * External dependencies
 */
import {
	render,
	fireEvent,
	waitFor,
	screen,
	act,
} from '@testing-library/react';
import { useSelect, useDispatch } from '@wordpress/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import PaymentRecommendations from '../payment-recommendations';
import { isWCPaySupported } from '../../task-lists/fills/PaymentGatewaySuggestions/components/WCPay';
import { createNoticesFromResponse } from '../../lib/notices';
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
vi.mock( '@wordpress/data', async () => {
	const mock = {
		...( await vi.importActual( '@wordpress/data' ) ),
		useSelect: vi.fn(),
		useDispatch: vi.fn().mockImplementation( () => ( {
			updateOptions: vi.fn(),
			installAndActivatePlugins: vi.fn(),
		} ) ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@woocommerce/components', () => {
	const mock = {
		EllipsisMenu: ( {
			renderContent: Content,
		}: {
			renderContent: React.FunctionComponent;
		} ) => <Content />,
		List: ( {
			items,
		}: {
			items: {
				key: string;
				title: string;
				after?: React.Component;
			}[];
		} ) => (
			<div>
				{ items.map( ( item ) => (
					<div key={ item.key }>
						<span>{ item.title }</span>
						{ item.after }
					</div>
				) ) }
			</div>
		),
		Link: ( {
			children,
			...props
		}: {
			children: React.ReactNode;
			href: string;
			onClick?: () => void;
			type?: string;
		} ) => <a { ...props }>{ children }</a>,
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock(
	'../../task-lists/fills/PaymentGatewaySuggestions/components/WCPay',
	() => {
		const mock = {
			isWCPaySupported: vi.fn(),
		};
		return Object.defineProperties(
			{
				default: mock,
			},
			Object.getOwnPropertyDescriptors( mock )
		);
	}
);
vi.mock( '../../lib/notices', () => {
	const mock = {
		createNoticesFromResponse: vi.fn().mockImplementation( () => {
			// do nothing
		} ),
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
declare global {
	interface Window {
		wcAdminFeatures: Record< string, boolean >;
	}
}
describe( 'Payment recommendations', () => {
	it( 'should render nothing with no paymentGatewaySuggestions and country not defined', () => {
		( useSelect as Mock ).mockReturnValue( {
			installedPaymentGateways: {},
			paymentGatewaySuggestions: undefined,
		} );
		const { container } = render( <PaymentRecommendations /> );
		expect( container.firstChild ).toBe( null );
	} );
	it( 'should render the list if displayable is true and has paymentGatewaySuggestions', () => {
		( isWCPaySupported as Mock ).mockReturnValue( true );
		( useSelect as Mock ).mockReturnValue( {
			installedPaymentGateways: {},
			paymentGatewaySuggestions: [
				{
					title: 'test',
					id: 'test',
					plugins: [ 'test' ],
				},
			],
		} );
		const { container, getByText } = render( <PaymentRecommendations /> );
		expect( container.firstChild ).not.toBeNull();
		expect( getByText( 'test' ) ).toBeInTheDocument();
	} );
	it( 'should not trigger event payments_recommendations_pageview, when it is not rendered', () => {
		( recordEvent as Mock ).mockClear();
		( isWCPaySupported as Mock ).mockReturnValue( true );
		( useSelect as Mock ).mockReturnValue( {
			displayable: false,
			installedPaymentGateways: {},
		} );
		const { container } = render( <PaymentRecommendations /> );
		expect( container.firstChild ).toBeNull();
		expect( recordEvent ).not.toHaveBeenCalledWith(
			'settings_payments_recommendations_pageview',
			{}
		);
	} );
	it( 'should trigger event payments_recommendations_pageview, when first rendered', () => {
		( isWCPaySupported as Mock ).mockReturnValue( true );
		( useSelect as Mock ).mockReturnValue( {
			installedPaymentGateways: {},
			paymentGatewaySuggestions: [
				{
					title: 'test',
					id: 'test',
					plugins: [ 'test' ],
				},
			],
		} );
		const { container } = render( <PaymentRecommendations /> );
		expect( container.firstChild ).not.toBeNull();
		expect( recordEvent ).toHaveBeenCalledWith(
			'settings_payments_recommendations_pageview',
			{
				test_displayed: true,
				woocommerce_payments_displayed: false,
			}
		);
	} );
	it( 'should set woocommerce-payments-displayed prop to true if pre install wc pay promotion gateway is displayed', () => {
		( isWCPaySupported as Mock ).mockReturnValue( true );
		( useSelect as Mock ).mockReturnValue( {
			installedPaymentGateways: {},
			paymentGatewaySuggestions: [
				{
					title: 'test',
					id: 'test',
					plugins: [ 'test' ],
				},
			],
		} );
		const { container } = render(
			<div>
				<div data-gateway_id="pre_install_woocommerce_payments_promotion"></div>
				<PaymentRecommendations />
			</div>
		);
		expect( container.firstChild ).not.toBeNull();
		expect( recordEvent ).toHaveBeenCalledWith(
			'settings_payments_recommendations_pageview',
			{
				test_displayed: true,
				woocommerce_payments_displayed: false,
			}
		);
	} );
	it( 'should not render if there are no paymentGatewaySuggestions', () => {
		( isWCPaySupported as Mock ).mockReturnValue( true );
		( useSelect as Mock ).mockReturnValue( {
			installedPaymentGateways: {},
			paymentGatewaySuggestions: [],
		} );
		const { container } = render( <PaymentRecommendations /> );
		expect( container.firstChild ).toBeNull();
	} );
	it( 'should trigger event settings_payment_recommendations_visit_marketplace_click when clicking the WooCommerce Marketplace link', () => {
		( isWCPaySupported as Mock ).mockReturnValue( true );
		( useSelect as Mock ).mockReturnValue( {
			installedPaymentGateways: {},
			paymentGatewaySuggestions: [
				{
					title: 'test',
					id: 'test',
					plugins: [ 'test' ],
				},
			],
		} );
		const { container } = render( <PaymentRecommendations /> );
		expect( container.firstChild ).not.toBeNull();
		fireEvent.click( screen.getByText( 'the WooCommerce Marketplace' ) );
		expect( recordEvent ).toHaveBeenCalledWith(
			'settings_payment_recommendations_visit_marketplace_click',
			{}
		);
	} );
	describe( 'interactions', () => {
		let oldLocation: Location;
		const mockLocation = {
			href: 'test',
		} as Location;
		const updateOptionsMock = vi.fn();
		const installAndActivateMock = vi
			.fn()
			.mockImplementation( () => Promise.resolve() );
		beforeEach( () => {
			( isWCPaySupported as Mock ).mockReturnValue( true );
			( useDispatch as Mock ).mockReturnValue( {
				updateOptions: updateOptionsMock,
				installAndActivatePlugins: installAndActivateMock,
			} );
			( useSelect as Mock ).mockReturnValue( {
				installedPaymentGateways: {},
				installedPaymentGateway: {
					settings_url: 'https://test.ca/random-link',
				},
				paymentGatewaySuggestions: [
					{
						title: 'test',
						id: 'test',
						plugins: [ 'test-product' ],
						actionText: 'install',
					},
					{
						title: 'another',
						id: 'another',
						plugins: [ 'another-product' ],
						actionText: 'install2',
					},
				],
			} );
			oldLocation = global.window.location;
			mockLocation.href = 'test';
			Object.defineProperty( global.window, 'location', {
				value: mockLocation,
			} );
		} );
		afterEach( () => {
			if ( oldLocation !== undefined ) {
				Object.defineProperty( global.window, 'location', oldLocation );
			}
		} );
		it( 'should install plugin and trigger event and redirect when finished, when clicking the action button', async () => {
			const { container, getByText } = render(
				<PaymentRecommendations />
			);
			expect( container.firstChild ).not.toBeNull();
			await act( async () => {
				fireEvent.click( getByText( 'install' ) );
			} );
			expect( installAndActivateMock ).toHaveBeenCalledWith( [
				'test-product',
			] );
			expect( recordEvent ).toHaveBeenCalledWith(
				'settings_payments_recommendations_setup',
				{
					extension_selected: 'test-product',
				}
			);
			expect( mockLocation.href ).toEqual(
				'https://test.ca/random-link'
			);
		} );
		it( 'should call create notice if install and activate failed', async () => {
			( useSelect as Mock ).mockReturnValue( {
				installedPaymentGateway: false,
				installedPaymentGateways: {},
				paymentGatewaySuggestions: [
					{
						title: 'test',
						id: 'test',
						plugins: [ 'test-product' ],
						actionText: 'install',
					},
				],
			} );
			installAndActivateMock.mockClear();
			installAndActivateMock.mockImplementation(
				() =>
					new Promise( () => {
						throw {
							code: 500,
							message: 'failed to install plugin',
						};
					} )
			);
			const { container, getByText } = render(
				<PaymentRecommendations />
			);
			expect( container.firstChild ).not.toBeNull();
			await act( async () => {
				fireEvent.click( getByText( 'install' ) );
			} );
			expect( installAndActivateMock ).toHaveBeenCalledWith( [
				'test-product',
			] );
			expect( recordEvent ).toHaveBeenCalledWith(
				'settings_payments_recommendations_setup',
				{
					extension_selected: 'test-product',
				}
			);
			await waitFor( () => {
				expect( createNoticesFromResponse ).toHaveBeenCalled();
			} );
			expect( mockLocation.href ).toEqual( 'test' );
		} );
		it( 'should only show gateways that have not been installed', async () => {
			( useSelect as Mock ).mockReturnValue( {
				installedPaymentGateway: false,
				installedPaymentGateways: {
					test: true,
				},
				paymentGatewaySuggestions: [
					{
						title: 'test',
						id: 'test',
						plugins: [ 'test-product' ],
						actionText: 'install',
					},
					{
						title: 'another',
						id: 'another',
						plugins: [ 'another-product' ],
						actionText: 'install2',
					},
				],
			} );
			const { queryByText } = render( <PaymentRecommendations /> );
			expect( queryByText( 'test' ) ).not.toBeInTheDocument();
			expect( queryByText( 'another' ) ).toBeInTheDocument();
		} );
		it( 'should navigate to the marketplace when clicking the WooCommerce Marketplace link', async () => {
			const { isFeatureEnabled } = await import( '~/utils/features' );
			( isFeatureEnabled as Mock ).mockReturnValue( true );
			const { container, getByText } = render(
				<PaymentRecommendations />
			);
			expect( container.firstChild ).not.toBeNull();
			fireEvent.click( getByText( 'the WooCommerce Marketplace' ) );
			expect( mockLocation.href ).toContain(
				'admin.php?page=wc-admin&tab=extensions&path=/extensions&category=payment-gateways'
			);
		} );
	} );
} );
