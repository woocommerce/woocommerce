/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { Form, FormContextType } from '@woocommerce/components';
import { Product } from '@woocommerce/data';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { CategoryField } from '../category-field';
import { ProductCategoryNode } from '../use-category-search';

jest.mock( '@woocommerce/tracks', () => ( { recordEvent: jest.fn() } ) );

jest.mock( '../use-category-search', () => {
	const originalModule = jest.requireActual( '../use-category-search' );
	return {
		getCategoriesTreeWithMissingParents:
			originalModule.getCategoriesTreeWithMissingParents,
		useCategorySearch: jest.fn().mockReturnValue( {
			searchCategories: jest.fn(),
			getFilteredItemsForSelectTree: jest.fn().mockReturnValue( [] ),
			isSearching: false,
			categoriesSelectList: [],
			categoryTreeKeyValues: {},
		} ),
	};
} );

describe( 'CategoryField', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'should render a dropdown select control', () => {
		const { queryByText, queryByPlaceholderText } = render(
			<Form initialValues={ { categories: [] } }>
				{ ( { getInputProps }: FormContextType< Product > ) => (
					<CategoryField
						label="Categories"
						placeholder="Search or create category…"
						{ ...getInputProps< ProductCategoryNode[] >(
							'categories'
						) }
					/>
				) }
			</Form>
		);
		queryByPlaceholderText( 'Search or create category…' )?.focus();
		expect( queryByText( 'Create new' ) ).toBeInTheDocument();
	} );

	it( 'should pass in the selected categories as select control items', () => {
		const { queryAllByText, queryByPlaceholderText } = render(
			<Form
				initialValues={ {
					categories: [
						{ id: 2, name: 'Test' },
						{ id: 5, name: 'Clothing' },
					],
				} }
			>
				{ ( { getInputProps }: FormContextType< Product > ) => (
					<CategoryField
						label="Categories"
						placeholder="Search or create category…"
						{ ...getInputProps< ProductCategoryNode[] >(
							'categories'
						) }
					/>
				) }
			</Form>
		);
		queryByPlaceholderText( 'Search or create category…' )?.focus();
		expect( queryAllByText( 'Test' ) ).toHaveLength( 2 );
		expect( queryAllByText( 'Clothing' ) ).toHaveLength( 2 );
	} );
} );
