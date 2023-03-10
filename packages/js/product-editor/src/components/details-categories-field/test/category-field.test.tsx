/**
 * External dependencies
 */
import { ReactElement, Component } from 'react';
import { render, fireEvent } from '@testing-library/react';
import { Form, FormContext } from '@woocommerce/components';
import { Product, ProductCategory } from '@woocommerce/data';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { CategoryField } from '../category-field';
import {
	getCategoriesTreeWithMissingParents,
	useCategorySearch,
} from '../use-category-search';

const mockCategoryList = [
	{ id: 1, name: 'Clothing', parent: 0 },
	{ id: 2, name: 'Hoodies', parent: 1 },
	{ id: 3, name: 'Rain gear', parent: 0 },
] as ProductCategory[];

jest.mock( '@woocommerce/components', () => {
	const originalModule = jest.requireActual( '@woocommerce/components' );

	type ChildrenProps = {
		items: ProductCategory[];
		isOpen: boolean;
		highlightedIndex: number;
		getMenuProps: () => Record< string, string >;
		getItemProps: () => Record< string, string >;
		selectItem: ( item: ProductCategory ) => void;
		setInputValue: ( value: string ) => void;
	};
	type SelectControlProps = {
		children: ( {}: ChildrenProps ) => ReactElement | Component;
		items: ProductCategory[];
		label: string;
		initialSelectedItems?: ProductCategory[];
		itemToString?: ( item: ProductCategory | null ) => string;
		getFilteredItems?: (
			allItems: ProductCategory[],
			inputValue: string,
			selectedItems: ProductCategory[]
		) => ProductCategory[];
		multiple?: boolean;
		onInputChange?: ( value: string | undefined ) => void;
		onRemove?: ( item: ProductCategory ) => void;
		onSelect?: ( selected: ProductCategory ) => void;
		placeholder?: string;
		selected: ProductCategory[];
	};

	return {
		...originalModule,
		__experimentalSelectControlMenu: ( {
			children,
		}: {
			children: JSX.Element;
		} ) => children,
		__experimentalSelectControl: ( {
			children,
			items,
			selected,
		}: SelectControlProps ) => {
			return (
				<div>
					[select-control]
					<div className="selected">
						{ selected.map( ( item ) => (
							<div key={ item.id }>{ item.name }</div>
						) ) }
					</div>
					<div className="children">
						{ children( {
							items,
							isOpen: true,
							getMenuProps: () => ( {} ),
							selectItem: () => {},
							highlightedIndex: -1,
							setInputValue: () => {},
							getItemProps: () => ( {} ),
						} ) }
					</div>
				</div>
			);
		},
	};
} );
jest.mock( '@woocommerce/tracks', () => ( { recordEvent: jest.fn() } ) );

jest.mock( '../use-category-search', () => {
	const originalModule = jest.requireActual( '../use-category-search' );
	return {
		getCategoriesTreeWithMissingParents:
			originalModule.getCategoriesTreeWithMissingParents,
		useCategorySearch: jest.fn().mockReturnValue( {
			searchCategories: jest.fn(),
			getFilteredItems: jest.fn(),
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
		const { queryByText } = render(
			<Form initialValues={ { categories: [] } }>
				{ ( { getInputProps }: FormContext< Product > ) => (
					<CategoryField
						label="Categories"
						placeholder="Search or create category…"
						{ ...getInputProps<
							Pick< ProductCategory, 'id' | 'name' >[]
						>( 'categories' ) }
					/>
				) }
			</Form>
		);
		expect( queryByText( '[select-control]' ) ).toBeInTheDocument();
	} );

	it( 'should pass in the selected categories as select control items', () => {
		const { queryByText } = render(
			<Form
				initialValues={ {
					categories: [
						{ id: 2, name: 'Test' },
						{ id: 5, name: 'Clothing' },
					],
				} }
			>
				{ ( { getInputProps }: FormContext< Product > ) => (
					<CategoryField
						label="Categories"
						placeholder="Search or create category…"
						{ ...getInputProps<
							Pick< ProductCategory, 'id' | 'name' >[]
						>( 'categories' ) }
					/>
				) }
			</Form>
		);
		expect( queryByText( 'Test' ) ).toBeInTheDocument();
		expect( queryByText( 'Clothing' ) ).toBeInTheDocument();
	} );

	describe( 'search values', () => {
		beforeEach( async () => {
			const items = await getCategoriesTreeWithMissingParents(
				mockCategoryList,
				''
			);
			( useCategorySearch as jest.Mock ).mockReturnValue( {
				searchCategories: jest.fn(),
				getFilteredItems: jest.fn(),
				isSearching: false,
				categoriesSelectList: items[ 0 ],
				categoryTreeKeyValues: items[ 2 ],
			} );
		} );

		it( 'should display only the parent categories passed in to the categoriesSelectList', () => {
			const { queryByText } = render(
				<Form
					initialValues={ {
						categories: [],
					} }
				>
					{ ( { getInputProps }: FormContext< Product > ) => (
						<CategoryField
							label="Categories"
							placeholder="Search or create category…"
							{ ...getInputProps<
								Pick< ProductCategory, 'id' | 'name' >[]
							>( 'categories' ) }
						/>
					) }
				</Form>
			);
			expect(
				queryByText( mockCategoryList[ 0 ].name )
			).toBeInTheDocument();
			const childParent = queryByText(
				mockCategoryList[ 1 ].name
			)?.parentElement?.closest(
				'.woocommerce-category-field-dropdown__item-children'
			);
			expect( childParent ).toBeInTheDocument();
			expect( childParent?.className ).not.toMatch(
				'woocommerce-category-field-dropdown__item-open'
			);
			expect(
				queryByText( mockCategoryList[ 2 ].name )
			).toBeInTheDocument();
		} );

		it( 'should show selected categories as selected', () => {
			const { getByLabelText } = render(
				<Form
					initialValues={ {
						categories: [ mockCategoryList[ 2 ] ],
					} }
				>
					{ ( { getInputProps }: FormContext< Product > ) => (
						<CategoryField
							label="Categories"
							placeholder="Search or create category…"
							{ ...getInputProps<
								Pick< ProductCategory, 'id' | 'name' >[]
							>( 'categories' ) }
						/>
					) }
				</Form>
			);
			const rainGearCheckbox = getByLabelText(
				mockCategoryList[ 2 ].name
			);
			expect( rainGearCheckbox ).toBeChecked();
			const clothingCheckbox = getByLabelText(
				mockCategoryList[ 0 ].name
			);
			expect( clothingCheckbox ).not.toBeChecked();
		} );

		it( 'should show selected categories as selected', () => {
			const { getByLabelText } = render(
				<Form
					initialValues={ {
						categories: [ mockCategoryList[ 2 ] ],
					} }
				>
					{ ( { getInputProps }: FormContext< Product > ) => (
						<CategoryField
							label="Categories"
							placeholder="Search or create category…"
							{ ...getInputProps<
								Pick< ProductCategory, 'id' | 'name' >[]
							>( 'categories' ) }
						/>
					) }
				</Form>
			);
			const rainGearCheckbox = getByLabelText(
				mockCategoryList[ 2 ].name
			);
			expect( rainGearCheckbox ).toBeChecked();
			const clothingCheckbox = getByLabelText(
				mockCategoryList[ 0 ].name
			);
			expect( clothingCheckbox ).not.toBeChecked();
		} );

		it( 'should include a toggle icon for parents that contain children', () => {
			const { getByLabelText } = render(
				<Form
					initialValues={ {
						categories: [ mockCategoryList[ 2 ] ],
					} }
				>
					{ ( { getInputProps }: FormContext< Product > ) => (
						<CategoryField
							label="Categories"
							placeholder="Search or create category…"
							{ ...getInputProps<
								Pick< ProductCategory, 'id' | 'name' >[]
							>( 'categories' ) }
						/>
					) }
				</Form>
			);
			const rainGearCheckboxParent = getByLabelText(
				mockCategoryList[ 0 ].name
			).parentElement?.closest(
				'.woocommerce-category-field-dropdown__item-content'
			);

			expect(
				rainGearCheckboxParent?.querySelector( 'svg' )
			).toBeInTheDocument();
		} );

		it( 'should allow user to toggle the parents using the svg button', () => {
			const { getByLabelText, queryByText } = render(
				<Form
					initialValues={ {
						categories: [ mockCategoryList[ 2 ] ],
					} }
				>
					{ ( { getInputProps }: FormContext< Product > ) => (
						<CategoryField
							label="Categories"
							placeholder="Search or create category…"
							{ ...getInputProps<
								Pick< ProductCategory, 'id' | 'name' >[]
							>( 'categories' ) }
						/>
					) }
				</Form>
			);
			const rainGearCheckboxParent = getByLabelText(
				mockCategoryList[ 0 ].name
			).parentElement?.closest(
				'.woocommerce-category-field-dropdown__item-content'
			);

			const toggle = rainGearCheckboxParent?.querySelector( 'svg' );
			if ( toggle ) {
				fireEvent.click( toggle );
			}
			const childParent = queryByText(
				mockCategoryList[ 1 ].name
			)?.parentElement?.closest(
				'.woocommerce-category-field-dropdown__item-children'
			);
			expect( childParent ).toBeInTheDocument();
			expect( childParent?.className ).toMatch(
				'woocommerce-category-field-dropdown__item-open'
			);
		} );
	} );
} );
