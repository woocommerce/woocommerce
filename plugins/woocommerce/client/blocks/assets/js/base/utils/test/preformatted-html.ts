/**
 * Internal dependencies
 */
import {
	swapPreformattedHtml,
	PRODUCT_ELEMENT_HTML_CONFIG,
	LIST_ITEM_HTML_CONFIG,
} from '../preformatted-html';

// Markup wc_price() produces for an RTL-script currency symbol (Lebanese
// pound). The dir="auto" bidi isolation and translate="no" must survive the
// sanitizer, or the first client-side price swap silently undoes them.
const RTL_PRICE_HTML =
	'<span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol" translate="no" dir="auto">ل.ل</span>&nbsp;2.00</bdi></span>';

describe( 'swapPreformattedHtml', () => {
	let ref: HTMLElement;

	beforeEach( () => {
		ref = document.createElement( 'div' );
	} );

	describe.each( [
		[ 'PRODUCT_ELEMENT_HTML_CONFIG', PRODUCT_ELEMENT_HTML_CONFIG ],
		[ 'LIST_ITEM_HTML_CONFIG', LIST_ITEM_HTML_CONFIG ],
	] )( 'price markup through %s', ( _label, config ) => {
		it( 'keeps the dir and translate attributes on the currency symbol', () => {
			swapPreformattedHtml( ref, RTL_PRICE_HTML, config );

			const symbol = ref.querySelector(
				'.woocommerce-Price-currencySymbol'
			);
			expect( symbol ).not.toBeNull();
			expect( symbol?.getAttribute( 'dir' ) ).toBe( 'auto' );
			expect( symbol?.getAttribute( 'translate' ) ).toBe( 'no' );
		} );

		it( 'still strips disallowed tags and attributes', () => {
			swapPreformattedHtml(
				ref,
				'<span class="amount" onclick="alert(1)" style="color:red">1</span><script>alert(1)</script>',
				config
			);

			const span = ref.querySelector( 'span' );
			expect( span?.getAttribute( 'onclick' ) ).toBeNull();
			expect( span?.getAttribute( 'style' ) ).toBeNull();
			expect( ref.querySelector( 'script' ) ).toBeNull();
		} );
	} );

	it( 'keeps image markup in list-item fields', () => {
		swapPreformattedHtml(
			ref,
			'<img src="a.jpg" srcset="a.jpg 1x" sizes="64px" alt="p" width="64" height="64" loading="lazy" decoding="async" />',
			LIST_ITEM_HTML_CONFIG
		);

		const img = ref.querySelector( 'img' );
		expect( img ).not.toBeNull();
		expect( img?.getAttribute( 'srcset' ) ).toBe( 'a.jpg 1x' );
		expect( img?.getAttribute( 'loading' ) ).toBe( 'lazy' );
	} );

	it( 'does nothing when the element is missing', () => {
		expect( () =>
			swapPreformattedHtml( null, RTL_PRICE_HTML, LIST_ITEM_HTML_CONFIG )
		).not.toThrow();
	} );

	it( 'does nothing when the field is not a string', () => {
		ref.innerHTML = '<em>previous</em>';

		swapPreformattedHtml( ref, undefined, PRODUCT_ELEMENT_HTML_CONFIG );

		expect( ref.innerHTML ).toBe( '<em>previous</em>' );
	} );
} );
