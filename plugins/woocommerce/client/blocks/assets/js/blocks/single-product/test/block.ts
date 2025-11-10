/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { screen } from '@testing-library/react';
import { http, HttpResponse } from 'msw';
import { setupServer } from 'msw/node';

/**
 * Internal dependencies
 */
import { initializeEditor } from '../../../../../tests/integration/helpers/integration-test-editor';
import '../';
import '../../../atomic/blocks/product-elements/price';
import '../../../atomic/blocks/product-elements/summary';

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

		const productPrice = await screen.findByText( '20,00 €' );
		expect( productPrice ).toBeInTheDocument();
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

		const productPrice = await screen.findByText( '20,00 €' );
		expect( productPrice ).toBeInTheDocument();
	} );
} );
