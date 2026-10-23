/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { recordMarketplaceView } from '../tracking';

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );

describe( 'recordMarketplaceView', () => {
	beforeEach( () => {
		jest.mocked( recordEvent ).mockClear();
	} );

	it( 'records the UTM parameters of a link into the marketplace', () => {
		recordMarketplaceView( {
			view: 'my-subscriptions',
			utm_source: 'pu',
			utm_campaign: 'pu_dashboard_widget_purchase',
		} );

		expect( recordEvent ).toHaveBeenCalledWith(
			'marketplace_view',
			expect.objectContaining( {
				view: 'my-subscriptions',
				utm_source: 'pu',
				utm_campaign: 'pu_dashboard_widget_purchase',
			} )
		);
	} );

	it( 'leaves the UTM parameters out when the URL has none', () => {
		recordMarketplaceView( { view: 'my-subscriptions' } );

		const [ , props ] = jest.mocked( recordEvent ).mock.calls[ 0 ];
		expect( props ).not.toHaveProperty( 'utm_source' );
		expect( props ).not.toHaveProperty( 'utm_campaign' );
	} );
} );
