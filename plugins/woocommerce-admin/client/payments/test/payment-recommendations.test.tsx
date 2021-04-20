/**
 * External dependencies
 */
import { render, fireEvent, waitFor } from '@testing-library/react';
import { useSelect, useDispatch } from '@wordpress/data';
import { recordEvent } from '@woocommerce/tracks';
import {
	PLUGINS_STORE_NAME,
	SETTINGS_STORE_NAME,
	OPTIONS_STORE_NAME,
	WCDataStoreName,
	WPDataSelectors,
	Plugin,
} from '@woocommerce/data';

/**
 * Internal dependencies
 */
import PaymentRecommendations, {
	getPaymentRecommendationData,
} from '../payment-recommendations';
import { isWCPaySupported } from '../../task-list/tasks/payments/methods/wcpay';
import { getAdminLink } from '../../wc-admin-settings';
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
jest.mock( '../../task-list/tasks/payments/methods/wcpay', () => ( {
	isWCPaySupported: jest.fn(),
} ) );

jest.mock( '../../wc-admin-settings', () => ( {
	getAdminLink: jest
		.fn()
		.mockImplementation( ( link: string ) => 'https://test.ca/' + link ),
} ) );
jest.mock( '../../lib/notices', () => ( {
	createNoticesFromResponse: jest.fn().mockImplementation( () => {
		// do nothing
	} ),
} ) );

const storeSelectors: WPDataSelectors = {
	hasStartedResolution: () => true,
	hasFinishedResolution: () => true,
	isResolving: () => false,
};

describe( 'Payment recommendations', () => {
	it( 'should render nothing with no recommendedPlugins and country not defined', () => {
		( useSelect as jest.Mock ).mockReturnValue( {
			displayable: false,
			recommendedPlugins: undefined,
		} );
		const { container } = render( <PaymentRecommendations /> );

		expect( container.firstChild ).toBe( null );
	} );

	describe( 'getPaymentRecommendationData', () => {
		const plugin = {
			title: 'test',
			slug: 'test',
			product: 'test',
		} as Plugin;
		const baseSelectValues = {
			plugins: undefined,
			optionsResolving: false,
			country: undefined,
			optionValues: {},
		};
		const recommendedPluginsMock = jest.fn();
		const createFakeSelect = ( data: {
			optionsResolving?: boolean;
			optionValues: Record< string, boolean | string >;
			country?: string;
			plugins?: Plugin[];
		} ) => {
			return jest
				.fn()
				.mockImplementation( ( storeName: WCDataStoreName ) => {
					switch ( storeName ) {
						case OPTIONS_STORE_NAME:
							return {
								isResolving: () => data.optionsResolving,
								getOption: ( option: string ) =>
									data.optionValues[ option ],
							};
						case SETTINGS_STORE_NAME:
							return {
								getSettings: () => ( {
									general: {
										woocommerce_default_country:
											data.country,
									},
								} ),
							};
						case PLUGINS_STORE_NAME:
							return {
								getRecommendedPlugins: recommendedPluginsMock.mockReturnValue(
									data.plugins
								),
							};
					}
				} );
		};

		it( 'should render nothing if the country is not supported', () => {
			( isWCPaySupported as jest.Mock ).mockReturnValue( false );

			const selectData = getPaymentRecommendationData(
				createFakeSelect( {
					...baseSelectValues,
					country: 'FR',
					plugins: [ plugin ],
				} )
			);

			expect( selectData.displayable ).toBe( false );
		} );

		it( 'should not call getRecommendedPlugins when displayable is false', () => {
			( isWCPaySupported as jest.Mock ).mockReturnValue( false );

			const selectData = getPaymentRecommendationData(
				createFakeSelect( {
					...baseSelectValues,
					country: 'FR',
					plugins: [ plugin ],
				} )
			);

			expect( selectData.displayable ).toBe( false );
			expect( recommendedPluginsMock ).not.toHaveBeenCalled();
		} );

		it( 'should have displayable as true if country is supported', () => {
			( isWCPaySupported as jest.Mock ).mockReturnValue( true );
			const selectData = getPaymentRecommendationData(
				createFakeSelect( {
					...baseSelectValues,
					country: 'US',
					plugins: [ plugin ],
					optionValues: {
						woocommerce_show_marketplace_suggestions: 'yes',
					},
				} )
			);

			expect( selectData.displayable ).toBeTruthy();
		} );

		it( 'should have displayable as false if hidden is set to true', () => {
			( isWCPaySupported as jest.Mock ).mockReturnValue( true );
			const selectData = getPaymentRecommendationData(
				createFakeSelect( {
					...baseSelectValues,
					country: 'US',
					plugins: [ plugin ],
					optionValues: {
						woocommerce_setting_payments_recommendations_hidden:
							'yes',
					},
				} )
			);

			expect( selectData.displayable ).toBeFalsy();
		} );

		it( 'should set displayable to true if isHidden is not defined', () => {
			( isWCPaySupported as jest.Mock ).mockReturnValue( true );
			const selectData = getPaymentRecommendationData(
				createFakeSelect( {
					...baseSelectValues,
					country: 'US',
					plugins: [ plugin ],
					optionValues: {
						woocommerce_setting_payments_recommendations_hidden:
							'no',
						woocommerce_show_marketplace_suggestions: 'yes',
					},
				} )
			);

			expect( selectData.displayable ).toBeTruthy();
		} );

		it( 'have displayable as false if still requesting options', () => {
			( isWCPaySupported as jest.Mock ).mockReturnValue( true );
			const selectData = getPaymentRecommendationData(
				createFakeSelect( {
					...baseSelectValues,
					country: 'US',
					plugins: [ plugin ],
					optionsResolving: true,
				} )
			);

			expect( selectData.displayable ).toBeFalsy();
		} );

		it( 'should not render if showMarketplaceSuggestion is set to "no"', () => {
			( isWCPaySupported as jest.Mock ).mockReturnValue( true );
			const selectData = getPaymentRecommendationData(
				createFakeSelect( {
					...baseSelectValues,
					country: 'US',
					plugins: [ plugin ],
					optionValues: {
						woocommerce_show_marketplace_suggestions: 'no',
					},
				} )
			);

			expect( selectData.displayable ).toBeFalsy();
		} );
	} );

	it( 'should render the list if displayable is true and has recommendedPlugins', () => {
		( isWCPaySupported as jest.Mock ).mockReturnValue( true );
		( useSelect as jest.Mock ).mockReturnValue( {
			displayable: true,
			recommendedPlugins: [ { title: 'test', slug: 'test' } ],
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
			displayable: true,
			recommendedPlugins: [ { title: 'test', slug: 'test' } ],
		} );
		const { container } = render( <PaymentRecommendations /> );

		expect( container.firstChild ).not.toBeNull();
		expect( recordEvent ).toHaveBeenCalledWith(
			'settings_payments_recommendations_pageview',
			{}
		);
	} );

	it( 'should not render if there are no recommendedPlugins', () => {
		( isWCPaySupported as jest.Mock ).mockReturnValue( true );
		( useSelect as jest.Mock ).mockReturnValue( {
			displayable: true,
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
				displayable: true,
				recommendedPlugins: [
					{
						title: 'test',
						slug: 'test',
						product: 'test-product',
						'button-text': 'install',
						'setup-link': '/wp-admin/random-link',
					},
					{
						title: 'another',
						slug: 'another',
						product: 'another-product',
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
					new Promise( ( resolve, reject ) => {
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
