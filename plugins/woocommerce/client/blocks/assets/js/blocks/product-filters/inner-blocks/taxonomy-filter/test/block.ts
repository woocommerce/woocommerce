/**
 * External dependencies
 */
import type { BlockAttributes } from '@wordpress/blocks';
import '@testing-library/jest-dom';
import { act, fireEvent, screen, within } from '@testing-library/react';

/**
 * Internal dependencies
 */
import {
	initializeEditor,
	selectBlock,
} from '../../../../../../../tests/integration/helpers/integration-test-editor';
import '../';
import '../../checkbox-list';

// Mock the getSetting function to return mock taxonomy data
jest.mock( '@woocommerce/settings', () => ( {
	getSetting: jest.fn( ( key: string, defaultValue: any ) => {
		if ( key === 'filterableProductTaxonomies' ) {
			return [
				{
					name: 'product_cat',
					label: 'Product Categories',
					labels: { singular_name: 'Category' },
				},
				{
					name: 'product_tag',
					label: 'Product Tags',
					labels: { singular_name: 'Tag' },
				},
			];
		}
		return defaultValue;
	} ),
} ) );

async function setup( attributes: BlockAttributes ) {
	const testBlock = [
		{
			name: 'woocommerce/product-filter-taxonomy',
			attributes,
		},
	];
	return initializeEditor( testBlock );
}

describe( 'Taxonomy Filter block', () => {
	describe( 'Initial display', () => {
		test( 'should show notice when no taxonomy is selected', async () => {
			await setup( { taxonomy: '' } );
			await selectBlock( /Block: Taxonomy/i );

			const block = within( screen.getByLabelText( /Block: Taxonomy/i ) );

			expect(
				block.getByText(
					/Please select a taxonomy to use this filter!/i
				)
			).toBeInTheDocument();
		} );

		test( 'should display taxonomy filter with preview data when taxonomy is selected', async () => {
			await setup( { taxonomy: 'product_cat' } );
			await selectBlock( /Block: Product Categories Filter/i );

			const block = within(
				screen.getByLabelText( /Block: Product Categories Filter/i )
			);

			// Should display the taxonomy label as heading
			expect(
				block.getByText( /Product Categories/i )
			).toBeInTheDocument();
		} );
	} );

	describe( 'Inspector controls', () => {
		beforeEach( async () => {
			await setup( { taxonomy: 'product_cat' } );
			await selectBlock( /Block: Product Categories Filter/i );
		} );

		test( 'should show taxonomy selection control', () => {
			const taxonomySelect = screen.getByRole( 'combobox', {
				name: /Taxonomy/i,
			} );

			expect( taxonomySelect ).toBeInTheDocument();
			expect( taxonomySelect ).toHaveValue( 'product_cat' );
		} );

		test( 'should allow changing taxonomy selection', async () => {
			const taxonomySelect = screen.getByRole( 'combobox', {
				name: /Taxonomy/i,
			} );

			await act( async () => {
				fireEvent.change( taxonomySelect, {
					target: { value: 'product_tag' },
				} );
			} );

			expect( taxonomySelect ).toHaveValue( 'product_tag' );
		} );

		test( 'should show sort order control with default value when enabled', () => {
			// First open the dropdown menu to enable the sort order control
			const optionsButton = screen.getByRole( 'button', {
				name: /Taxonomy Filter Settings options/i,
			} );
			fireEvent.click( optionsButton );

			// Enable the sort order control
			const showSortOrderToggle = screen.getByRole( 'menuitemcheckbox', {
				name: /Sort Order/i,
			} );
			fireEvent.click( showSortOrderToggle );

			// Close the menu
			fireEvent.click( optionsButton );

			const sortOrderSelect = screen.getByRole( 'combobox', {
				name: /Sort Order/i,
			} );

			expect( sortOrderSelect ).toBeInTheDocument();
			expect( sortOrderSelect ).toHaveValue( 'count-desc' );
		} );

		test( 'should allow changing sort order when enabled', () => {
			// First open the dropdown menu to enable the sort order control
			const optionsButton = screen.getByRole( 'button', {
				name: /Taxonomy Filter Settings options/i,
			} );
			fireEvent.click( optionsButton );

			// Enable the sort order control
			const showSortOrderToggle = screen.getByRole( 'menuitemcheckbox', {
				name: /Sort Order/i,
			} );
			fireEvent.click( showSortOrderToggle );

			// Close the menu
			fireEvent.click( optionsButton );

			const sortOrderSelect = screen.getByRole( 'combobox', {
				name: /Sort Order/i,
			} );

			fireEvent.change( sortOrderSelect, {
				target: { value: 'name-asc' },
			} );

			expect( sortOrderSelect ).toHaveValue( 'name-asc' );
		} );

		test( 'should show product counts toggle', () => {
			const productCountsToggle = screen.getByRole( 'checkbox', {
				name: /Product counts/i,
			} );

			expect( productCountsToggle ).toBeInTheDocument();
			expect( productCountsToggle ).not.toBeChecked();
		} );

		test( 'should allow toggling product counts', () => {
			const productCountsToggle = screen.getByRole( 'checkbox', {
				name: /Product counts/i,
			} );

			fireEvent.click( productCountsToggle );

			expect( productCountsToggle ).toBeChecked();
		} );

		test( 'should show hide empty items toggle when enabled', () => {
			// First open the dropdown menu to enable the hide empty control
			const optionsButton = screen.getByRole( 'button', {
				name: /Taxonomy Filter Settings options/i,
			} );
			fireEvent.click( optionsButton );

			// Enable the hide empty control
			const showHideEmptyToggle = screen.getByRole( 'menuitemcheckbox', {
				name: /Hide items with no products/i,
			} );
			fireEvent.click( showHideEmptyToggle );

			// Close the menu
			fireEvent.click( optionsButton );

			const hideEmptyToggle = screen.getByRole( 'checkbox', {
				name: /Hide items with no products/i,
			} );

			expect( hideEmptyToggle ).toBeInTheDocument();
			expect( hideEmptyToggle ).toBeChecked(); // Default is true
		} );

		test( 'should allow toggling hide empty items when enabled', () => {
			// First open the dropdown menu to enable the hide empty control
			const optionsButton = screen.getByRole( 'button', {
				name: /Taxonomy Filter Settings options/i,
			} );
			fireEvent.click( optionsButton );

			// Enable the hide empty control
			const showHideEmptyToggle = screen.getByRole( 'menuitemcheckbox', {
				name: /Hide items with no products/i,
			} );
			fireEvent.click( showHideEmptyToggle );

			// Close the menu
			fireEvent.click( optionsButton );

			const hideEmptyToggle = screen.getByRole( 'checkbox', {
				name: /Hide items with no products/i,
			} );

			fireEvent.click( hideEmptyToggle );

			expect( hideEmptyToggle ).not.toBeChecked();
		} );
	} );

	describe( 'Settings panel', () => {
		beforeEach( async () => {
			await setup( { taxonomy: 'product_cat' } );
			await selectBlock( /Block: Product Categories Filter/i );
		} );

		test( 'should reset all settings when reset button is clicked', async () => {
			// First enable hidden controls
			const optionsButton = screen.getByRole( 'button', {
				name: /Taxonomy Filter Settings options/i,
			} );
			await act( async () => {
				fireEvent.click( optionsButton );
			} );

			// Enable sort order control
			const showSortOrderToggle = screen.getByRole( 'menuitemcheckbox', {
				name: /Sort Order/i,
			} );
			await act( async () => {
				fireEvent.click( showSortOrderToggle );
			} );

			// Find and click the reset button
			const resetButton = screen.getByRole( 'menuitem', {
				name: /Reset all/i,
			} );
			await act( async () => {
				fireEvent.click( resetButton );
			} );

			// Close the menu
			await act( async () => {
				fireEvent.click( optionsButton );
			} );

			// Check that the controls are reset/hidden
			const taxonomySelect = screen.getByRole( 'combobox', {
				name: /Taxonomy/i,
			} );
			const productCountsToggle = screen.getByRole( 'checkbox', {
				name: /Product counts/i,
			} );

			expect( taxonomySelect ).toHaveValue( '' );
			expect( productCountsToggle ).not.toBeChecked();

			// Sort order should be hidden again after reset
			expect(
				screen.queryByRole( 'combobox', { name: /Sort Order/i } )
			).not.toBeInTheDocument();
		} );
	} );

	describe( 'Different attribute combinations', () => {
		test( 'should handle all attributes set', async () => {
			await setup( {
				taxonomy: 'product_cat',
				showCounts: true,
				displayStyle: 'dropdown',
				sortOrder: 'name-asc',
				hideEmpty: false,
				isPreview: false,
			} );
			await selectBlock( /Block: Product Categories Filter/i );

			const block = within(
				screen.getByLabelText( /Block: Product Categories Filter/i )
			);

			// Should display the heading
			expect(
				block.getByText( /Product Categories/i )
			).toBeInTheDocument();

			// Check that all controls reflect the set attributes
			expect(
				screen.getByRole( 'combobox', { name: /Taxonomy/i } )
			).toHaveValue( 'product_cat' );
			expect(
				screen.getByRole( 'checkbox', { name: /Product counts/i } )
			).toBeChecked();

			// Since we set attributes, these controls should already be visible
			expect(
				screen.getByRole( 'combobox', { name: /Sort Order/i } )
			).toHaveValue( 'name-asc' );
			expect(
				screen.getByRole( 'checkbox', {
					name: /Hide items with no products/i,
				} )
			).not.toBeChecked();
		} );
	} );
} );
