/**
 * External dependencies
 */
import { useCallback, useEffect, useRef, useState } from '@wordpress/element';
import { resolveSelect } from '@wordpress/data';
import {
	EXPERIMENTAL_PRODUCT_CATEGORIES_STORE_NAME,
	ProductCategory,
} from '@woocommerce/data';
import { escapeRegExp } from 'lodash';

/**
 * Internal dependencies
 */
import { CategoryTreeItem } from './category-field-item';

const PAGE_SIZE = 100;
const parentCategoryCache: Record< number, ProductCategory > = {};
const productCategoryQueryObject = {
	per_page: PAGE_SIZE,
};

/**
 * Recursive function to set isOpen to true for all the childrens parents.
 */
function openParents(
	treeList: Record< number, CategoryTreeItem >,
	item: CategoryTreeItem
) {
	if ( treeList[ item.parentID ] ) {
		treeList[ item.parentID ].isOpen = true;
		if ( treeList[ item.parentID ].parentID !== 0 ) {
			openParents( treeList, treeList[ item.parentID ] );
		}
	}
}

/**
 * Sort function for category tree items, sorts by popularity and then alphabetically.
 */
export const sortCategoryTreeItems = (
	menuItems: CategoryTreeItem[]
): CategoryTreeItem[] => {
	return menuItems.sort( ( a, b ) => {
		if ( a.data.count === b.data.count ) {
			return a.data.name.localeCompare( b.data.name );
		}
		return b.data.count - a.data.count;
	} );
};

/**
 * Flattens the category tree into a single list, also sorts the children of any parent tree item.
 */
function flattenCategoryTreeAndSortChildren(
	items: ProductCategory[] = [],
	treeItems: CategoryTreeItem[]
) {
	for ( const treeItem of treeItems ) {
		items.push( treeItem.data );
		if ( treeItem.children.length > 0 ) {
			treeItem.children = sortCategoryTreeItems( treeItem.children );
			flattenCategoryTreeAndSortChildren( items, treeItem.children );
		}
	}
	return items;
}

async function getProductCategories(
	args?: Record< string, string | string[] | number | number[] | undefined >
) {
	return resolveSelect(
		EXPERIMENTAL_PRODUCT_CATEGORIES_STORE_NAME
	).getProductCategories< ProductCategory[] >( args );
}

async function getProductCategoriesTotalCount() {
	return resolveSelect(
		EXPERIMENTAL_PRODUCT_CATEGORIES_STORE_NAME
	).getProductCategoriesTotalCount< number >( productCategoryQueryObject );
}

const filterItems = (
	allItems: ProductCategory[],
	inputValue: string,
	selectedItems: ProductCategory[],
	categoryTreeKeyValues: Record< number, CategoryTreeItem >
) => {
	const searchRegex = new RegExp( escapeRegExp( inputValue ), 'i' );
	return allItems.filter(
		( item ) =>
			selectedItems.indexOf( item ) < 0 &&
			( searchRegex.test( item.name ) ||
				( categoryTreeKeyValues[ item.id ] &&
					categoryTreeKeyValues[ item.id ].isOpen ) )
	);
};

/**
 * Recursive function to turn a category list into a tree and retrieve any missing parents.
 * It checks if any parents are missing, and then does a single request to retrieve those, running this function again after.
 */
export async function getCategoriesTreeWithMissingParents(
	newCategories: ProductCategory[],
	search: string,
	selectedItems: ProductCategory[] = []
): Promise<
	[
		ProductCategory[],
		CategoryTreeItem[],
		Record< number, CategoryTreeItem >
	]
> {
	const items: Record< number, CategoryTreeItem > = {};
	const missingParents: number[] = [];

	for ( const cat of newCategories ) {
		items[ cat.id ] = {
			data: cat,
			children: [],
			parentID: cat.parent,
			isOpen: false,
		};
	}
	// Loops through each item and adds children to their parents by the use of parentID.
	Object.keys( items ).forEach( ( key ) => {
		const item = items[ parseInt( key, 10 ) ];
		if ( item.parentID !== 0 ) {
			// Check the parent cache incase the parent was missing and use that instead.
			if (
				! items[ item.parentID ] &&
				parentCategoryCache[ item.parentID ]
			) {
				items[ item.parentID ] = {
					data: parentCategoryCache[ item.parentID ],
					children: [],
					parentID: parentCategoryCache[ item.parentID ].parent,
					isOpen: false,
				};
			}
			if ( items[ item.parentID ] ) {
				items[ item.parentID ].children.push( item );
				parentCategoryCache[ item.parentID ] =
					items[ item.parentID ].data;
				// Open the parents if the child matches the search string.
				const searchRegex = new RegExp( escapeRegExp( search ), 'i' );
				if ( search.length > 0 && searchRegex.test( item.data.name ) ) {
					openParents( items, item );
				}
			} else {
				missingParents.push( item.parentID );
			}
		}
	} );

	// Retrieve the missing parent objects incase not all of them were included.
	if ( missingParents.length > 0 ) {
		return getProductCategories( {
			include: missingParents,
		} ).then( ( parentCategories ) => {
			return getCategoriesTreeWithMissingParents(
				[
					...( parentCategories as ProductCategory[] ),
					...newCategories,
				],
				search,
				selectedItems
			);
		} );
	}
	const categoryTreeList = sortCategoryTreeItems(
		Object.values( items ).filter( ( item ) => item.parentID === 0 )
	);
	const categoryCheckboxList = flattenCategoryTreeAndSortChildren(
		[],
		categoryTreeList
	);
	const filterdCategories = filterItems(
		categoryCheckboxList,
		search,
		selectedItems,
		items
	);

	return Promise.resolve( [ filterdCategories, categoryTreeList, items ] );
}

/**
 * A hook used to handle all the search logic for the category search component.
 * This hook also handles the data structure and provides a tree like structure see: CategoryTreeItema.
 */
export const useCategorySearch = ( selectedItems?: ProductCategory[] ) => {
	const [ initialCategories, setInitialCategories ] = useState<
		ProductCategory[]
	>( [] );
	const [ totalCount, setTotalCount ] = useState< number >( 0 );
	const [ isSearching, setIsSearching ] = useState( true );
	const [ categoriesAndNewItem, setCategoriesAndNewItem ] = useState<
		[
			ProductCategory[],
			CategoryTreeItem[],
			Record< number, CategoryTreeItem >
		]
	>( [ [], [], {} ] );
	const lastSearchValue = useRef( '' );
	const isAsync =
		! initialCategories ||
		( initialCategories.length > 0 && totalCount > PAGE_SIZE );

	useEffect( () => {
		setIsSearching( true );
		getProductCategories( productCategoryQueryObject )
			.then( ( categories ) => {
				setInitialCategories( categories );
				return getCategoriesTreeWithMissingParents(
					categories,
					'',
					selectedItems
				);
			} )
			.then( setCategoriesAndNewItem )
			.then( getProductCategoriesTotalCount )
			.then( setTotalCount )
			.catch( () => {} )
			.finally( () => {
				setIsSearching( false );
			} );
	}, [ selectedItems ] );

	const searchCategories = useCallback(
		async ( search?: string ): Promise< ProductCategory[] > => {
			lastSearchValue.current = search || '';
			if ( ! isAsync && initialCategories.length > 0 ) {
				return getCategoriesTreeWithMissingParents(
					[ ...initialCategories ],
					search || '',
					selectedItems
				).then( ( categoryData ) => {
					setCategoriesAndNewItem( categoryData );
					return categoryData[ 0 ];
				} );
			}
			setIsSearching( true );
			try {
				const newCategories = await getProductCategories( {
					search,
					per_page: PAGE_SIZE,
				} );
				const categoryTreeData =
					await getCategoriesTreeWithMissingParents(
						newCategories,
						search || '',
						selectedItems
					);
				setIsSearching( false );
				setCategoriesAndNewItem( categoryTreeData );
				return categoryTreeData[ 0 ];
			} catch ( e ) {
				setIsSearching( false );
				return [];
			}
		},
		[ initialCategories, isAsync, selectedItems ]
	);

	const categoryTreeKeyValues = categoriesAndNewItem[ 2 ];

	return {
		searchCategories,
		categoriesSelectList: categoriesAndNewItem[ 0 ],
		categories: categoriesAndNewItem[ 1 ],
		isSearching,
		categoryTreeKeyValues,
	};
};

export type UseCategorySearchInput = {
	selectedItems?: ProductCategory[];
};
