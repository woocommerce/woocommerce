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

			// Open display settings panel
			const displaySettings = screen.getByRole( 'button', {
				name: /Display Settings/i,
			} );
			if ( displaySettings.getAttribute( 'aria-expanded' ) !== 'true' ) {
				fireEvent.click( displaySettings );
			}
		} );

		test( 'should handle section visibility toggles correctly', () => {
			const block = within(
				screen.getByLabelText( /Block: Product Specifications/i )
			);

			// Test initial state - all sections should be visible by default
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

			// Test hiding weight section
			fireEvent.click(
				screen.getByRole( 'checkbox', { name: /Show Weight/i } )
			);
			expect( block.queryByText( /Weight/i ) ).not.toBeInTheDocument();
			expect( block.getByText( /Dimensions/i ) ).toBeInTheDocument();
			expect( block.getByText( /Test Attribute/i ) ).toBeInTheDocument();

			// Test hiding dimensions section
			fireEvent.click(
				screen.getByRole( 'checkbox', {
					name: /Show Dimensions/i,
				} )
			);
			expect( block.queryByText( /Weight/i ) ).not.toBeInTheDocument();
			expect(
				block.queryByText( /Dimensions/i )
			).not.toBeInTheDocument();
			expect( block.getByText( /Test Attribute/i ) ).toBeInTheDocument();

			// Test hiding attributes section
			fireEvent.click(
				screen.getByRole( 'checkbox', {
					name: /Show Attributes/i,
				} )
			);
			expect( block.queryByText( /Weight/i ) ).not.toBeInTheDocument();
			expect(
				block.queryByText( /Dimensions/i )
			).not.toBeInTheDocument();
			expect(
				block.queryByText( /Test Attribute/i )
			).not.toBeInTheDocument();

			// Test restoring visibility by toggling all sections back on
			fireEvent.click(
				screen.getByRole( 'checkbox', { name: /Show Weight/i } )
			);
			fireEvent.click(
				screen.getByRole( 'checkbox', {
					name: /Show Dimensions/i,
				} )
			);
			fireEvent.click(
				screen.getByRole( 'checkbox', {
					name: /Show Attributes/i,
				} )
			);

			// Verify all sections are visible again
			expect( block.getByText( /Weight/i ) ).toBeInTheDocument();
			expect( block.getByText( /Dimensions/i ) ).toBeInTheDocument();
			expect( block.getByText( /Test Attribute/i ) ).toBeInTheDocument();
		} );
	} );
} );
