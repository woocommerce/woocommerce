/**
 * External dependencies
 */
import { act, renderHook } from '@testing-library/react-hooks';
import { useSelect, resolveSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { useCategorySearch } from '../use-category-search';

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn(),
	resolveSelect: jest.fn(),
} ) );

const mockCategoryList = [
	{ id: 1, name: 'Clothing', parent: 0, count: 0 },
	{ id: 2, name: 'Hoodies', parent: 1, count: 0 },
	{ id: 4, name: 'Accessories', parent: 1, count: 0 },
	{ id: 5, name: 'Belts', parent: 4, count: 0 },
	{ id: 3, name: 'Rain gear', parent: 0, count: 0 },
	{ id: 6, name: 'Furniture', parent: 0, count: 0 },
];

describe( 'useCategorySearch', () => {
	const getProductCategoriesMock = jest
		.fn()
		.mockReturnValue( [ ...mockCategoryList ] );
	const getProductCategoriesTotalCountMock = jest
		.fn()
		.mockReturnValue( mockCategoryList.length );
	const getProductCategoriesResolveMock = jest.fn();

	beforeEach( () => {
		jest.clearAllMocks();
		( useSelect as jest.Mock ).mockImplementation( ( callback ) => {
			return callback( () => ( {
				getProductCategories: getProductCategoriesMock,
				getProductCategoriesTotalCount:
					getProductCategoriesTotalCountMock,
			} ) );
		} );
		( resolveSelect as jest.Mock ).mockImplementation( () => ( {
			getProductCategories: getProductCategoriesResolveMock,
		} ) );
	} );

	it( 'should retrieve an initial list of product categories and generate a tree', async () => {
		getProductCategoriesMock.mockReturnValue( undefined );
		getProductCategoriesTotalCountMock.mockReturnValue(
			mockCategoryList.length
		);
		const { result, rerender, waitForNextUpdate } = renderHook( () =>
			useCategorySearch()
		);

		act( () => {
			getProductCategoriesMock.mockReturnValue( [ ...mockCategoryList ] );
			rerender();
		} );

		await waitForNextUpdate();

		expect( result.current.categoriesSelectList.length ).toEqual(
			mockCategoryList.length
		);
		expect( result.current.categories.length ).toEqual(
			mockCategoryList.filter( ( c ) => c.parent === 0 ).length
		);
	} );

	it( 'should return a correct tree for categories with each item containing a childrens property', async () => {
		getProductCategoriesMock.mockReturnValue( [ ...mockCategoryList ] );
		getProductCategoriesTotalCountMock.mockReturnValue(
			mockCategoryList.length
		);
		const { result, waitForNextUpdate } = renderHook( () =>
			useCategorySearch()
		);

		await waitForNextUpdate();

		const clothing = result.current.categories.find(
			( cat ) => cat.data.name === 'Clothing'
		);
		expect( clothing?.children[ 0 ].data.name ).toEqual( 'Accessories' );
		expect( clothing?.children[ 0 ].children[ 0 ].data.name ).toEqual(
			'Belts'
		);
	} );

	it( 'should sort items by count first and then alphabetical', async () => {
		getProductCategoriesMock.mockReturnValue( [
			...mockCategoryList,
			{ id: 12, name: 'BB', parent: 0, count: 0 },
			{ id: 13, name: 'AA', parent: 0, count: 0 },
			{ id: 11, name: 'ZZZ', parent: 0, count: 20 },
		] );

		getProductCategoriesTotalCountMock.mockReturnValue(
			mockCategoryList.length
		);
		const { result, waitForNextUpdate } = renderHook( () =>
			useCategorySearch()
		);

		await waitForNextUpdate();

		expect( result.current.categoriesSelectList[ 0 ].name ).toEqual(
			'ZZZ'
		);
		expect( result.current.categoriesSelectList[ 1 ].name ).toEqual( 'AA' );
		expect( result.current.categoriesSelectList[ 2 ].name ).toEqual( 'BB' );
	} );

	it( 'should also sort children by count first and then alphabetical', async () => {
		getProductCategoriesMock.mockReturnValue( [
			...mockCategoryList,
			{ id: 12, name: 'AB', parent: 1, count: 0 },
			{ id: 13, name: 'AA', parent: 1, count: 0 },
			{ id: 11, name: 'ZZZ', parent: 1, count: 20 },
		] );

		getProductCategoriesTotalCountMock.mockReturnValue(
			mockCategoryList.length
		);
		const { result, waitForNextUpdate } = renderHook( () =>
			useCategorySearch()
		);

		await waitForNextUpdate();

		const clothing = result.current.categories.find(
			( cat ) => cat.data.name === 'Clothing'
		);
		expect( clothing?.children[ 0 ].data.name ).toEqual( 'ZZZ' );
		expect( clothing?.children[ 1 ].data.name ).toEqual( 'AA' );
		expect( clothing?.children[ 2 ].data.name ).toEqual( 'AB' );
	} );

	it( 'should order the select list by parent, child, nested child, parent', async () => {
		getProductCategoriesMock.mockReturnValue( [
			...mockCategoryList,
			{ id: 13, name: 'AA', parent: 1, count: 0 },
			{ id: 11, name: 'ZZ', parent: 1, count: 20 },
		] );

		getProductCategoriesTotalCountMock.mockReturnValue(
			mockCategoryList.length
		);
		const { result, waitForNextUpdate } = renderHook( () =>
			useCategorySearch()
		);

		await waitForNextUpdate();

		expect( result.current.categoriesSelectList[ 0 ].name ).toEqual(
			'Clothing'
		);
		// child of clothing.
		expect( result.current.categoriesSelectList[ 1 ].name ).toEqual( 'ZZ' );
		expect( result.current.categoriesSelectList[ 2 ].name ).toEqual( 'AA' );
		expect( result.current.categoriesSelectList[ 3 ].name ).toEqual(
			'Accessories'
		);
		// child of accessories.
		expect( result.current.categoriesSelectList[ 4 ].name ).toEqual(
			'Belts'
		);
		// child of clothing.
		expect( result.current.categoriesSelectList[ 5 ].name ).toEqual(
			'Hoodies'
		);
		// top level.
		expect( result.current.categoriesSelectList[ 6 ].name ).toEqual(
			'Furniture'
		);
	} );

	describe( 'getFilteredItems', () => {
		it( 'should filter items by label, matching input value, and if selected', async () => {
			getProductCategoriesMock.mockReturnValue( [ ...mockCategoryList ] );

			getProductCategoriesTotalCountMock.mockReturnValue(
				mockCategoryList.length
			);
			const { result, waitForNextUpdate } = renderHook( () =>
				useCategorySearch()
			);
			await waitForNextUpdate();

			const filteredItems = result.current.getFilteredItems(
				result.current.categoriesSelectList,
				'Rain',
				[]
			);
			expect( filteredItems.length ).toEqual( 1 );
			expect( filteredItems[ 0 ].name ).toEqual( 'Rain gear' );
		} );

		it( 'should filter items by isOpen as well, keeping them if isOpen is true', async () => {
			getProductCategoriesMock.mockReturnValue( [ ...mockCategoryList ] );

			getProductCategoriesTotalCountMock.mockReturnValue(
				mockCategoryList.length
			);
			const { result, waitForNextUpdate } = renderHook( () =>
				useCategorySearch()
			);
			await waitForNextUpdate();
			act( () => {
				result.current.searchCategories( 'Bel' );
			} );
			await waitForNextUpdate();
			expect( result.current.categoriesSelectList.length ).toEqual( 6 );
			const filteredItems = result.current.getFilteredItems(
				result.current.categoriesSelectList,
				'Bel',
				[]
			);
			expect( filteredItems.length ).toEqual( 3 );
			expect( filteredItems[ 0 ].name ).toEqual( 'Clothing' );
			expect( filteredItems[ 1 ].name ).toEqual( 'Accessories' );
			expect( filteredItems[ 2 ].name ).toEqual( 'Belts' );
		} );
	} );

	describe( 'searchCategories', () => {
		it( 'should not use async when total categories is less then page size', async () => {
			getProductCategoriesResolveMock.mockClear();
			getProductCategoriesMock.mockReturnValue( [ ...mockCategoryList ] );

			getProductCategoriesTotalCountMock.mockReturnValue(
				mockCategoryList.length
			);
			const { result, waitForNextUpdate } = renderHook( () =>
				useCategorySearch()
			);

			await waitForNextUpdate();

			act( () => {
				result.current.searchCategories( 'Clo' );
			} );

			await waitForNextUpdate();
			expect( getProductCategoriesResolveMock ).not.toHaveBeenCalled();
		} );

		it( 'should use async when total categories is more then page size', async () => {
			getProductCategoriesResolveMock
				.mockClear()
				.mockResolvedValue(
					mockCategoryList.filter( ( c ) => c.name === 'Clothing' )
				);
			getProductCategoriesMock.mockReturnValue( [ ...mockCategoryList ] );

			getProductCategoriesTotalCountMock.mockReturnValue( 200 );
			const { result, waitForNextUpdate } = renderHook( () =>
				useCategorySearch()
			);

			await waitForNextUpdate();

			act( () => {
				result.current.searchCategories( 'Clo' );
			} );

			await waitForNextUpdate();
			expect( getProductCategoriesResolveMock ).toHaveBeenCalledWith( {
				search: 'Clo',
				per_page: 100,
			} );
			expect( result.current.categoriesSelectList.length ).toEqual( 1 );
		} );

		it( 'should update isSearching when async is enabled', async () => {
			let finish: () => void = () => {};
			getProductCategoriesResolveMock.mockClear().mockReturnValue(
				new Promise( ( resolve ) => {
					finish = () =>
						resolve(
							mockCategoryList.filter(
								( c ) => c.name === 'Clothing'
							)
						);
				} )
			);
			getProductCategoriesMock.mockReturnValue( [ ...mockCategoryList ] );
			getProductCategoriesTotalCountMock.mockReturnValue( 200 );

			const { result, waitForNextUpdate } = renderHook( () =>
				useCategorySearch()
			);

			await waitForNextUpdate();

			act( () => {
				result.current.searchCategories( 'Clo' );
			} );
			expect( result.current.isSearching ).toBe( true );

			act( () => {
				finish();
			} );
			await waitForNextUpdate();
			expect( result.current.isSearching ).toBe( false );
			expect( getProductCategoriesResolveMock ).toHaveBeenCalledWith( {
				search: 'Clo',
				per_page: 100,
			} );
			expect( result.current.categoriesSelectList.length ).toEqual( 1 );
		} );

		it( 'should set isSearching back to false if search failed and keep last results', async () => {
			let finish: () => void = () => {};
			getProductCategoriesResolveMock.mockClear().mockReturnValue(
				new Promise( ( resolve, reject ) => {
					finish = () => reject();
				} )
			);
			getProductCategoriesMock.mockReturnValue( [ ...mockCategoryList ] );
			getProductCategoriesTotalCountMock.mockReturnValue( 200 );

			const { result, waitForNextUpdate } = renderHook( () =>
				useCategorySearch()
			);

			await waitForNextUpdate();

			act( () => {
				result.current.searchCategories( 'Clo' );
			} );
			expect( result.current.isSearching ).toBe( true );

			act( () => {
				finish();
			} );
			await waitForNextUpdate();
			expect( result.current.isSearching ).toBe( false );
			expect( getProductCategoriesResolveMock ).toHaveBeenCalledWith( {
				search: 'Clo',
				per_page: 100,
			} );
			expect( result.current.categoriesSelectList.length ).toEqual( 6 );
		} );

		it( 'should keep parent in the list if only child matches search value', async () => {
			getProductCategoriesResolveMock
				.mockClear()
				.mockResolvedValue( [
					mockCategoryList.find( ( c ) => c.name === 'Hoodies' ),
				] );
			getProductCategoriesMock.mockReturnValue( [ ...mockCategoryList ] );
			getProductCategoriesTotalCountMock.mockReturnValue( 200 );

			const { result, waitForNextUpdate } = renderHook( () =>
				useCategorySearch()
			);

			await waitForNextUpdate();

			act( () => {
				result.current.searchCategories( 'Hood' );
			} );
			await waitForNextUpdate();
			expect( getProductCategoriesResolveMock ).toHaveBeenCalledWith( {
				search: 'Hood',
				per_page: 100,
			} );
			expect( result.current.categoriesSelectList.length ).toEqual( 2 );
			expect( result.current.categoriesSelectList[ 0 ].name ).toEqual(
				'Clothing'
			);
			expect( result.current.categoriesSelectList[ 1 ].name ).toEqual(
				'Hoodies'
			);
		} );

		it( 'should set parent isOpen to true if child matches search value', async () => {
			getProductCategoriesResolveMock
				.mockClear()
				.mockResolvedValue( [
					mockCategoryList.find( ( c ) => c.name === 'Hoodies' ),
				] );
			getProductCategoriesMock.mockReturnValue( [ ...mockCategoryList ] );
			getProductCategoriesTotalCountMock.mockReturnValue( 200 );

			const { result, waitForNextUpdate } = renderHook( () =>
				useCategorySearch()
			);

			await waitForNextUpdate();

			act( () => {
				result.current.searchCategories( 'Hood' );
			} );
			await waitForNextUpdate();
			expect( getProductCategoriesResolveMock ).toHaveBeenCalledWith( {
				search: 'Hood',
				per_page: 100,
			} );
			expect( result.current.categories[ 0 ].isOpen ).toEqual( true );
		} );
	} );
} );
