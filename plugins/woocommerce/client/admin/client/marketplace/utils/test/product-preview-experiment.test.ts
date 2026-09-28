/**
 * Internal dependencies
 */
import { addVariationToPreviewLinks } from '../product-preview-experiment';

describe( 'addVariationToPreviewLinks', () => {
	function hrefs( html: string ) {
		const template = document.createElement( 'template' );
		template.innerHTML = html;
		return Array.from( template.content.querySelectorAll( 'a' ) ).map(
			( link ) => link.getAttribute( 'href' )
		);
	}

	it( 'adds the variation to WooCommerce.com links and keeps their parameters', () => {
		const html = addVariationToPreviewLinks(
			'<p>Intro</p>' +
				'<a href="https://woocommerce.com/cart/?add-to-cart=1&utm_source=previewscreen" data-iam-tracks="buy_now">Buy now</a>' +
				'<a href="https://woocommerce.com/products/test/?utm_source=previewscreen">See more</a>',
			'treatment'
		);

		expect( hrefs( html ) ).toEqual( [
			'https://woocommerce.com/cart/?add-to-cart=1&utm_source=previewscreen&utm_term=treatment',
			'https://woocommerce.com/products/test/?utm_source=previewscreen&utm_term=treatment',
		] );
		expect( html ).toContain( '<p>Intro</p>' );
		expect( html ).toContain( 'data-iam-tracks="buy_now"' );
	} );

	it( 'leaves links to other sites and relative links unchanged', () => {
		const html = addVariationToPreviewLinks(
			'<a href="https://example.com/docs/">Docs</a><a href="#details">Details</a>',
			'treatment'
		);

		expect( hrefs( html ) ).toEqual( [
			'https://example.com/docs/',
			'#details',
		] );
	} );
} );
