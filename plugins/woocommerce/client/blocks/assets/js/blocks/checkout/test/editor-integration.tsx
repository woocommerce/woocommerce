/**
 * External dependencies
 */
import { act, screen, waitFor } from '@testing-library/react';
import { registerCheckoutFilters } from '@woocommerce/blocks-checkout';
import { type BlockAttributes } from '@wordpress/blocks';
import { getByLabelText, getByRole } from '@testing-library/dom';
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

async function setup( attributes: BlockAttributes ) {
	const testBlock = [ { name: 'woocommerce/checkout', attributes } ];
	return initializeEditor( testBlock );
}

describe( 'Checkout block editor integration', () => {
	beforeAll( async () => {
		// Register a checkout filter to allow `core/table` block in all Checkout inner blocks,
		// add `core/audio` into the woocommerce/checkout-totals-block specifically
		registerCheckoutFilters( 'woo-test-namespace', {
			// @ts-expect-error - The types for the checkout filters are not defined.
			additionalCartCheckoutInnerBlockTypes: (
				value: string[],
				extensions,
				{ block }: { block: string }
			) => {
				value.push( 'core/table' );
				if ( block === 'woocommerce/checkout-totals-block' ) {
					value.push( 'core/audio' );
				}
				return value;
			},
		} );
	} );

	it( 'inner blocks can be added/removed by filters', async () => {
		await setup( {} );

		// Verify Checkout block is properly initialized in the editor.
		expect( screen.getByLabelText( /^Block: Checkout$/i ) ).toBeVisible();

		await waitFor( () => {
			expect(
				screen.getByLabelText( /^Block: Order Summary$/i )
			).toBeVisible();
		} );

		const orderSummaryBlock = screen.getByLabelText(
			/^Block: Order Summary$/i
		);

		// Block appender
		if ( ! orderSummaryBlock.parentElement ) {
			throw new Error( 'Order Summary block parent element not found.' );
		}

		const orderSummaryAppendButton = getByLabelText(
			orderSummaryBlock.parentElement,
			'Add block'
		);

		// Open the block inserter for Checkout Totals.
		await act( async () => {
			await userEvent.click( orderSummaryAppendButton );
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
			expect( tableOption ).toBeInTheDocument();

			// Verify Audio option is available (added only for checkout totals block).
			expect( audioOption ).toBeInTheDocument();
		} );

		await act( async () => {
			await userEvent.click(
				screen.getByRole( 'option', { name: /Table/i } )
			);
			await userEvent.click( orderSummaryBlock );
		} );

		await selectBlock( 'Block: Contact Information' );
		const contactInfoBlock = screen.getByLabelText(
			/^Block: Contact Information$/i
		);
		// Find and click the first "Add block" button within the Order Summary context.
		const contactInfoAddButton = getByRole( contactInfoBlock, 'button', {
			name: /^Add block$/i,
		} );

		// Open the block inserter for Order Summary.
		await act( async () => {
			await userEvent.click( contactInfoAddButton );
		} );

		// Verify Table option is still available (general filter applies to all checkout blocks).
		const contactInformationTableOption = screen.getByRole( 'option', {
			name: /Table/i,
		} );
		await waitFor( () => {
			expect( contactInformationTableOption ).toBeVisible();
		} );

		// Verify Audio option is NOT available (block-specific filter only applies to Checkout Totals).
		const ContactInformationAudioOption = screen.queryByRole( 'option', {
			name: /Audio/i,
		} );
		expect( ContactInformationAudioOption ).not.toBeInTheDocument();
	} );
} );
