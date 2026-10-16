/**
 * @jest-environment jest-fixed-jsdom
 */

const $ = require( 'jquery' );

describe( 'classic cart fragments widget visibility', () => {
	const widget = () => document.getElementById( 'cart-widget' );

	beforeAll( () => {
		global.jQuery = $;
		global.Cookies = require( '../../js-cookie/js.cookie' );
	} );

	afterAll( () => {
		delete global.Cookies;
	} );

	const load = async ( itemsInCart ) => {
		document.cookie = itemsInCart
			? 'woocommerce_items_in_cart=1; path=/'
			: 'woocommerce_items_in_cart=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
		document.body.innerHTML = `<div id="cart-widget" class="widget_shopping_cart">
			<div class="hide_cart_widget_if_empty"><div class="widget_shopping_cart_content"></div></div></div>`;
		global.wc_cart_fragments_params = {
			ajax_url: '/wp-admin/admin-ajax.php',
			wc_ajax_url: '/?wc-ajax=%%endpoint%%',
			cart_hash_key: 'wc_cart_hash_test',
			fragment_name: 'wc_fragments_test',
			request_timeout: '5000',
		};
		jest.spyOn( $, 'ajax' ).mockImplementation( () => {} );
		jest.isolateModules( () => require( '../cart-fragments' ) );
		await new Promise( ( resolve ) => $( resolve ) );
	};

	afterEach( () => {
		$( document.body ).off();
		jest.restoreAllMocks();
		delete global.wc_cart_fragments_params;
		document.body.innerHTML = '';
		window.sessionStorage.clear();
		window.localStorage.clear();
	} );

	test( 'hides an empty-cart widget again when an addition fails', async () => {
		await load( false );
		expect( widget().style.display ).toBe( 'none' );
		$( document.body ).trigger( 'adding_to_cart', [ $(), {} ] );
		expect( widget().style.display ).not.toBe( 'none' );
		$( document.body ).trigger( 'wc_add_to_cart_failed', [ $(), { error: true } ] );
		expect( widget().style.display ).toBe( 'none' );
	} );

	test( 'keeps the widget visible after a failure when the cart has items', async () => {
		await load( true );
		$( document.body ).trigger( 'adding_to_cart', [ $(), {} ] );
		$( document.body ).trigger( 'wc_add_to_cart_failed', [ $(), { error: true } ] );
		expect( widget().style.display ).not.toBe( 'none' );
	} );
} );
