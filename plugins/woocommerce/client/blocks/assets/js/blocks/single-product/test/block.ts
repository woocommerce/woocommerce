/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { screen } from '@testing-library/react';
import { http, HttpResponse } from 'msw';
import { setupServer } from 'msw/node';
import { createBlock, parse, serialize } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { initializeEditor } from '../../../../../tests/integration/helpers/integration-test-editor';
import { textContentMatcher } from '../../../../../tests/utils/find-by-text';
import '../';
import '../../product-elements-blocks/price';
import '../../product-elements-blocks/summary';

const mockProduct = {
	id: 82,
	name: 'Beanie with Logo',
	short_description: 'This is a short description',
	prices: {
		price: '2000',
		regular_price: '2000',
		sale_price: '',
		price_range: null,
		currency_code: 'EUR',
		currency_symbol: '€',
		currency_minor_unit: 2,
		currency_decimal_separator: ',',
		currency_thousand_separator: '.',
		currency_prefix: '',
		currency_suffix: ' €',
	},
	images: [
		{
			id: 1,
			src: 'test-image-1.jpg',
			thumbnail: 'test-thumb-1.jpg',
			alt: 'Test 1',
		},
		{
			id: 2,
			src: 'test-image-2.jpg',
			thumbnail: 'test-thumb-2.jpg',
			alt: 'Test 2',
		},
		{
			id: 3,
			src: 'test-image-3.jpg',
			thumbnail: 'test-thumb-3.jpg',
			alt: 'Test 3',
		},
	],
};

// Setup MSW.
const handlers = [
	http.get( '/wp/v2/types', () => {
		return HttpResponse.json( {} );
	} ),

	http.get( '/wc/store/v1/products/:id', () => {
		return HttpResponse.json( mockProduct );
	} ),
];

const server = setupServer( ...handlers );

// Start MSW.
beforeAll( () => server.listen() );
afterEach( () => server.resetHandlers() );
afterAll( () => server.close() );

async function setup() {
	const singleProductBlock = [
		{
			name: 'woocommerce/single-product',
			attributes: {
				productId: '82',
			},
		},
	];
	return initializeEditor( singleProductBlock );
}

describe( 'Product block', () => {
	it( 'preserves unstyled saved markup', () => {
		const content =
			'<!-- wp:woocommerce/single-product {"productId":82} -->\n<div class="wp-block-woocommerce-single-product woocommerce"></div>\n<!-- /wp:woocommerce/single-product -->';
		const [ block ] = parse( content );

		expect( block.isValid ).toBe( true );
		expect( serialize( block ) ).toBe( content );
	} );

	it( 'round-trips spacing, background, and border styles on the saved wrapper', () => {
		const style = {
			color: { gradient: 'linear-gradient(135deg,#ffffff,#eeeeee)' },
			border: {
				color: '#123456',
				style: 'solid',
				width: '2px',
				radius: '8px',
			},
			spacing: {
				margin: { top: 'var:preset|spacing|40', bottom: '0' },
				padding: {
					top: '0',
					right: '1rem',
					bottom: '2rem',
					left: '1rem',
				},
			},
		};
		const content = serialize(
			createBlock( 'woocommerce/single-product', {
				productId: 82,
				style,
			} )
		);
		const wrapper = document.createElement( 'div' );
		wrapper.innerHTML = content;
		const product = wrapper.querySelector< HTMLElement >(
			'.wp-block-woocommerce-single-product'
		);

		expect( product ).toHaveClass( 'woocommerce', 'has-background' );
		expect( product?.getAttribute( 'style' ) ).toContain(
			'background:linear-gradient(135deg,#ffffff,#eeeeee)'
		);
		expect( product?.style.borderWidth ).toBe( '2px' );
		expect( product?.style.borderRadius ).toBe( '8px' );
		expect( product?.getAttribute( 'style' ) ).toContain(
			'margin-top:var(--wp--preset--spacing--40)'
		);
		expect( product?.style.marginBottom ).toBe( '0px' );
		expect( product?.style.paddingTop ).toBe( '0px' );
		expect( product?.style.paddingRight ).toBe( '1rem' );
		expect( product?.style.paddingBottom ).toBe( '2rem' );
		expect( product?.style.paddingLeft ).toBe( '1rem' );

		const [ block ] = parse( content );
		expect( block.isValid ).toBe( true );
		expect( block.attributes.style ).toEqual( style );
		expect( serialize( block ) ).toBe( content );
		expect(
			serialize(
				createBlock( 'woocommerce/single-product', { style: {} } )
			)
		).not.toContain( 'style=' );
	} );

	it( 'should render inner blocks for users without edit permissions', async () => {
		// The V4 of this endpoint will return product data to authors,
		// see https://github.com/woocommerce/woocommerce/pull/61718.
		// However, V3 didn't, that's why we need this test.
		server.use(
			http.get( '/wc/v3/products/:id', () => {
				return HttpResponse.json( '', { status: 403 } );
			} )
		);

		await setup();

		const block = await screen.findAllByLabelText( `Block: Product` );
		expect( block.length ).toBeGreaterThan( 0 );

		const productDescription = await screen.findByText(
			'This is a short description'
		);
		expect( productDescription ).toBeInTheDocument();

		expect(
			await screen.findByText( textContentMatcher( '20,00 €' ) )
		).toBeInTheDocument();

		// wp-6.8: MSW warns about unhandled OPTIONS preflight requests from
		// @wordpress/core-data in jsdom where there's no real network layer.
		expect( console ).toHaveWarned();
	} );

	it( 'should render inner blocks for admins', async () => {
		server.use(
			http.get( '/wc/v3/products/:id', () => {
				return HttpResponse.json( mockProduct );
			} )
		);

		await setup();

		const block = await screen.findAllByLabelText( `Block: Product` );
		expect( block.length ).toBeGreaterThan( 0 );

		const productDescription = await screen.findByText(
			'This is a short description'
		);
		expect( productDescription ).toBeInTheDocument();

		expect(
			await screen.findByText( textContentMatcher( '20,00 €' ) )
		).toBeInTheDocument();
	} );
} );
