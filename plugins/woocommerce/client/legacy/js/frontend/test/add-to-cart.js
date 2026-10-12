/**
 * @jest-environment jest-fixed-jsdom
 */

const $ = require( 'jquery' );

const button = ( id = 'first', productId = '7' ) =>
	`<a id="${ id }" href="/?add-to-cart=${ productId }" role="button" class="add_to_cart_button ajax_add_to_cart"
		data-product_id="${ productId }">Add to cart</a>`;
// Rendered notices start with a text node, like the server's wc_print_notices() output.
const messages =
	'\n<ul class="woocommerce-error" role="alert"><li>Cannot add this item.</li></ul>\n';

describe( 'classic AJAX add to cart', () => {
	let requests;
	let addedEvents;
	let originalUrl;
	let originalFxOff;

	beforeAll( () => {
		global.jQuery = $;
		require( '../../jquery-blockui/jquery.blockUI' );
		originalFxOff = $.fx.off;
		$.fx.off = true;
	} );

	afterAll( () => {
		$.fx.off = originalFxOff;
	} );

	beforeEach( async () => {
		requests = [];
		addedEvents = [];
		originalUrl = window.location.href;
		document.body.className = 'woocommerce';
		document.body.innerHTML = `<main><div class="woocommerce-notices-wrapper"></div>
			<ul class="products"><li>${ button() }</li></ul></main>`;
		global.wc_add_to_cart_params = {
			wc_ajax_url: '/?wc-ajax=%%endpoint%%',
			cart_redirect_after_add: 'no',
			cart_url: '/cart/',
			i18n_view_cart: 'View cart',
			is_cart: false,
		};
		jest.spyOn( $, 'ajax' ).mockImplementation( ( request ) => {
			requests.push( request );
		} );
		jest.isolateModules( () => require( '../add-to-cart' ) );
		await new Promise( ( resolve ) => $( resolve ) );
		$( document.body ).on( 'added_to_cart.test', ( _event, ...args ) => {
			addedEvents.push( args );
		} );
	} );

	afterEach( () => {
		$( document.body ).off();
		jest.restoreAllMocks();
		delete global.wc_add_to_cart_params;
		document.body.innerHTML = '';
		document.body.className = '';
	} );

	const click = ( id = 'first' ) => {
		const element = document.getElementById( id );
		element.click();
		return $( element );
	};

	const respond = ( response, index = 0 ) => {
		requests[ index ].success( response );
		requests[ index ].complete();
	};

	const expectFailure = ( $button ) => {
		expect( $button.hasClass( 'loading' ) ).toBe( false );
		expect( $button.hasClass( 'added' ) ).toBe( false );
		expect( $( '.added_to_cart' ) ).toHaveLength( 0 );
		expect( addedEvents ).toHaveLength( 0 );
		expect( window.location.href ).toBe( originalUrl );
	};

	test( 'advertises notice capability before the extension event', () => {
		let eventData;
		$( document.body ).on( 'adding_to_cart.test', ( _event, _$button, data ) => {
			eventData = Object.assign( {}, data );
		} );
		click();
		expect( eventData.return_notices ).toBe( true );
		expect( requests[ 0 ].data.return_notices ).toBe( true );
		expect( requests[ 0 ].data.product_id ).toBe( '7' );
	} );

	test( 'allows extensions to opt out of returned notices', () => {
		$( document.body ).on( 'adding_to_cart.test', ( _event, _$button, data ) => {
			data.return_notices = false;
		} );
		click();
		expect( requests[ 0 ].data.return_notices ).toBe( false );
	} );

	test.each( [ 'no', 'yes' ] )( 'shows inline errors without success or cart redirect (%s)', ( redirect ) => {
		global.wc_add_to_cart_params.cart_redirect_after_add = redirect;
		const $button = click();
		respond( { error: true, product_url: '/product/bag/', messages } );
		expectFailure( $button );
		expect( $( 'main [role="alert"]' ).text() ).toBe( 'Cannot add this item.' );
	} );

	test.each( [ 'no', 'yes' ] )( 'keeps an empty negotiated error on the page (%s)', ( redirect ) => {
		global.wc_add_to_cart_params.cart_redirect_after_add = redirect;
		const $button = click();
		respond( { error: true, product_url: '/product/bag/', messages: '' } );
		expectFailure( $button );
		expect( $( '[role="alert"]' ) ).toHaveLength( 0 );
	} );

	test.each( [
		[ 'inline', { error: true, product_url: '/product/bag/', messages } ],
		[ 'empty inline', { error: true, product_url: '/product/bag/', messages: '' } ],
		[ 'URL-less', { error: true, product_url: false } ],
	] )( 'fires wc_add_to_cart_failed once for a failure (%s)', ( _label, response ) => {
		const failures = [];
		$( document.body ).on( 'wc_add_to_cart_failed.test', ( _event, $failed, failedResponse ) => {
			failures.push( [ $failed, failedResponse, $failed.hasClass( 'loading' ), $( '.woocommerce-error' ).length ] );
		} );
		const $button = click();
		respond( response );
		expect( failures ).toEqual( [ [ $button, response, false, response.messages ? 1 : 0 ] ] );
		expectFailure( $button );
	} );

	test( 'fires wc_add_to_cart_failed before following a legacy error redirect', () => {
		let hashWhenFired;
		$( document.body ).on( 'wc_add_to_cart_failed.test', () => {
			hashWhenFired = window.location.hash;
		} );
		click();
		respond( { error: true, product_url: '#product-page' } );
		expect( hashWhenFired ).toBe( '' );
		expect( window.location.hash ).toBe( '#product-page' );
		window.location.hash = '';
	} );

	test( 'does not fire wc_add_to_cart_failed for a successful addition', () => {
		const failed = jest.fn();
		$( document.body ).on( 'wc_add_to_cart_failed.test', failed );
		click();
		respond( { fragments: {}, cart_hash: 'cart-hash' } );
		expect( failed ).not.toHaveBeenCalled();
		expect( addedEvents ).toHaveLength( 1 );
	} );

	test( 'uses the nearest explicit WooCommerce container without replacing widget notices', () => {
		document.body.innerHTML = `<main><div class="woocommerce"><div class="woocommerce-notices-wrapper" id="local"></div>
			<div class="woocommerce widget"><div class="woocommerce-notices-wrapper" id="widget">Widget notice</div></div>
			<ul class="products"><li>${ button() }</li></ul></div></main>`;
		const $button = click();
		respond( { error: true, messages } );
		expectFailure( $button );
		expect( $( '#local [role="alert"]' ).text() ).toBe( 'Cannot add this item.' );
		expect( $( '#widget' ).text() ).toBe( 'Widget notice' );
	} );

	test.each( [ 'local', 'owned' ] )( 'clears the prior %s error on an empty inline response, keeping unrelated alerts', ( mode ) => {
		document.body.innerHTML = `<aside><div id="unrelated" class="woocommerce-notices-wrapper">
			<p role="alert">Earlier widget alert.</p></div></aside><main>
			${ mode === 'local' ? '<div class="woocommerce-notices-wrapper"></div>' : '' }
			<ul class="products"><li>${ button() }</li></ul>
			${ mode === 'owned' ? '<ul class="products"><li>Another loop</li></ul>' : '' }</main>`;
		const $button = click();
		respond( { error: true, messages } );
		expect( $( '.products' ).first().prev().find( '[role="alert"]' ).text() ).toBe( 'Cannot add this item.' );
		click();
		respond( { error: true, product_url: '/product/bag/', messages: '' }, 1 );
		expectFailure( $button );
		expect( $( 'main [role="alert"]' ) ).toHaveLength( 0 );
		expect( $( 'main .woocommerce-notices-wrapper' ) ).toHaveLength( mode === 'local' ? 1 : 0 );
		expect( $( '#unrelated [role="alert"]' ).text() ).toBe( 'Earlier widget alert.' );
	} );

	test( 'does not create an empty fallback when an inline response has no messages', () => {
		document.body.innerHTML = `<section>${ button() }</section>`;
		const $button = click();
		respond( { error: true, messages: '' } );
		expectFailure( $button );
		expect( $( '.woocommerce-notices-wrapper' ) ).toHaveLength( 0 );
	} );

	test.each( [ 'local', 'owned' ] )( 'retires the error when the same button succeeds on retry (%s wrapper)', ( mode ) => {
		if ( mode === 'owned' ) {
			$( '.woocommerce-notices-wrapper' ).remove();
		} else {
			$( '.woocommerce-notices-wrapper' ).append( '<p id="store-notice" role="alert">Store notice.</p>' );
		}
		const $button = click();
		respond( { error: true, messages } );
		expect( $( '.woocommerce-error' ) ).toHaveLength( 1 );
		$( document.body ).append( '<span id="cart-count">0</span>' );
		const fragments = { '#cart-count': '<span id="cart-count">1</span>' };
		click();
		expect( requests[ 1 ].data ).toEqual( { product_id: '7', return_notices: true } );
		respond( { fragments, cart_hash: 'cart-hash' }, 1 );
		expect( $( '.woocommerce-error' ) ).toHaveLength( 0 );
		expect( $( 'main .woocommerce-notices-wrapper' ) ).toHaveLength( mode === 'local' ? 1 : 0 );
		expect( $( '#store-notice' ) ).toHaveLength( mode === 'local' ? 1 : 0 );
		expect( $button.hasClass( 'loading' ) ).toBe( false );
		expect( $button.hasClass( 'added' ) ).toBe( true );
		expect( $button.next().is( 'a.added_to_cart' ) ).toBe( true );
		expect( $( '#cart-count' ).text() ).toBe( '1' );
		expect( addedEvents ).toEqual( [ [ fragments, 'cart-hash', $button ] ] );
		expect( window.location.href ).toBe( originalUrl );
	} );

	test( 'retains the latest failed product feedback when another button in the same loop succeeds', () => {
		$( '.products' ).append( `<li>${ button( 'second', '8' ) }</li>` );
		const $first = click();
		respond( { error: true, messages } );
		const $second = click( 'second' );
		respond( { error: true, messages: messages.replace( 'Cannot add this item.', 'Another item is unavailable.' ) }, 1 );
		click();
		respond( { error: true, messages }, 2 );
		expect( $( 'main .woocommerce-error' ) ).toHaveLength( 1 );
		expect( $( 'main .woocommerce-error' ).text() ).toBe( 'Cannot add this item.' );
		click( 'second' );
		respond( { fragments: {}, cart_hash: 'other-cart' }, 3 );
		expect( $( 'main .woocommerce-error' ).text() ).toBe( 'Cannot add this item.' );
		click( 'second' );
		respond( { error: true, messages: messages.replace( 'Cannot add this item.', 'Another item is unavailable.' ) }, 4 );
		click();
		respond( { fragments: {}, cart_hash: 'first-cart' }, 5 );
		expect( $( 'main .woocommerce-error' ).text() ).toBe( 'Another item is unavailable.' );
		expect( $first.hasClass( 'added' ) ).toBe( true );
		expect( $second.hasClass( 'added' ) ).toBe( false );
		click( 'second' );
		respond( { fragments: {}, cart_hash: 'second-cart' }, 6 );
		expect( $( 'main .woocommerce-error' ) ).toHaveLength( 0 );
		expect( addedEvents ).toEqual( [
			[ {}, 'other-cart', $second ],
			[ {}, 'first-cart', $first ],
			[ {}, 'second-cart', $second ],
		] );
	} );

	test( 'keeps only the latest error across product lists, and another list\'s success keeps it', () => {
		document.body.innerHTML = `<main><ul id="first-loop" class="products"><li>${ button() }</li></ul>
			<ul id="second-loop" class="products"><li>${ button( 'second', '8' ) }</li></ul></main>`;
		const $first = click();
		respond( { error: true, messages } );
		expect( $( '#first-loop' ).prev().find( '[role="alert"]' ).text() ).toBe( 'Cannot add this item.' );
		click( 'second' );
		respond( { error: true, messages: messages.replace( 'Cannot add this item.', 'Another item is unavailable.' ) }, 1 );
		expect( $( '#first-loop' ).prev().is( '.woocommerce-notices-wrapper' ) ).toBe( false );
		expect( $( '#second-loop' ).prev().find( '[role="alert"]' ).text() ).toBe( 'Another item is unavailable.' );
		click();
		respond( { fragments: {}, cart_hash: 'first-cart' }, 2 );
		expect( $( '.woocommerce-notices-wrapper' ) ).toHaveLength( 1 );
		expect( $( '#second-loop' ).prev().find( '[role="alert"]' ).text() ).toBe( 'Another item is unavailable.' );
		expect( addedEvents ).toEqual( [ [ {}, 'first-cart', $first ] ] );
	} );

	test( 'preserves pre-existing local notices through error replacement and an empty inline response', () => {
		$( 'main .woocommerce-notices-wrapper' ).append( '<p id="store-notice" role="alert">Store notice.</p>' );
		click();
		respond( { error: true, messages } );
		expect( $( '#store-notice' ).text() ).toBe( 'Store notice.' );
		click();
		respond( { error: true, messages }, 1 );
		expect( $( 'main .woocommerce-error' ) ).toHaveLength( 1 );
		click();
		respond( { error: true, messages: '' }, 2 );
		expect( $( 'main .woocommerce-error' ) ).toHaveLength( 0 );
		expect( $( '#store-notice' ).text() ).toBe( 'Store notice.' );
	} );

	test.each( [
		{ error: true },
		{ error: true, product_url: false },
		{ error: true, product_url: '' },
		{ error: true, product_url: { url: '/help/' } },
		{ error: true, product_url: [ '/help/' ], messages: [ messages ] },
		{ error: true, product_url: true, messages: 1 },
		{ error: true, product_url: 1, messages: null },
	] )( 'makes a URL-less or malformed failure terminal: %j', ( response ) => {
		const consoleError = jest.spyOn( console, 'error' ).mockImplementation( () => {} );
		const $button = click();
		respond( response );
		expectFailure( $button );
		expect( $( '[role="alert"]' ) ).toHaveLength( 0 );
		expect( consoleError ).not.toHaveBeenCalled();
	} );

	test( 'reuses the unambiguous local wrapper, not the first global wrapper', () => {
		$( 'main' ).before( '<div id="unrelated" class="woocommerce-notices-wrapper">Account notice</div>' );
		const $button = click();
		respond( { error: true, messages } );
		expectFailure( $button );
		expect( $( '#unrelated' ).text() ).toBe( 'Account notice' );
		expect( $( 'main .woocommerce-notices-wrapper' ) ).toHaveLength( 1 );
		expect( $( 'main [role="alert"]' ).text() ).toBe( 'Cannot add this item.' );
	} );

	test.each( [
		[ 'above the viewport', -500, true ],
		[ 'below the viewport', window.innerHeight + 10, true ],
		[ 'already visible', 20, false ],
	] )( 'scrolls to and focuses only an error that is out of view (%s)', ( _label, top, scrolls ) => {
		const scrollIntoView = jest.fn();
		window.HTMLElement.prototype.scrollIntoView = scrollIntoView;
		jest.spyOn( window.HTMLElement.prototype, 'getBoundingClientRect' ).mockReturnValue( { top, bottom: top + 50 } );
		const element = document.getElementById( 'first' );
		element.focus();
		click();
		respond( { error: true, messages } );
		const notice = $( '.woocommerce-error' )[ 0 ];
		expect( scrollIntoView ).toHaveBeenCalledTimes( scrolls ? 1 : 0 );
		if ( scrolls ) {
			expect( scrollIntoView.mock.contexts[ 0 ] ).toBe( notice );
			expect( scrollIntoView ).toHaveBeenCalledWith( { block: 'center' } );
			expect( notice.getAttribute( 'tabindex' ) ).toBe( '-1' );
		}
		expect( document.activeElement ).toBe( scrolls ? notice : element );
		delete window.HTMLElement.prototype.scrollIntoView;
	} );

	test.each( [
		[ `<main><ul class="products"><li>${ button() }</li></ul></main>`, 'ul.products' ],
		[ `<section><ul class="products"><li>${ button() }</li></ul></section>`, 'ul.products' ],
		[
			`<div class="wc-block-grid"><ul class="wc-block-grid__products">
				<li><div class="wp-block-button">${ button() }</div></li></ul></div>`,
			'ul.wc-block-grid__products',
		],
		[ `<main><p class="product woocommerce add_to_cart_inline">$12 ${ button() }</p></main>`, 'p.add_to_cart_inline' ],
		[ `<section>${ button() }</section>`, '#first' ],
	] )( 'creates a connected notice destination without an existing wrapper: %s', ( markup, target ) => {
		document.body.innerHTML = markup;
		const $button = click();
		respond( { error: true, messages } );
		expectFailure( $button );
		expect( $( '.woocommerce-notices-wrapper [role="alert"]' ).text() ).toBe( 'Cannot add this item.' );
		expect( $( target ).prev().hasClass( 'woocommerce-notices-wrapper' ) ).toBe( true );
	} );

	test( 'shows errors when the originating button has been detached', () => {
		const $button = click();
		$( 'main' ).remove();
		respond( { error: true, messages } );
		expectFailure( $button );
		expect( $( document.body ).find( '[role="alert"]' ).text() ).toBe( 'Cannot add this item.' );
	} );

	test( 'retains keyboard focus on the initiating button', () => {
		const element = document.getElementById( 'first' );
		element.focus();
		$( element ).trigger( $.Event( 'keydown', { key: ' ' } ) );
		respond( { error: true, messages } );
		expect( document.activeElement ).toBe( element );
		expectFailure( $( element ) );
	} );

	test( 'keeps another queued button loading until its own successful response', () => {
		$( '.products' ).append( `<li>${ button( 'second' ) }</li>` );
		const $first = click();
		const $second = click( 'second' );
		expect( requests ).toHaveLength( 1 );
		respond( { error: true, messages } );
		expectFailure( $first );
		expect( $second.hasClass( 'loading' ) ).toBe( true );
		expect( requests ).toHaveLength( 2 );
		const fragments = { '#cart-count': '<span id="cart-count">1</span>' };
		$( document.body ).append( '<span id="cart-count">0</span>' );
		respond( { fragments, cart_hash: 'cart-hash' }, 1 );
		expect( $second.hasClass( 'loading' ) ).toBe( false );
		expect( $second.hasClass( 'added' ) ).toBe( true );
		expect( $first.hasClass( 'added' ) ).toBe( false );
		expect( $( '#cart-count' ).text() ).toBe( '1' );
		expect( $second.next().is( 'a.added_to_cart' ) ).toBe( true );
		expect( addedEvents ).toEqual( [ [ fragments, 'cart-hash', $second ] ] );
	} );
} );
