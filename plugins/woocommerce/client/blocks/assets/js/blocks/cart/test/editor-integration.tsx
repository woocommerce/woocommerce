/**
 * External dependencies
 */
import { act, screen, waitFor } from '@testing-library/react';
import { registerCheckoutFilters } from '@woocommerce/blocks-checkout';
import { type BlockAttributes } from '@wordpress/blocks';
import { getAllByRole, getByLabelText } from '@testing-library/dom';
import { userEvent } from '@testing-library/user-event';

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

	it( 'inner blocks can be added/removed by filters', async () => {
		await setup( {} );

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

		// Verify Table option is available (should be available on all blocks).
		expect( tableOption ).toBeVisible();

		// Verify Audio option is available (added only for order summary block).
		expect( audioOption ).toBeVisible();

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
		expect( filledCartTableOption ).toBeVisible();

		// Verify Audio option is NOT available (block-specific filter only applies to Order Summary).
		const filledCartAudioOption = screen.queryByRole( 'option', {
			name: /Audio/i,
		} );
		expect( filledCartAudioOption ).not.toBeInTheDocument();
	} );

	it( 'can convert to Empty Cart block', async () => {
		// Setup the cart block with default attributes (filled cart view)
		await setup( {} );

		// Verify Cart block is properly initialized in the editor
		expect( screen.getByLabelText( /^Block: Cart$/i ) ).toBeVisible();

		const filledCartBlock = screen.getByLabelText( /Block: Filled Cart/i );
		const emptyCartBlock = screen.getByLabelText( /Block: Empty Cart/i );

		expect( filledCartBlock ).toBeVisible();
		expect( filledCartBlock ).not.toHaveAttribute( 'hidden' );
		expect( emptyCartBlock ).toBeInTheDocument();
		expect( emptyCartBlock ).toHaveAttribute( 'hidden' );

		await waitFor( () => {
			expect(
				screen.getByLabelText( /Block: Filled Cart$/i )
			).toBeVisible();
		} );

		await selectBlock( /Block: Filled Cart/i );

		const selectParentBlockButton = screen.getByRole( 'button', {
			name: /Select parent block: Cart/i,
		} );

		await act( async () => {
			await userEvent.click( selectParentBlockButton );
		} );

		const switchViewButton = screen.getByRole( 'button', {
			name: /Switch view/i,
		} );

		await act( async () => {
			await userEvent.click( switchViewButton );
		} );

		expect( switchViewButton ).toHaveAttribute( 'aria-expanded', 'true' );

		const emptyCartButton = screen.getByRole( 'menuitem', {
			name: /Empty Cart/i,
		} );

		await act( async () => {
			await userEvent.click( emptyCartButton );
		} );

		expect(
			screen.getByLabelText( /^Block: Empty Cart$/i )
		).toBeInTheDocument();
		expect( emptyCartBlock ).toHaveAttribute( 'hidden', '' );
		expect( emptyCartBlock ).toHaveAttribute( 'hidden' );

		// Go back to filled cart
		await act( async () => {
			await userEvent.click( switchViewButton );
		} );

		expect( switchViewButton ).toHaveAttribute( 'aria-expanded', 'true' );

		const filledCartButton = screen.getByRole( 'menuitem', {
			name: /Filled Cart/i,
		} );

		await act( async () => {
			await userEvent.click( filledCartButton );
		} );

		expect( emptyCartBlock ).toHaveAttribute( 'hidden' );
		expect( filledCartBlock ).not.toHaveAttribute( 'hidden' );
	} );
} );
