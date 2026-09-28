/**
 * External dependencies
 */
import { loadExperimentAssignment } from '@woocommerce/explat';

jest.mock( '@woocommerce/explat', () => ( {
	loadExperimentAssignment: jest.fn(),
} ) );

/**
 * Internal dependencies
 */
import {
	PRODUCT_PREVIEW_EXPERIMENT_NAME,
	addVariationToPreviewLinks,
	loadProductPreviewVariation,
} from '../product-preview-experiment';

describe( 'loadProductPreviewVariation', () => {
	it( 'resolves to the assigned variation', async () => {
		jest.mocked( loadExperimentAssignment ).mockResolvedValue( {
			experimentName: PRODUCT_PREVIEW_EXPERIMENT_NAME,
			variationName: 'treatment',
			retrievedTimestamp: 0,
			ttl: 60,
		} );

		await expect( loadProductPreviewVariation() ).resolves.toBe(
			'treatment'
		);
		expect( loadExperimentAssignment ).toHaveBeenCalledWith(
			PRODUCT_PREVIEW_EXPERIMENT_NAME
		);
	} );

	it( 'resolves to null when the store is not in the experiment', async () => {
		jest.mocked( loadExperimentAssignment ).mockResolvedValue( {
			experimentName: PRODUCT_PREVIEW_EXPERIMENT_NAME,
			variationName: null,
			retrievedTimestamp: 0,
			ttl: 60,
			isFallbackExperimentAssignment: true,
		} );

		await expect( loadProductPreviewVariation() ).resolves.toBeNull();
	} );
} );

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
