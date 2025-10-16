/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { act, fireEvent, screen, waitFor } from '@testing-library/react';
import { http, HttpResponse } from 'msw';
import { setupServer } from 'msw/node';
import { readFileSync } from 'fs';
import { join } from 'path';
import { dispatch } from '@wordpress/data';
import { productsStore } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import {
	initializeEditor,
	selectBlock,
} from '../../../../../tests/integration/helpers/integration-test-editor';
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
import '../variation-selector/attribute-options';
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

async function switchProductType( productType: string ) {
	await selectBlock( 'Block: Add to Cart + Options (Beta)' );

	await act( async () => {
		fireEvent.click(
			screen.getByRole( 'button', { name: 'Switch product type' } )
		);
	} );

	await act( async () => {
		fireEvent.click(
			screen.getByRole( 'menuitem', { name: productType } )
		);
	} );
}

const expectHasBlock = async ( blockName: string ) => {
	const block = await screen.findAllByLabelText( `Block: ${ blockName }` );
	expect( block.length ).toBeGreaterThan( 0 );
};

describe( 'Add to Cart + Options block', () => {
	it( 'should render inner blocks for simple and external products', async () => {
		await setup();
		await expectHasBlock( 'Add to Cart + Options (Beta)' );

		// Simple products.
		await expectHasBlock( 'Product Stock Indicator' );
		await expectHasBlock( 'Product Quantity (Beta)' );
		await expectHasBlock( 'Add to Cart Button' );

		// External products.
		await switchProductType( 'External/Affiliate product' );

		await waitFor( () => {
			expect(
				screen.queryByLabelText( 'Block: Product Stock Indicator' )
			).not.toBeInTheDocument();
		} );
		await expectHasBlock( 'Add to Cart Button' );
	} );

	it( 'should render inner blocks for grouped products', async () => {
		expect.hasAssertions();

		await setup();
		await expectHasBlock( 'Add to Cart + Options (Beta)' );

		await switchProductType( 'Grouped product' );

		await expectHasBlock( 'Grouped Product Selector (Beta)' );
		await expectHasBlock( 'Grouped Product: Template (Beta)' );
		await expectHasBlock( 'Grouped Product: Item Selector (Beta)' );
		await expectHasBlock( 'Grouped Product: Item Label (Beta)' );
		await expectHasBlock( 'Product Price' );
		await expectHasBlock( 'Product Stock Indicator' );
	} );

	it( 'should render inner blocks for grouped products with no store products', async () => {
		expect.hasAssertions();

		server.use(
			http.get( '/wc/v3/products', () => {
				return HttpResponse.json( [] );
			} )
		);

		await setup();
		await expectHasBlock( 'Add to Cart + Options (Beta)' );

		await switchProductType( 'Grouped product' );

		await expectHasBlock( 'Grouped Product Selector (Beta)' );
		await expectHasBlock( 'Grouped Product: Template (Beta)' );
		await expectHasBlock( 'Grouped Product: Item Selector (Beta)' );
		await expectHasBlock( 'Grouped Product: Item Label (Beta)' );
		await expectHasBlock( 'Product Price' );
		await expectHasBlock( 'Product Stock Indicator' );
	} );

	it( 'should render inner blocks for variable products', async () => {
		expect.hasAssertions();

		await setup();
		await expectHasBlock( 'Add to Cart + Options (Beta)' );

		await switchProductType( 'Variable product' );

		await expectHasBlock( 'Variation Selector (Beta)' );
		await expectHasBlock( 'Variation Selector: Attribute Name (Beta)' );
		await expectHasBlock( 'Variation Selector: Attribute Options (Beta)' );
		await expectHasBlock( 'Variation Selector: Template (Beta)' );
		await expectHasBlock( 'Variation Description (Beta)' );
		await expectHasBlock( 'Product Stock Indicator' );
		await expectHasBlock( 'Product Quantity (Beta)' );
		await expectHasBlock( 'Add to Cart Button' );
	} );
} );
