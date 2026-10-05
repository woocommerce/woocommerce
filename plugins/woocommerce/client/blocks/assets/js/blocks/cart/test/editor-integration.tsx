/* eslint-disable testing-library/no-unnecessary-act -- Gutenberg schedules asynchronous updates after these interactions. */
/**
 * External dependencies
 */
import {
	act,
	screen,
	waitFor,
	getAllByRole,
	getByLabelText,
} from '@testing-library/react';
import { registerCheckoutFilters } from '@woocommerce/blocks-checkout';
import { type BlockAttributes } from '@wordpress/blocks';
import { userEvent } from '@testing-library/user-event';
import { previewCart } from '@woocommerce/resource-previews';
import { dispatch } from '@wordpress/data';
import { CART_STORE_KEY as storeKey } from '@woocommerce/block-data';

// Use the editor's hook registry so WooCommerce's editor filters run.
jest.mock( '@wordpress/hooks', () => {
	const { createRequire } = jest.requireActual( 'module' );
	const editorRequire = createRequire(
		require.resolve( '@wordpress/block-editor' )
	);
	return jest.requireActual( editorRequire.resolve( '@wordpress/hooks' ) );
} );

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
import '../../product-elements-blocks/sale-badge/index';
import '../../product-elements-blocks/image/index';
import '../../product-elements-blocks/price/index';
import '../../product-elements-blocks/button/index';
import '../../product-elements-blocks/title/index';
import '../../product-template/index.tsx';
import '../../product-collection/index.tsx';

async function setup(
	attributes: BlockAttributes,
	options: { useSubRegistry?: boolean } = {}
) {
	const testBlock = [ { name: 'woocommerce/cart', attributes } ];
	return initializeEditor( testBlock, {}, options );
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

	it( 'inner blocks can be added/removed by filters', async () => {
		await setup( {} );
		await selectBlock( /^Block: Filled Cart$/i );

		// Verify Cart block is properly initialized in the editor.
		await waitFor( () => {
			expect( screen.getByLabelText( /^Block: Cart$/i ) ).toBeVisible();
			// Test Order Summary block - should have both Table and Audio options (specific filter applied).
		} );

		await waitFor( () => {
			expect(
				screen.getByLabelText( /^Block: Order Summary$/i )
			).toBeVisible();
		} );

		await selectBlock( /^Block: Order Summary$/i );

		const orderSummaryBlock = screen.getByLabelText(
			/^Block: Order Summary$/i
		);

		const orderSummaryAddButton = getByLabelText(
			orderSummaryBlock,
			/^Add block$/i
		);

		// Open the block inserter for Order Summary.
		await act( async () => {
			await userEvent.click( orderSummaryAddButton );
		} );

		const options = screen.getAllByRole( 'option' );
		const tableOption = options.find(
			( element ) => element.textContent === 'Table'
		);
		const audioOption = options.find(
			( element ) => element.textContent === 'Audio'
		);

		await waitFor( () => {
			// Verify Table option is available (should be available on all blocks).
			expect( tableOption ).toBeVisible();

			// Verify Audio option is available (added only for order summary block).
			expect( audioOption ).toBeVisible();
		} );

		// Test Filled Cart block - should only have Table option (no block-specific Audio filter).
		const filledCartBlock = screen.getByLabelText( /Block: Filled Cart/i );
		await act( async () => {
			await userEvent.click( filledCartBlock );
		} );

		if ( ! filledCartBlock.parentElement ) {
			throw new Error( 'Filled Cart block parent element not found.' );
		}

		// Find and click the first "Add block" button within the Filled Cart context.
		const filledCartAddButtons = getAllByRole(
			filledCartBlock.parentElement,
			'button',
			{
				name: /^Add block$/i,
			}
		);

		// Open the block inserter for Filled Cart.
		await act( async () => {
			await userEvent.click( filledCartAddButtons[ 0 ] );
		} );

		// Verify Table option is still available (general filter applies to all cart blocks).
		const filledCartTableOption = screen.getByRole( 'option', {
			name: /Table/i,
		} );
		await waitFor( () => {
			expect( filledCartTableOption ).toBeVisible();
		} );

		// Verify Audio option is NOT available (block-specific filter only applies to Order Summary).
		const filledCartAudioOption = screen.queryByRole( 'option', {
			name: /Audio/i,
		} );
		expect( filledCartAudioOption ).not.toBeInTheDocument();
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

	it( 'can switch between filled and empty cart previews', async () => {
		// The view switcher uses global data selectors and actions.
		await setup( {}, { useSubRegistry: false } );
		await selectBlock( /^Block: Filled Cart$/i );

		const filledCartBlock =
			screen.getByLabelText( /^Block: Filled Cart$/i );
		const emptyCartBlock = screen.getByLabelText( /^Block: Empty Cart$/i );
		expect( filledCartBlock ).toBeVisible();
		expect( emptyCartBlock ).not.toBeVisible();

		await selectBlock( /^Block: Cart$/i );
		await act( () =>
			userEvent.click(
				screen.getByRole( 'button', { name: /Switch view/i } )
			)
		);
		await act( () =>
			userEvent.click(
				screen.getByRole( 'menuitem', { name: /Empty Cart/i } )
			)
		);
		expect( emptyCartBlock ).toBeVisible();
		expect( filledCartBlock ).not.toBeVisible();

		await act( () =>
			userEvent.click(
				screen.getByRole( 'button', { name: /Switch view/i } )
			)
		);
		await act( () =>
			userEvent.click(
				screen.getByRole( 'menuitem', { name: /Filled Cart/i } )
			)
		);
		expect( filledCartBlock ).toBeVisible();
		expect( emptyCartBlock ).not.toBeVisible();
	} );
} );
