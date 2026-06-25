/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { screen, waitFor } from '@testing-library/react';
import { http, HttpResponse } from 'msw';
import { setupServer } from 'msw/node';
import { readFileSync } from 'fs';
import { join } from 'path';
import { dispatch } from '@wordpress/data';
import { productsStore } from '@woocommerce/data';
import { store as coreStore } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { initializeEditor } from '../../../../../tests/integration/helpers/integration-test-editor';
import { store as productTypeTemplateStateStore } from '../../../shared/stores/product-type-template-state';
import '../';
import '../quantity-selector';
import '../../../atomic/blocks/product-elements/button';
import '../../../atomic/blocks/product-elements/stock-indicator';
import '../../../atomic/blocks/product-elements/price';
import '../grouped-product-selector';
import '../grouped-product-selector/product-item';
import '../grouped-product-selector/product-item-label';
import '../grouped-product-selector/product-item-selector';
import '../variation-selector';
import '../variation-selector/attribute';
import '../variation-selector/attribute-name';
import '../variation-description';

const mockTemplatePartsHTML: Record< string, string > = {
	simple: '',
	external: '',
	grouped: '',
	variable: '',
};

Object.keys( mockTemplatePartsHTML ).forEach( ( key ) => {
	mockTemplatePartsHTML[ key ] = readFileSync(
		join(
			__dirname,
			`../../../../../../../templates/parts/${ key }-product-add-to-cart-with-options.html`
		),
		'utf-8'
	);
} );

jest.mock( '@woocommerce/settings', () => {
	return {
		...jest.requireActual( '@woocommerce/settings' ),
		getSetting: jest.fn().mockImplementation( ( key, defaultValue ) => {
			if ( key === 'productTypes' ) {
				return {
					simple: 'Simple product',
					external: 'External/Affiliate product',
					grouped: 'Grouped product',
					variable: 'Variable product',
				};
			}
			if ( key === 'addToCartWithOptionsTemplatePartIds' ) {
				return {
					simple: 'woocommerce/woocommerce//simple-product-add-to-cart-with-options',
					external:
						'woocommerce/woocommerce//external-product-add-to-cart-with-options',
					grouped:
						'woocommerce/woocommerce//grouped-product-add-to-cart-with-options',
					variable:
						'woocommerce/woocommerce//variable-product-add-to-cart-with-options',
				};
			}
			return defaultValue;
		} ),
	};
} );

const mockProduct = {
	id: 82,
	name: 'Beanie with Logo',
	type: 'simple',
	is_in_stock: true,
	stock_availability: { text: '', class: 'in-stock' },
};

const getTemplatePartId = ( productType: string ) =>
	`woocommerce/woocommerce//${ productType }-product-add-to-cart-with-options`;

// Setup MSW.
const handlers = [
	http.get( '/wp/v2/types', () => {
		return HttpResponse.json( {
			wp_template_part: {
				slug: 'wp_template_part',
				rest_base: 'template-parts',
				rest_namespace: 'wp/v2',
			},
		} );
	} ),

	http.get( '/wc/v3/products', () => {
		return HttpResponse.json( [ mockProduct ] );
	} ),

	http.get( '/wc/store/v1/products/:id', () => {
		return HttpResponse.json( mockProduct );
	} ),

	http.get( '/wc/v3/products/:id', () => {
		return HttpResponse.json( mockProduct );
	} ),

	http.options( '/wp/v2/template-parts/*', () => {
		return HttpResponse.json(
			{},
			{
				headers: {
					allow: 'GET, POST, PUT, PATCH, DELETE',
				},
			}
		);
	} ),

	http.get( '/wp/v2/template-parts/*', ( request ) => {
		if (
			request.params[ 0 ] ===
			'woocommerce/woocommerce//simple-product-add-to-cart-with-options'
		) {
			return HttpResponse.json( {
				id: 'woocommerce/woocommerce//simple-product-add-to-cart-with-options',
				content: {
					raw: mockTemplatePartsHTML.simple,
				},
			} );
		}
		if (
			request.params[ 0 ] ===
			'woocommerce/woocommerce//external-product-add-to-cart-with-options'
		) {
			return HttpResponse.json( {
				id: 'woocommerce/woocommerce//external-product-add-to-cart-with-options',
				content: {
					raw: mockTemplatePartsHTML.external,
				},
			} );
		}
		if (
			request.params[ 0 ] ===
			'woocommerce/woocommerce//grouped-product-add-to-cart-with-options'
		) {
			return HttpResponse.json( {
				id: 'woocommerce/woocommerce//grouped-product-add-to-cart-with-options',
				content: {
					raw: mockTemplatePartsHTML.grouped,
				},
			} );
		}

		if (
			request.params[ 0 ] ===
			'woocommerce/woocommerce//variable-product-add-to-cart-with-options'
		) {
			return HttpResponse.json( {
				id: 'woocommerce/woocommerce//variable-product-add-to-cart-with-options',
				content: {
					raw: mockTemplatePartsHTML.variable,
				},
			} );
		}
	} ),
];

const server = setupServer( ...handlers );

// Start MSW.
beforeAll( () => server.listen() );
afterEach( () => {
	dispatch( productsStore ).invalidateResolutionForStore();
	dispatch( coreStore ).invalidateResolutionForStore();
	dispatch( productTypeTemplateStateStore ).switchProductType( 'simple' );
	server.resetHandlers();
} );
afterAll( () => server.close() );

async function setup() {
	const addToCartWithOptionsBlock = [
		{
			name: 'woocommerce/add-to-cart-with-options',
		},
	];
	return await initializeEditor( addToCartWithOptionsBlock );
}

const expectHasRenderedBlock = async ( blockName: string ) => {
	const block = await screen.findAllByLabelText( `Block: ${ blockName }` );
	expect( block.length ).toBeGreaterThan( 0 );
};

const getTemplateBlockNames = ( productType: string ): string[] => {
	return Array.from(
		mockTemplatePartsHTML[ productType ].matchAll(
			/<!-- wp:([a-z0-9-]+\/[a-z0-9-]+)/g
		),
		( match ) => match[ 1 ]
	);
};

const expectTemplateHasBlocks = (
	productType: string,
	expectedBlockNames: string[]
) => {
	expect( getTemplateBlockNames( productType ) ).toEqual(
		expect.arrayContaining( expectedBlockNames )
	);
};

// In Jest, clicking the toolbar product-type switcher does not reliably render
// the template part. Set the product type directly and check that the block
// fetches the matching template part.
const expectTemplatePartToBeRequested = async ( productType: string ) => {
	let requestedTemplatePartId = '';

	server.use(
		http.get( '/wp/v2/template-parts/*', ( request ) => {
			requestedTemplatePartId = String( request.params[ 0 ] );
			return HttpResponse.json( {
				id: requestedTemplatePartId,
				content: {
					raw: mockTemplatePartsHTML[ productType ],
				},
			} );
		} )
	);

	dispatch( productTypeTemplateStateStore ).switchProductType( productType );

	await setup();
	await expectHasRenderedBlock( 'Add to Cart + Options (Beta)' );

	await waitFor( () => {
		expect( requestedTemplatePartId ).toBe(
			getTemplatePartId( productType )
		);
	} );
};

describe( 'Add to Cart + Options block', () => {
	// The wp-6.8 version of @wordpress/private-apis causes a deprecation
	// warning for __unstableIsPreviewMode that fires non-deterministically
	// during async block editor setup. Filter it from jest-console's spy
	// before it checks for unexpected warnings.
	afterEach( () => {
		/* eslint-disable no-console */
		( console.warn as jest.Mock ).mock.calls = (
			console.warn as jest.Mock
		 ).mock.calls.filter(
			( [ firstArg ]: [ unknown ] ) =>
				! (
					typeof firstArg === 'string' &&
					firstArg.includes( '__unstableIsPreviewMode' )
				)
		);
		/* eslint-enable no-console */
	} );

	it( 'should render inner blocks for simple and external products', async () => {
		await setup();
		await expectHasRenderedBlock( 'Add to Cart + Options (Beta)' );

		expectTemplateHasBlocks( 'simple', [
			'woocommerce/product-stock-indicator',
			'woocommerce/add-to-cart-with-options-quantity-selector',
			'woocommerce/product-button',
		] );

		const externalTemplateBlocks = getTemplateBlockNames( 'external' );
		expect( externalTemplateBlocks ).toContain(
			'woocommerce/product-button'
		);
		expect( externalTemplateBlocks ).not.toContain(
			'woocommerce/product-stock-indicator'
		);
	} );

	it( 'should render inner blocks for grouped products', async () => {
		expect.hasAssertions();

		await expectTemplatePartToBeRequested( 'grouped' );

		expectTemplateHasBlocks( 'grouped', [
			'woocommerce/add-to-cart-with-options-grouped-product-selector',
			'woocommerce/add-to-cart-with-options-grouped-product-item',
			'woocommerce/add-to-cart-with-options-grouped-product-item-selector',
			'woocommerce/add-to-cart-with-options-grouped-product-item-label',
			'woocommerce/product-price',
			'woocommerce/product-stock-indicator',
		] );
	} );

	it( 'should render inner blocks for grouped products with no store products', async () => {
		expect.hasAssertions();

		server.use(
			http.get( '/wc/v3/products', () => {
				return HttpResponse.json( [] );
			} )
		);

		await expectTemplatePartToBeRequested( 'grouped' );

		expectTemplateHasBlocks( 'grouped', [
			'woocommerce/add-to-cart-with-options-grouped-product-selector',
			'woocommerce/add-to-cart-with-options-grouped-product-item',
			'woocommerce/add-to-cart-with-options-grouped-product-item-selector',
			'woocommerce/add-to-cart-with-options-grouped-product-item-label',
			'woocommerce/product-price',
			'woocommerce/product-stock-indicator',
		] );
	} );

	it( 'should render inner blocks for variable products', async () => {
		expect.hasAssertions();

		await expectTemplatePartToBeRequested( 'variable' );

		expectTemplateHasBlocks( 'variable', [
			'woocommerce/add-to-cart-with-options-variation-selector',
			'woocommerce/add-to-cart-with-options-variation-selector-attribute',
			'woocommerce/add-to-cart-with-options-variation-selector-attribute-name',
			'woocommerce/add-to-cart-with-options-variation-description',
			'woocommerce/product-stock-indicator',
			'woocommerce/add-to-cart-with-options-quantity-selector',
			'woocommerce/product-button',
		] );
	} );

	it( 'should render the placeholder when viewed as a user without permissions to edit template parts', async () => {
		server.use(
			http.options( '/wp/v2/template-parts/*', () => {
				return HttpResponse.json(
					{},
					{
						headers: {
							allow: 'GET',
						},
					}
				);
			} )
		);

		await setup();
		await expectHasRenderedBlock( 'Add to Cart + Options (Beta)' );

		await waitFor( () =>
			expect(
				screen.getByLabelText( 'Add to Cart + Options form' )
			).toBeInTheDocument()
		);
	} );
} );
