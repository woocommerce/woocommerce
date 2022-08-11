/**
 * External dependencies
 */
import { render, fireEvent } from '@testing-library/react';
import { ProductDataContextProvider } from '@woocommerce/shared-context';

/**
 * Internal dependencies
 */
import { Block } from '../block';

jest.mock( '@woocommerce/base-hooks', () => ( {
	__esModule: true,
	useBorderProps: jest.fn( () => ( {
		className: '',
		style: {},
	} ) ),
	useTypographyProps: jest.fn( () => ( {
		style: {},
	} ) ),
	useSpacingProps: jest.fn( () => ( {
		style: {},
	} ) ),
} ) );

const productWithoutImages = {
	name: 'Test product',
	id: 1,
	fallbackAlt: 'Test product',
	permalink: 'http://test.com/product/test-product/',
	images: [],
};

const productWithImages = {
	name: 'Test product',
	id: 1,
	fallbackAlt: 'Test product',
	permalink: 'http://test.com/product/test-product/',
	images: [
		{
			id: 56,
			src: 'logo-1.jpg',
			thumbnail: 'logo-1-324x324.jpg',
			srcset: 'logo-1.jpg 800w, logo-1-300x300.jpg 300w, logo-1-150x150.jpg 150w, logo-1-768x767.jpg 768w, logo-1-324x324.jpg 324w, logo-1-416x415.jpg 416w, logo-1-100x100.jpg 100w',
			sizes: '(max-width: 800px) 100vw, 800px',
			name: 'logo-1.jpg',
			alt: '',
		},
		{
			id: 55,
			src: 'beanie-with-logo-1.jpg',
			thumbnail: 'beanie-with-logo-1-324x324.jpg',
			srcset: 'beanie-with-logo-1.jpg 800w, beanie-with-logo-1-300x300.jpg 300w, beanie-with-logo-1-150x150.jpg 150w, beanie-with-logo-1-768x768.jpg 768w, beanie-with-logo-1-324x324.jpg 324w, beanie-with-logo-1-416x416.jpg 416w, beanie-with-logo-1-100x100.jpg 100w',
			sizes: '(max-width: 800px) 100vw, 800px',
			name: 'beanie-with-logo-1.jpg',
			alt: '',
		},
	],
};

describe( 'Product Image Block', () => {
	describe( 'with product link', () => {
		test( 'should render an anchor with the product image', () => {
			const component = render(
				<ProductDataContextProvider product={ productWithImages }>
					<Block showProductLink={ true } />
				</ProductDataContextProvider>
			);

			// use testId as alt is added after image is loaded
			const image = component.getByTestId( 'product-image' );
			fireEvent.load( image );

			const productImage = component.getByAltText(
				productWithImages.name
			);
			expect( productImage.getAttribute( 'src' ) ).toBe(
				productWithImages.images[ 0 ].src
			);

			const anchor = productImage.closest( 'a' );
			expect( anchor.getAttribute( 'href' ) ).toBe(
				productWithImages.permalink
			);
		} );

		test( 'should render an anchor with the placeholder image', () => {
			const component = render(
				<ProductDataContextProvider product={ productWithoutImages }>
					<Block showProductLink={ true } />
				</ProductDataContextProvider>
			);

			const placeholderImage = component.getByAltText( '' );
			expect( placeholderImage.getAttribute( 'src' ) ).toBe(
				'placeholder.jpg'
			);

			const anchor = placeholderImage.closest( 'a' );
			expect( anchor.getAttribute( 'href' ) ).toBe(
				productWithoutImages.permalink
			);
			expect( anchor.getAttribute( 'aria-label' ) ).toBe(
				`Link to ${ productWithoutImages.name }`
			);
		} );
	} );

	describe( 'without product link', () => {
		test( 'should render the product image without an anchor wrapper', () => {
			const component = render(
				<ProductDataContextProvider product={ productWithImages }>
					<Block showProductLink={ false } />
				</ProductDataContextProvider>
			);
			const image = component.getByTestId( 'product-image' );
			fireEvent.load( image );

			const productImage = component.getByAltText(
				productWithImages.name
			);
			expect( productImage.getAttribute( 'src' ) ).toBe(
				productWithImages.images[ 0 ].src
			);

			const anchor = productImage.closest( 'a' );
			expect( anchor ).toBe( null );
		} );

		test( 'should render the placeholder image without an anchor wrapper', () => {
			const component = render(
				<ProductDataContextProvider product={ productWithoutImages }>
					<Block showProductLink={ false } />
				</ProductDataContextProvider>
			);

			const placeholderImage = component.getByAltText( '' );
			expect( placeholderImage.getAttribute( 'src' ) ).toBe(
				'placeholder.jpg'
			);

			const anchor = placeholderImage.closest( 'a' );
			expect( anchor ).toBe( null );
		} );
	} );
} );
