/**
 * External dependencies
 */
import type { BlockAttributes } from '@wordpress/blocks';
import '@testing-library/jest-dom';
import { fireEvent, screen, within } from '@testing-library/react';

/**
 * Internal dependencies
 */
import {
	initializeEditor,
	selectBlock,
} from '../../../../../../../tests/integration/helpers/integration-test-editor';
import '../';

// Mock the useProductDataContext hook
jest.mock( '@woocommerce/shared-context', () => ( {
	useProductDataContext: () => ( {
		product: {
			id: 1,
			images: [
				{
					id: 1,
					src: 'test-image-1.jpg',
					alt: 'Test Image 1',
				},
				{
					id: 2,
					src: 'test-image-2.jpg',
					alt: 'Test Image 2',
				},
			],
		},
	} ),
} ) );

async function setup( attributes: BlockAttributes ) {
	const testBlock = [
		{
			name: 'woocommerce/product-gallery-thumbnails',
			attributes,
		},
	];
	return initializeEditor( testBlock );
}

const blockName = /Block: Thumbnails/i;

describe( 'Product Gallery Thumbnails block', () => {
	describe( 'Display settings', () => {
		beforeEach( async () => {
			await setup( {} );
			await selectBlock( blockName );
		} );

		test( 'should have crop images enabled by default', () => {
			const block = within( screen.getByLabelText( blockName ) );

			// Check if the crop images toggle is enabled by default
			expect(
				screen.getByRole( 'checkbox', { name: /Crop images to fit/i } )
			).toBeChecked();

			// Verify the cropped class is applied to images
			const images = block.getAllByRole( 'img' );
			images.forEach( ( image ) => {
				expect( image ).toHaveClass(
					'wc-block-product-gallery-thumbnails__thumbnail__image--cropped'
				);
			} );
		} );

		test( 'should toggle crop images functionality', () => {
			const block = within( screen.getByLabelText( blockName ) );

			// Get all images before toggling
			const imagesBefore = block.getAllByRole( 'img' );
			imagesBefore.forEach( ( image ) => {
				expect( image ).toHaveClass(
					'wc-block-product-gallery-thumbnails__thumbnail__image--cropped'
				);
			} );

			// Toggle crop images off
			fireEvent.click(
				screen.getByRole( 'checkbox', { name: /Crop images to fit/i } )
			);

			// Get all images after toggling
			const imagesAfter = block.getAllByRole( 'img' );
			imagesAfter.forEach( ( image ) => {
				expect( image ).not.toHaveClass(
					'wc-block-product-gallery-thumbnails__thumbnail__image--cropped'
				);
			} );

			// Toggle crop images back on
			fireEvent.click(
				screen.getByRole( 'checkbox', { name: /Crop images to fit/i } )
			);

			// Verify the cropped class is applied again
			const imagesAfterToggle = block.getAllByRole( 'img' );
			imagesAfterToggle.forEach( ( image ) => {
				expect( image ).toHaveClass(
					'wc-block-product-gallery-thumbnails__thumbnail__image--cropped'
				);
			} );
		} );
	} );
} );
