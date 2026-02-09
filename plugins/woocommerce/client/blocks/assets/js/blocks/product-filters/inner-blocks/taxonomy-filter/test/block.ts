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

// Mock getSetting to return the taxonomy data we need
jest.mock( '@woocommerce/settings', () => {
	const originalModule = jest.requireActual( '@woocommerce/settings' );
	return {
		...originalModule,
		getSetting: jest.fn( ( key, defaultValue ) => {
			if ( key === 'filterableProductTaxonomies' ) {
				return [
					{
						name: 'product_cat',
						label: 'Category',
					},
					{
						name: 'product_tag',
						label: 'Tag',
					},
				];
			}
			// Use the original getSetting for other keys
			return originalModule.getSetting( key, defaultValue );
		} ),
		getSettingWithCoercion: jest.fn(
			( key: string, defaultValue: unknown ) => {
				return defaultValue;
			}
		),
	};
} );

// Mock WooCommerce schema selectors to prevent namespace errors
jest.mock( '../../../../../data/schema/selectors', () => ( {
	getRoute: jest.fn( () => null ),
	getRoutes: jest.fn( () => ( {
		'/wc/store/v1': {},
	} ) ),
} ) );

async function setup( attributes: BlockAttributes ) {
	const testBlock = [
		{
			name: 'woocommerce/product-filter-taxonomy',
			attributes: {
				...attributes,
				isPreview: true,
			},
		},
	];
	return initializeEditor( testBlock );
}

/**
 * Helper function to enable a control via the dropdown menu
 */
function enableControl( controlName: string ) {
	const optionsButton = screen.getByRole( 'button', {
		name: /Display Settings options/i,
	} );
	fireEvent.click( optionsButton );

	const controlToggle = screen.getByRole( 'menuitemcheckbox', {
		name: new RegExp( controlName, 'i' ),
	} );
	fireEvent.click( controlToggle );

	fireEvent.click( optionsButton ); // Close menu
}

describe( 'Taxonomy Filter block', () => {
	describe( 'Initial display', () => {
		test( 'should show notice when no taxonomy is selected', async () => {
			await setup( { taxonomy: '' } );
			await selectBlock( /Block: Taxonomy Filter/i );

			const block = within(
				screen.getByLabelText( /Block: Taxonomy Filter/i )
			);

			expect(
				block.getByText(
					/Please select a taxonomy to use this filter!/i
				)
			).toBeInTheDocument();
		} );

		test( 'should display taxonomy filter when taxonomy is selected', async () => {
			await setup( { taxonomy: 'product_cat' } );
			await selectBlock( /Block: Category Filter/i );

			const block = within(
				screen.getByLabelText( /Block: Category Filter/i )
			);

			// Should display the taxonomy label as heading
			expect( block.getByText( /Category/i ) ).toBeInTheDocument();
		} );
	} );

	describe( 'Inspector controls', () => {
		beforeEach( async () => {
			await setup( { taxonomy: 'product_cat' } );
			await selectBlock( /Block: Category Filter/i );
		} );

		test( 'should show product counts toggle', () => {
			const productCountsToggle = screen.getByRole( 'checkbox', {
				name: /Product counts/i,
			} );

			expect( productCountsToggle ).toBeInTheDocument();
			expect( productCountsToggle ).not.toBeChecked();
		} );

		test( 'should allow toggling product counts', async () => {
			await selectBlock( /Block: Category Filter/i );

			const block = within(
				screen.getByLabelText( /Block: Category Filter/i )
			);

			// expect the list doesn't have count indicators initially
			expect( block.queryAllByText( /\(\d+\)/ ) ).toHaveLength( 0 );

			const productCountsToggle = screen.getByRole( 'checkbox', {
				name: /Product counts/i,
			} );

			await act( async () => {
				fireEvent.click( productCountsToggle );
			} );

			expect( productCountsToggle ).toBeChecked();

			// expect the list has count indicators after toggling
			expect( block.queryAllByText( /\(\d+\)/ ).length ).toBeGreaterThan(
				0
			);
		} );
	} );

	describe( 'Advanced controls', () => {
		beforeEach( async () => {
			await setup( { taxonomy: 'product_cat' } );
			await selectBlock( /Block: Category Filter/i );
		} );

		test( 'should show sort order control when enabled', () => {
			enableControl( 'Sort Order' );

			const sortOrderSelect = screen.getByRole( 'combobox', {
				name: /Sort Order/i,
			} );

			expect( sortOrderSelect ).toBeInTheDocument();
			expect( sortOrderSelect ).toHaveValue( 'count-desc' );
		} );

		test( 'should allow changing sort order when enabled', () => {
			enableControl( 'Sort Order' );

			const sortOrderSelect = screen.getByRole( 'combobox', {
				name: /Sort Order/i,
			} );

			fireEvent.change( sortOrderSelect, {
				target: { value: 'name-asc' },
			} );

			expect( sortOrderSelect ).toHaveValue( 'name-asc' );
		} );

		test( 'should show hide empty items toggle when enabled', () => {
			enableControl( 'Hide items with no products' );

			const hideEmptyToggle = screen.getByRole( 'checkbox', {
				name: /Hide items with no products/i,
			} );

			expect( hideEmptyToggle ).toBeInTheDocument();
			expect( hideEmptyToggle ).toBeChecked(); // Default is true
		} );

		test( 'should allow toggling hide empty items when enabled', () => {
			enableControl( 'Hide items with no products' );

			const hideEmptyToggle = screen.getByRole( 'checkbox', {
				name: /Hide items with no products/i,
			} );

			fireEvent.click( hideEmptyToggle );

			expect( hideEmptyToggle ).not.toBeChecked();
		} );
	} );

	describe( 'Attribute combinations', () => {
		test( 'should handle all attributes set correctly', async () => {
			await setup( {
				taxonomy: 'product_cat',
				showCounts: true,
				displayStyle: 'dropdown',
				sortOrder: 'name-asc',
				hideEmpty: false,
			} );
			await selectBlock( /Block: Category Filter/i );

			const block = within(
				screen.getByLabelText( /Block: Category Filter/i )
			);

			// Should display the heading
			expect( block.getByText( /Category/i ) ).toBeInTheDocument();

			// Check that all controls reflect the set attributes
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

		test( 'should handle product tags taxonomy', async () => {
			await setup( {
				taxonomy: 'product_tag',
				showCounts: true,
			} );
			await selectBlock( /Block: Tag Filter/i );

			const block = within(
				screen.getByLabelText( /Block: Tag Filter/i )
			);

			// Should display the taxonomy label as heading
			expect( block.getByText( /Tag/i ) ).toBeInTheDocument();

			// Should show count indicators since showCounts is true
			expect( block.queryAllByText( /\(\d+\)/ ).length ).toBeGreaterThan(
				0
			);
		} );
	} );
} );
