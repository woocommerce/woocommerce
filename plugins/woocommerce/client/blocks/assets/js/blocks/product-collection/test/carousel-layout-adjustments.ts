/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import {
	screen,
	waitFor,
	within,
	fireEvent,
	act,
} from '@testing-library/react';

/**
 * Internal dependencies
 */
import {
	initializeEditor,
	selectBlock,
} from '../../../../../tests/integration/helpers/integration-test-editor';
import { LayoutOptions } from '../types';
import '../';
import '../../next-previous-buttons';
import '../../product-template';

jest.mock( '@woocommerce/block-settings', () => ( {
	...jest.requireActual( '@woocommerce/block-settings' ),
	isExperimentalBlocksEnabled: () => true,
} ) );

type SetupAttributes = {
	query?: {
		inherit?: boolean;
		__woocommerceOnSale?: boolean;
	};
};

async function setup( {
	withHeading,
	attributes,
}: {
	withHeading: boolean;
	attributes: SetupAttributes;
} ) {
	const productCollectionBlock = {
		name: 'woocommerce/product-collection',
		attributes: {
			query: {
				type: 'product',
				...attributes.query,
			},
			displayLayout: {
				type: LayoutOptions.GRID,
				columns: 3,
			},
			...attributes,
		},
		innerBlocks: withHeading
			? [
					{ name: 'core/heading' },
					{ name: 'woocommerce/product-template' },
			  ]
			: [ { name: 'woocommerce/product-template' } ],
	};
	return initializeEditor( [ productCollectionBlock ] );
}

describe( 'Product Collection Block - Carousel Layout Adjustments', () => {
	describe( 'On Sale Collection with Heading', () => {
		it( 'should handle transition to and from carousel layout correctly', async () => {
			// 1. Add Product Collection in editor with On Sale query
			await setup( {
				withHeading: true,
				attributes: {
					query: {
						inherit: false,
						__woocommerceOnSale: true,
					},
				},
			} );

			await selectBlock( /Block: Product Collection/i );

			// 3. Switch to Carousel mode
			// Find and click the carousel layout option
			await waitFor( () => {
				expect(
					screen.getByRole( 'radio', { name: /carousel/i } )
				).toBeVisible();
			} );

			const carouselOption = screen.getByRole( 'radio', {
				name: /carousel/i,
			} );
			await act( async () => {
				fireEvent.click( carouselOption );
			} );

			// 4. Verify there's ROW with HEADING AND NEXT PREV
			const groupBlock = screen.getByRole( 'document', {
				name: /Block: Row/i,
			} );
			expect( groupBlock ).toBeInTheDocument();

			// Check if the group has both heading and next/prev buttons
			const groupContent = within( groupBlock );
			expect(
				groupContent.getByRole( 'document', {
					name: /Block: Heading/i,
				} )
			).toBeInTheDocument();
			expect(
				groupContent.getByRole( 'document', {
					name: /Block: Next\/Previous Buttons/i,
				} )
			).toBeInTheDocument();

			// 5. Switch back to GRID
			const gridOption = screen.getByRole( 'radio', { name: /grid/i } );
			await act( async () => {
				fireEvent.click( gridOption );
			} );

			// 6. Verify the HEADING is kept but in its original position
			const headingAfterGrid = screen.getByRole( 'document', {
				name: /Block: Heading/i,
			} );
			expect( headingAfterGrid ).toBeInTheDocument();
			expect( headingAfterGrid.parentElement ).not.toBe( groupBlock );
		} );
	} );

	describe( 'Custom Collection without Heading', () => {
		it( 'should handle transition to and from carousel layout correctly', async () => {
			// 1. Add Product Collection in editor with custom query
			await setup( {
				withHeading: false,
				attributes: {
					query: {
						inherit: false,
					},
				},
			} );

			await selectBlock( /Block: Product Collection/i );

			// 3. Switch to Carousel mode
			await waitFor( () => {
				expect(
					screen.getByRole( 'radio', { name: /carousel/i } )
				).toBeVisible();
			} );

			const carouselOption = screen.getByRole( 'radio', {
				name: /carousel/i,
			} );

			await act( async () => {
				fireEvent.click( carouselOption );
			} );

			// 4. Verify there's ROW with NEXT PREV only and pagination removed
			const groupBlock = screen.getByRole( 'document', {
				name: /Block: Row/i,
			} );
			expect( groupBlock ).toBeInTheDocument();

			// Check if the group has next/prev buttons
			const groupContent = within( groupBlock );
			expect(
				groupContent.getByRole( 'document', {
					name: /Block: Next\/Previous Buttons/i,
				} )
			).toBeInTheDocument();

			// Verify pagination is removed
			expect(
				screen.queryByRole( 'document', {
					name: /Block: Pagination/i,
				} )
			).not.toBeInTheDocument();

			// 5. Switch back to GRID
			const gridOption = screen.getByRole( 'radio', { name: /grid/i } );
			await act( async () => {
				fireEvent.click( gridOption );
			} );

			// 6. Verify there's no GROUP anymore
			expect(
				screen.queryByRole( 'document', { name: /Block: Row/i } )
			).not.toBeInTheDocument();

			// Verify pagination is restored
			expect(
				screen.getByRole( 'document', {
					name: /Block: Pagination/i,
				} )
			).toBeInTheDocument();
		} );
	} );
} );
