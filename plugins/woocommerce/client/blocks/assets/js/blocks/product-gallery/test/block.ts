/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { screen, waitFor } from '@testing-library/react';
import { createBlock } from '@wordpress/blocks';
import { http, HttpResponse } from 'msw';
import { setupServer } from 'msw/node';

/**
 * Internal dependencies
 */
import { initializeEditor } from '../../../../../tests/integration/helpers/integration-test-editor';
import blockJson from '../block.json';
import '../';
import '../inner-blocks/product-gallery-large-image';
import '../inner-blocks/product-gallery-thumbnails';
import '../../next-previous-buttons';
import '../../single-product';
import '../../../atomic/blocks/product-elements/image';
import '../../../atomic/blocks/product-elements/sale-badge';

// Setup MSW
const handlers = [
	http.get( '/wp/v2/product/:id', () => {
		return HttpResponse.json( {
			id: 123,
			title: { rendered: 'Test Product' },
			images: [
				{
					id: 1,
					src: 'test-image-1.jpg',
					thumbnail: 'test-thumb-1.jpg',
					alt: 'Test 1',
				},
			],
		} );
	} ),
	http.get( '/wc/v3/products/:id', () => {
		return HttpResponse.json( {
			id: 123,
			name: 'Test Product',
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
		} );
	} ),

	http.get( '/wc/store/v1', () => {
		return HttpResponse.json( {} );
	} ),

	http.get( '/wp/v2/types', () => {
		return HttpResponse.json( {} );
	} ),

	http.get( '/wc/store/v1/products/:id', () => {
		return HttpResponse.json( {
			id: 123,
			name: 'Test Product',
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
		} );
	} ),
];

const server = setupServer( ...handlers );

// Start MSW
beforeAll( () => server.listen() );
afterEach( () => server.resetHandlers() );
afterAll( () => server.close() );

async function setup( attributes = {} ) {
	const productImageBlock = createBlock( 'woocommerce/product-image', {
		showProductLink: false,
		showSaleBadge: false,
		aspectRatio: '16/9',
		...attributes,
	} );

	const largeImageBlock = createBlock(
		'woocommerce/product-gallery-large-image',
		{},
		[
			productImageBlock,
			createBlock( 'woocommerce/product-sale-badge', { align: 'right' } ),
			createBlock(
				'woocommerce/product-gallery-large-image-next-previous'
			),
		]
	);

	const thumbnailsBlock = createBlock(
		'woocommerce/product-gallery-thumbnails'
	);

	const productGalleryBlock = createBlock(
		blockJson.name,
		{
			hoverZoom: true,
			fullScreenOnClick: true,
		},
		[ thumbnailsBlock, largeImageBlock ]
	);

	const singleProductBlock = [
		{
			name: 'woocommerce/single-product',
			attributes: {
				productId: '123',
			},
			innerBlocks: [ productGalleryBlock ],
		},
	];
	return initializeEditor( singleProductBlock );
}

describe( 'Product Gallery Block', () => {
	it( 'should render the block in the editor with correct structure', async () => {
		await setup();

		// Get the main block wrapper
		const block = screen.getByRole( 'document', {
			name: /Block: Product Gallery/i,
		} );
		expect( block ).toBeInTheDocument();

		// Check inner blocks container
		const innerBlocks = block.querySelector( '.block-editor-inner-blocks' );
		expect( innerBlocks ).toBeInTheDocument();

		// Check layout container
		const layout = block.querySelector(
			'.block-editor-block-list__layout'
		);
		expect( layout ).toBeInTheDocument();
		expect( layout ).toHaveClass( 'is-layout-flex' );
		expect( layout ).toHaveClass( 'is-horizontal' );
		expect( layout ).toHaveClass( 'is-nowrap' );

		// Check for large image block and its inner blocks
		const largeImageBlock = screen.getByRole( 'document', {
			name: /Block: Large Image/i,
		} );
		expect( largeImageBlock ).toBeInTheDocument();

		// Check inner blocks of large image
		expect(
			screen.getByRole( 'document', { name: /Block: Product Image/i } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'document', { name: /Block: On-Sale Badge/i } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'document', {
				name: /Block: Next\/Previous Buttons/i,
			} )
		).toBeInTheDocument();

		await waitFor( () => {
			expect(
				screen.getAllByRole( 'img', { hidden: true } ).at( 0 )
			).not.toHaveAttribute( 'src', 'placeholder.jpg' );
		} );

		// Check that the product image is rendered
		const productImage = screen.getByTestId( 'product-image' );
		expect( productImage ).toBeInTheDocument();
		expect( productImage ).toHaveAttribute( 'src', 'test-image-1.jpg' );
		expect( productImage ).toHaveAttribute( 'alt', 'Test 1' );
	} );

	it( 'should ensure thumbnail height matches large image height with custom aspect ratio', async () => {
		await setup( { aspectRatio: '16/9' } );

		// Get the large image
		const productImage = screen.getByTestId( 'product-image' );
		expect( productImage ).toBeInTheDocument();

		// Verify aspect ratio class is applied
		const imageContainer = productImage.closest(
			'.wc-block-components-product-image'
		);
		expect( imageContainer ).toHaveClass(
			'wc-block-components-product-image--aspect-ratio-16-9'
		);

		// Get the thumbnails block
		const thumbnailsBlock = screen.getByRole( 'document', {
			name: /Block: Thumbnails/i,
		} );
		expect( thumbnailsBlock ).toBeInTheDocument();

		// Get the heights
		const largeImageHeight = productImage.getBoundingClientRect().height;
		const thumbnailHeight = thumbnailsBlock.getBoundingClientRect().height;

		// Check that the heights match
		expect( thumbnailHeight ).toBe( largeImageHeight );
	} );
} );
