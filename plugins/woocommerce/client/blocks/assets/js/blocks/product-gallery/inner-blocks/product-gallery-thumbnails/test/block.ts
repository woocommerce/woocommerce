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
	} );
} );
