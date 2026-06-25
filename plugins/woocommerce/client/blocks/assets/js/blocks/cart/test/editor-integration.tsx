/**
 * External dependencies
 */
import { act, screen, waitFor } from '@testing-library/react';
import { registerCheckoutFilters } from '@woocommerce/blocks-checkout';
import { type BlockAttributes } from '@wordpress/blocks';
import { previewCart } from '@woocommerce/resource-previews';
import { dispatch } from '@wordpress/data';
import { CART_STORE_KEY as storeKey } from '@woocommerce/block-data';

/**
 * Internal dependencies
 */
import {
	initializeEditor,
	selectBlock,
} from '../../../../../tests/integration/helpers/integration-test-editor';
import '../index';
import '../inner-blocks/index';
import '../inner-blocks/cart-order-summary-coupon-form/index';
import '../../product-new/index';
import '../../../atomic/blocks/product-elements/sale-badge/index';
import '../../../atomic/blocks/product-elements/image/index';
import '../../../atomic/blocks/product-elements/price/index';
import '../../../atomic/blocks/product-elements/button/index';
import '../../../atomic/blocks/product-elements/title/index';
import '../../product-template/index.tsx';
import '../../product-collection/index.tsx';
import { getAllowedBlocks } from '../../cart-checkout-shared/editor-utils';

async function setup( attributes: BlockAttributes ) {
	const testBlock = [ { name: 'woocommerce/cart', attributes } ];
	return initializeEditor( testBlock );
}

describe( 'Cart block editor integration', () => {
	beforeAll( () => {
		// Register a checkout filter to allow `core/table` block in all Cart inner blocks,
		// add `core/audio` into the woocommerce/cart-order-summary-block specifically
		registerCheckoutFilters( 'woo-test-namespace', {
			// @ts-expect-error - The types for the checkout filters are not defined.
			additionalCartCheckoutInnerBlockTypes: (
				value: string[],
				extensions,
				{ block }: { block: string }
			) => {
				value.push( 'core/table' );
				if ( block === 'woocommerce/cart-order-summary-block' ) {
					value.push( 'core/audio' );
				}
				return value;
			},
		} );
	} );

	beforeEach( () => {
		act( () => {
			// need to clear the store resolution state between tests.
			// eslint-disable-next-line @typescript-eslint/no-explicit-any
			( dispatch( storeKey ) as any ).invalidateResolutionForStore();
			// Set up cart data with preview cart items
			// eslint-disable-next-line @typescript-eslint/no-explicit-any
			( dispatch( storeKey ) as any ).receiveCart( previewCart );
		} );
	} );

	it( 'inner blocks can be added/removed by filters', () => {
		expect(
			getAllowedBlocks( 'woocommerce/cart-order-summary-block' )
		).toEqual( expect.arrayContaining( [ 'core/table', 'core/audio' ] ) );

		expect( getAllowedBlocks( 'woocommerce/filled-cart-block' ) ).toEqual(
			expect.arrayContaining( [ 'core/table' ] )
		);
		expect(
			getAllowedBlocks( 'woocommerce/filled-cart-block' )
		).not.toContain( 'core/audio' );
	} );

	it( 'renders the Product collection cross-sells', async () => {
		await setup( {} );

		// Verify Cart block is properly initialized in the editor.
		expect(
			await screen.findByLabelText( /^Block: Cart$/i )
		).toBeVisible();

		// Navigate to the Filled Cart block first
		await selectBlock( /^Block: Filled Cart$/i );

		// Verify Product Collection block is present in the Cart Items
		const productCollection = await screen.findByLabelText(
			/^Block: Product Collection$/i
		);
		expect( productCollection ).toBeVisible();
	} );

	it( 'shows the cart preview in the editor', async () => {
		await setup( {} );

		// Verify Cart block is properly initialized in the editor.
		await waitFor( () => {
			expect( screen.getByLabelText( /^Block: Cart$/i ) ).toBeVisible();
			// Test Order Summary block - should have both Table and Audio options (specific filter applied).
		} );

		await waitFor( () => {
			expect(
				screen.getByLabelText( /Block: Filled Cart$/i )
			).toBeVisible();
		} );

		await selectBlock( /Block: Filled Cart/i );
		await selectBlock( /Block: Cart Line Items/i );

		const cartItems = previewCart.items;
		// Now the product links should be rendered
		cartItems.forEach( ( item ) => {
			const productNameElement = screen.getByRole( 'link', {
				name: item.name,
			} );
			expect( productNameElement ).toBeVisible();
			expect( productNameElement ).toHaveTextContent( item.name );
		} );
	} );

	it( 'can render the Empty Cart block view', async () => {
		await setup( { currentView: 'woocommerce/empty-cart-block' } );

		// Verify Cart block is properly initialized in the editor
		expect( screen.getByLabelText( /^Block: Cart$/i ) ).toBeVisible();

		const filledCartBlock = screen.getByLabelText( /Block: Filled Cart/i );
		const emptyCartBlock = screen.getByLabelText( /Block: Empty Cart/i );

		expect( emptyCartBlock ).toBeInTheDocument();
		expect( emptyCartBlock ).not.toHaveAttribute( 'hidden' );
		expect( filledCartBlock ).toHaveAttribute( 'hidden' );
	} );
} );
