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
	const getProductCategoriesMock = jest.fn();
	const getProductCategoriesTotalCountMock = jest.fn();

	beforeEach( () => {
		getProductCategoriesMock.mockResolvedValue( [ ...mockCategoryList ] );
		getProductCategoriesTotalCountMock.mockResolvedValue(
			mockCategoryList.length
		);

		( resolveSelect as jest.Mock ).mockReturnValue( {
			getProductCategories: getProductCategoriesMock,
			getProductCategoriesTotalCount: getProductCategoriesTotalCountMock,
		} );
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	it( 'should retrieve an initial list of product categories and generate a tree', async () => {
		const { result, waitForNextUpdate } = renderHook( () =>
			useCategorySearch()
		);

		await waitForNextUpdate();

		expect( result.current.categoriesSelectList.length ).toEqual(
			mockCategoryList.length
		);
		expect( result.current.categories.length ).toEqual(
			mockCategoryList.filter( ( { parent } ) => parent === 0 ).length
		);
	} );

	it( 'should return a correct tree for categories with each item containing a childrens property', async () => {
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
		getProductCategoriesMock.mockResolvedValue( [
			...mockCategoryList,
			{ id: 12, name: 'BB', parent: 0, count: 0 },
			{ id: 13, name: 'AA', parent: 0, count: 0 },
			{ id: 11, name: 'ZZZ', parent: 0, count: 20 },
		] );

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
		getProductCategoriesMock.mockResolvedValue( [
			...mockCategoryList,
			{ id: 12, name: 'AB', parent: 1, count: 0 },
			{ id: 13, name: 'AA', parent: 1, count: 0 },
			{ id: 11, name: 'ZZZ', parent: 1, count: 20 },
		] );

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
		getProductCategoriesMock.mockResolvedValue( [
			...mockCategoryList,
			{ id: 13, name: 'AA', parent: 1, count: 0 },
			{ id: 11, name: 'ZZ', parent: 1, count: 20 },
		] );

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

	describe( 'searchCategories', () => {
		it( 'should not use async when total categories is less than page size', async () => {
			const { result, waitForNextUpdate } = renderHook( () =>
				useCategorySearch()
			);

			await waitForNextUpdate();

			const search = 'Clo';

			act( () => {
				result.current.searchCategories( search );
			} );

			await waitForNextUpdate();

			expect( getProductCategoriesMock ).not.toHaveBeenCalledWith( {
				search,
				per_page: expect.any( Number ),
			} );
		} );

		it( 'should use async when total categories is more then page size', async () => {
			getProductCategoriesMock.mockResolvedValue(
				mockCategoryList.filter( ( { name } ) => name === 'Clothing' )
			);
			getProductCategoriesTotalCountMock.mockResolvedValue( 200 );

			const { result, waitForNextUpdate } = renderHook( () =>
				useCategorySearch()
			);

			await waitForNextUpdate();

			const search = 'Clo';

			act( () => {
				result.current.searchCategories( search );
			} );

			await waitForNextUpdate();

			expect( getProductCategoriesMock ).toHaveBeenCalledWith( {
				search,
				per_page: 100,
			} );
			expect( result.current.categoriesSelectList.length ).toEqual( 1 );
		} );

		it( 'should update isSearching when async is enabled', async () => {
			getProductCategoriesTotalCountMock.mockResolvedValue( 200 );

			const { result, waitForNextUpdate } = renderHook( () =>
				useCategorySearch()
			);

			await waitForNextUpdate();

			getProductCategoriesMock.mockResolvedValue(
				mockCategoryList.filter( ( { name } ) => name === 'Clothing' )
			);

			const search = 'Clo';

			act( () => {
				result.current.searchCategories( search );
			} );
			expect( result.current.isSearching ).toBe( true );

			await waitForNextUpdate();

			expect( result.current.isSearching ).toBe( false );
			expect( getProductCategoriesMock ).toHaveBeenCalledWith( {
				search,
				per_page: 100,
			} );
			expect( result.current.categoriesSelectList.length ).toEqual( 1 );
		} );

		it( 'should set isSearching back to false if search failed and keep last results', async () => {
			getProductCategoriesTotalCountMock.mockResolvedValue( 200 );

			const { result, waitForNextUpdate } = renderHook( () =>
				useCategorySearch()
			);

			await waitForNextUpdate();

			getProductCategoriesMock.mockRejectedValue( undefined );

			act( () => {
				result.current.searchCategories( 'Clo' );
			} );

			expect( result.current.isSearching ).toBe( true );

			await waitForNextUpdate();

			expect( result.current.isSearching ).toBe( false );
			expect( getProductCategoriesMock ).toHaveBeenCalledWith( {
				search: 'Clo',
				per_page: 100,
			} );
			expect( result.current.categoriesSelectList.length ).toEqual( 6 );
		} );

		it( 'should keep parent in the list if only child matches search value', async () => {
			getProductCategoriesTotalCountMock.mockResolvedValue( 200 );

			const { result, waitForNextUpdate } = renderHook( () =>
				useCategorySearch()
			);

			await waitForNextUpdate();

			getProductCategoriesMock.mockResolvedValue(
				mockCategoryList.filter( ( { name } ) => name === 'Hoodies' )
			);

			act( () => {
				result.current.searchCategories( 'Hood' );
			} );

			await waitForNextUpdate();

			expect( getProductCategoriesMock ).toHaveBeenCalledWith( {
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
			getProductCategoriesTotalCountMock.mockResolvedValue( 200 );

			const { result, waitForNextUpdate } = renderHook( () =>
				useCategorySearch()
			);

			await waitForNextUpdate();

			getProductCategoriesMock.mockResolvedValue(
				mockCategoryList.filter( ( { name } ) => name === 'Hoodies' )
			);

			act( () => {
				result.current.searchCategories( 'Hood' );
			} );

			await waitForNextUpdate();

			expect( getProductCategoriesMock ).toHaveBeenCalledWith( {
				search: 'Hood',
				per_page: 100,
			} );
			expect( result.current.categories[ 0 ].isOpen ).toEqual( true );
		} );
	} );
} );
