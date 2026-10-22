/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { trackPluginNoticeLinks } from '../plugin-notice-tracking';

jest.mock( '@woocommerce/tracks', () => ( { recordEvent: jest.fn() } ) );

const updateRow = ( id: string ) =>
	`<tr class="plugin-update-tr" id="${ id }-update"><td><p><a href="#" class="woocommerce-renew-subscription" id="${ id }">Renew</a></p></td></tr>`;

const noticeRow = ( id: string ) =>
	`<tr class="plugin-update-tr" data-plugin-row-type="woo-subscription-notice"><td><p><a href="#" class="woocommerce-renew-subscription" id="${ id }">Renew</a></p></td></tr>`;

const render = ( rows: string ) => {
	document.body.innerHTML = `<table><tbody id="the-list">${ rows }</tbody></table>`;
	trackPluginNoticeLinks(
		'.woocommerce-renew-subscription',
		'woo_renew_subscription_in_plugins'
	);
};

describe( 'trackPluginNoticeLinks', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'records nothing when no link matches', () => {
		render( '' );

		expect( recordEvent ).not.toHaveBeenCalled();
	} );

	it( 'records the shown event without properties for update-row links', () => {
		render( updateRow( 'a' ) + updateRow( 'b' ) );

		expect( recordEvent ).toHaveBeenCalledTimes( 1 );
		expect( recordEvent ).toHaveBeenCalledWith(
			'woo_renew_subscription_in_plugins_shown'
		);
	} );

	it( 'records the shown event with no_update for notice-row links', () => {
		render( noticeRow( 'a' ) + noticeRow( 'b' ) );

		expect( recordEvent ).toHaveBeenCalledTimes( 1 );
		expect( recordEvent ).toHaveBeenCalledWith(
			'woo_renew_subscription_in_plugins_shown',
			{ no_update: true }
		);
	} );

	it( 'records one shown event per placement when both are on the page', () => {
		render( updateRow( 'a' ) + noticeRow( 'b' ) );

		expect( recordEvent ).toHaveBeenCalledTimes( 2 );
		expect( recordEvent ).toHaveBeenCalledWith(
			'woo_renew_subscription_in_plugins_shown'
		);
		expect( recordEvent ).toHaveBeenCalledWith(
			'woo_renew_subscription_in_plugins_shown',
			{ no_update: true }
		);
	} );

	it( 'records clicks with the placement of the clicked link', () => {
		render( updateRow( 'a' ) + noticeRow( 'b' ) );
		jest.clearAllMocks();

		document.getElementById( 'a' )?.click();
		document.getElementById( 'b' )?.click();

		expect( recordEvent ).toHaveBeenCalledTimes( 2 );
		expect( recordEvent ).toHaveBeenNthCalledWith(
			1,
			'woo_renew_subscription_in_plugins_clicked'
		);
		expect( recordEvent ).toHaveBeenNthCalledWith(
			2,
			'woo_renew_subscription_in_plugins_clicked',
			{ no_update: true }
		);
	} );
} );
