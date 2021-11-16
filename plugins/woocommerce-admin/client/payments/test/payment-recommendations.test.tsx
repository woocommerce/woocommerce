/**
 * External dependencies
 */
import { render, fireEvent, waitFor } from '@testing-library/react';
import { useSelect, useDispatch } from '@wordpress/data';
import { recordEvent } from '@woocommerce/tracks';
import { getAdminLink } from '@woocommerce/wc-admin-settings';

/**
 * Internal dependencies
 */
import PaymentRecommendations from '../payment-recommendations';
import { isWCPaySupported } from '../../tasks/fills/PaymentGatewaySuggestions/components/WCPay';
import { createNoticesFromResponse } from '~/lib/notices';

jest.mock( '@woocommerce/tracks', () => ( { recordEvent: jest.fn() } ) );

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn(),
	useDispatch: jest.fn().mockImplementation( () => ( {
		updateOptions: jest.fn(),
		installAndActivatePlugins: jest.fn(),
	} ) ),
} ) );
jest.mock( '@woocommerce/components', () => ( {
	EllipsisMenu: ( {
		renderContent: Content,
	}: {
		renderContent: React.FunctionComponent;
	} ) => <Content />,
	List: ( {
		items,
	}: {
		items: { key: string; title: string; after?: React.Component }[];
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
} ) );
jest.mock(
	'../../tasks/fills/PaymentGatewaySuggestions/components/WCPay',
	() => ( {
		isWCPaySupported: jest.fn(),
	} )
);

jest.mock( '@woocommerce/wc-admin-settings', () => ( {
	getAdminLink: jest
		.fn()
		.mockImplementation( ( link: string ) => 'https://test.ca/' + link ),
} ) );
jest.mock( '../../lib/notices', () => ( {
	createNoticesFromResponse: jest.fn().mockImplementation( () => {
		// do nothing
	} ),
} ) );

describe( 'Payment recommendations', () => {
	it( 'should render nothing with no recommendedPlugins and country not defined', () => {
		( useSelect as jest.Mock ).mockReturnValue( {
			recommendedPlugins: undefined,
		} );
		const { container } = render( <PaymentRecommendations /> );

		expect( container.firstChild ).toBe( null );
	} );

	it( 'should render the list if displayable is true and has recommendedPlugins', () => {
		( isWCPaySupported as jest.Mock ).mockReturnValue( true );
		( useSelect as jest.Mock ).mockReturnValue( {
			recommendedPlugins: [
				{ title: 'test', id: 'test', plugins: [ 'test' ] },
			],
		} );
		const { container, getByText } = render( <PaymentRecommendations /> );

		expect( container.firstChild ).not.toBeNull();
		expect( getByText( 'test' ) ).toBeInTheDocument();
	} );

	it( 'should not trigger event payments_recommendations_pageview, when it is not rendered', () => {
		( recordEvent as jest.Mock ).mockClear();
		( isWCPaySupported as jest.Mock ).mockReturnValue( true );
		( useSelect as jest.Mock ).mockReturnValue( {
			displayable: false,
		} );
		const { container } = render( <PaymentRecommendations /> );

		expect( container.firstChild ).toBeNull();
		expect( recordEvent ).not.toHaveBeenCalledWith(
			'settings_payments_recommendations_pageview',
			{}
		);
	} );

	it( 'should trigger event payments_recommendations_pageview, when first rendered', () => {
		( isWCPaySupported as jest.Mock ).mockReturnValue( true );
		( useSelect as jest.Mock ).mockReturnValue( {
			recommendedPlugins: [
				{ title: 'test', id: 'test', plugins: [ 'test' ] },
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
		( isWCPaySupported as jest.Mock ).mockReturnValue( true );
		( useSelect as jest.Mock ).mockReturnValue( {
			recommendedPlugins: [
				{ title: 'test', id: 'test', plugins: [ 'test' ] },
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

	it( 'should not render if there are no recommendedPlugins', () => {
		( isWCPaySupported as jest.Mock ).mockReturnValue( true );
		( useSelect as jest.Mock ).mockReturnValue( {
			recommendedPlugins: [],
		} );
		const { container } = render( <PaymentRecommendations /> );

		expect( container.firstChild ).toBeNull();
	} );

	describe( 'interactions', () => {
		let oldLocation: Location;
		const mockLocation = {
			href: 'test',
		} as Location;
		const updateOptionsMock = jest.fn();
		const installAndActivateMock = jest
			.fn()
			.mockImplementation( () => Promise.resolve() );
		beforeEach( () => {
			( isWCPaySupported as jest.Mock ).mockReturnValue( true );
			( useDispatch as jest.Mock ).mockReturnValue( {
				updateOptions: updateOptionsMock,
				installAndActivatePlugins: installAndActivateMock,
			} );
			( useSelect as jest.Mock ).mockReturnValue( {
				recommendedPlugins: [
					{
						title: 'test',
						id: 'test',
						plugins: [ 'test-product' ],
						'button-text': 'install',
						'setup-link': '/wp-admin/random-link',
					},
					{
						title: 'another',
						id: 'another',
						plugins: [ 'another-product' ],
						'button-text': 'install2',
						'setup-link': '/wp-admin/random-link',
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
			Object.defineProperty( global.window, 'location', oldLocation );
		} );

		it( 'should install plugin and trigger event and redirect when finished, when clicking the action button', async () => {
			const { container, getByText } = render(
				<PaymentRecommendations />
			);

			expect( container.firstChild ).not.toBeNull();
			fireEvent.click( getByText( 'install' ) );
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
				expect( getAdminLink ).toHaveBeenCalledWith( 'random-link' );
			} );
			expect( mockLocation.href ).toEqual(
				'https://test.ca/random-link'
			);
		} );

		it( 'should call create notice if install and activate failed', async () => {
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
			fireEvent.click( getByText( 'install' ) );
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
	} );
} );
