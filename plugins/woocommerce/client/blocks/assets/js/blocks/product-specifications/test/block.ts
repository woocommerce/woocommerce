/**
 * External dependencies
 */
import type { BlockAttributes } from '@wordpress/blocks';
import '@testing-library/jest-dom';
import { fireEvent, screen, waitFor, within } from '@testing-library/react';

/**
 * Internal dependencies
 */
import {
	initializeEditor,
	selectBlock,
} from '../../../../../tests/integration/helpers/integration-test-editor';
import '../';

async function setup( attributes: BlockAttributes ) {
	const testBlock = [
		{
			name: 'woocommerce/product-specifications',
			attributes,
		},
	];
	return initializeEditor( testBlock );
}

describe( 'Product Specifications block', () => {
	describe( 'Display settings', () => {
		beforeEach( async () => {
			await setup( {} );
			await selectBlock( /Block: Product Specifications/i );

			await waitFor( () => {
				expect(
					screen.getByRole( 'button', { name: /display settings/i } )
				).toBeVisible();
			} );

			const displaySettings = screen.getByRole( 'button', {
				name: /display settings/i,
			} );

			if ( displaySettings.getAttribute( 'aria-expanded' ) !== 'true' ) {
				fireEvent.click( displaySettings );
			}

			// Wait for ToolsPanel to fully initialize and settle.
			await waitFor( () => {
				expect(
					screen.getByRole( 'checkbox', { name: /Show Weight/i } )
				).toBeVisible();
			} );
		} );

		test( 'should show all sections by default', () => {
			const block = within(
				screen.getByLabelText( /Block: Product Specifications/i )
			);

			expect( block.getByText( /Weight/i ) ).toBeInTheDocument();
			expect( block.getByText( /Dimensions/i ) ).toBeInTheDocument();
			expect( block.getByText( /Test Attribute/i ) ).toBeInTheDocument();

			// Verify toggle controls are checked by default
			expect(
				screen.getByRole( 'checkbox', { name: /Show Weight/i } )
			).toBeChecked();
			expect(
				screen.getByRole( 'checkbox', {
					name: /Show Dimensions/i,
				} )
			).toBeChecked();
			expect(
				screen.getByRole( 'checkbox', {
					name: /Show Attributes/i,
				} )
			).toBeChecked();
		} );

		test( 'should hide weight section when toggled off', async () => {
			const block = within(
				screen.getByLabelText( /Block: Product Specifications/i )
			);

			const weightCheckbox = screen.getByRole( 'checkbox', {
				name: /Show Weight/i,
			} );

			fireEvent.click( weightCheckbox );

			await waitFor( () => {
				expect(
					block.queryByText( /Weight/i )
				).not.toBeInTheDocument();
			} );

			expect( block.getByText( /Dimensions/i ) ).toBeInTheDocument();
			expect( block.getByText( /Test Attribute/i ) ).toBeInTheDocument();
		} );

		test( 'should hide dimensions section when toggled off', async () => {
			const block = within(
				screen.getByLabelText( /Block: Product Specifications/i )
			);

			const dimensionsCheckbox = screen.getByRole( 'checkbox', {
				name: /Show Dimensions/i,
			} );

			fireEvent.click( dimensionsCheckbox );

			await waitFor( () => {
				expect(
					block.queryByText( /Dimensions/i )
				).not.toBeInTheDocument();
			} );

			expect( block.getByText( /Weight/i ) ).toBeInTheDocument();
			expect( block.getByText( /Test Attribute/i ) ).toBeInTheDocument();
		} );

		test( 'should hide attributes section when toggled off', async () => {
			const block = within(
				screen.getByLabelText( /Block: Product Specifications/i )
			);

			const attributesCheckbox = screen.getByRole( 'checkbox', {
				name: /Show Attributes/i,
			} );

			fireEvent.click( attributesCheckbox );

			await waitFor( () => {
				expect(
					block.queryByText( /Test Attribute/i )
				).not.toBeInTheDocument();
			} );

			expect( block.getByText( /Weight/i ) ).toBeInTheDocument();
			expect( block.getByText( /Dimensions/i ) ).toBeInTheDocument();
		} );

		test( 'should restore visibility when sections are toggled back on', async () => {
			const block = within(
				screen.getByLabelText( /Block: Product Specifications/i )
			);

			// First hide all sections
			fireEvent.click(
				screen.getByRole( 'checkbox', { name: /Show Weight/i } )
			);
			fireEvent.click(
				screen.getByRole( 'checkbox', { name: /Show Dimensions/i } )
			);
			fireEvent.click(
				screen.getByRole( 'checkbox', { name: /Show Attributes/i } )
			);

			// Wait for all items to be hidden.
			await waitFor( () => {
				expect(
					block.queryByText( /Weight/i )
				).not.toBeInTheDocument();
				expect(
					block.queryByText( /Dimensions/i )
				).not.toBeInTheDocument();
				expect(
					block.queryByText( /Test Attribute/i )
				).not.toBeInTheDocument();
			} );

			// Then show them all again
			fireEvent.click(
				screen.getByRole( 'checkbox', { name: /Show Weight/i } )
			);
			fireEvent.click(
				screen.getByRole( 'checkbox', { name: /Show Dimensions/i } )
			);
			fireEvent.click(
				screen.getByRole( 'checkbox', { name: /Show Attributes/i } )
			);

			// Wait for all items to be shown.
			await waitFor( () => {
				expect( block.getByText( /Weight/i ) ).toBeInTheDocument();
				expect( block.getByText( /Dimensions/i ) ).toBeInTheDocument();
				expect(
					block.getByText( /Test Attribute/i )
				).toBeInTheDocument();
			} );
		} );
	} );
} );
