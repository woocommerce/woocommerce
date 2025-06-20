/**
 * External dependencies
 */
import { act, screen } from '@testing-library/react';
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
		expect( screen.getByLabelText( /^Block: Cart$/i ) ).toBeInTheDocument();

		// Test Order Summary block - should have both Table and Audio options (specific filter applied).
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
		expect( tableOption ).toBeInTheDocument();

		// Verify Audio option is available (added only for order summary block).
		expect( audioOption ).toBeInTheDocument();

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
		expect( filledCartTableOption ).toBeInTheDocument();

		// Verify Audio option is NOT available (block-specific filter only applies to Order Summary).
		const filledCartAudioOption = screen.queryByRole( 'option', {
			name: /Audio/i,
		} );
		expect( filledCartAudioOption ).not.toBeInTheDocument();
	} );
} );
