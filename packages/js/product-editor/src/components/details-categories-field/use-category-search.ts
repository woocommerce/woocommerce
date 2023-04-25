/**
 * External dependencies
 */
import { useCallback, useEffect, useRef, useState } from '@wordpress/element';
import { useSelect, resolveSelect } from '@wordpress/data';
import {
	EXPERIMENTAL_PRODUCT_CATEGORIES_STORE_NAME,
	WCDataSelector,
	ProductCategory,
} from '@woocommerce/data';
import { escapeRegExp } from 'lodash';
import { TreeItemType } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { CategoryTreeItem } from './category-field-item';

const PAGE_SIZE = 100;
const parentCategoryCache: Record< number, ProductCategory > = {};

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

export type ProductCategoryNode = Pick<
	ProductCategory,
	'id' | 'name' | 'parent'
>;

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

/**
 * Recursive function to turn a category list into a tree and retrieve any missing parents.
 * It checks if any parents are missing, and then does a single request to retrieve those, running this function again after.
 */
export async function getCategoriesTreeWithMissingParents(
	newCategories: ProductCategory[],
	search: string
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
		return (
			resolveSelect( EXPERIMENTAL_PRODUCT_CATEGORIES_STORE_NAME )
				.getProductCategories( {
					include: missingParents,
				} )
				// eslint-disable-next-line @typescript-eslint/ban-ts-comment
				// @ts-ignore
				.then( ( parentCategories ) => {
					return getCategoriesTreeWithMissingParents(
						[
							...( parentCategories as ProductCategory[] ),
							...newCategories,
						],
						search
					);
				} )
		);
	}
	const categoryTreeList = sortCategoryTreeItems(
		Object.values( items ).filter( ( item ) => item.parentID === 0 )
	);
	const categoryCheckboxList = flattenCategoryTreeAndSortChildren(
		[],
		categoryTreeList
	);

	return Promise.resolve( [ categoryCheckboxList, categoryTreeList, items ] );
}

const productCategoryQueryObject = {
	per_page: PAGE_SIZE,
};

/**
 * A hook used to handle all the search logic for the category search component.
 * This hook also handles the data structure and provides a tree like structure see: CategoryTreeItema.
 */
export const useCategorySearch = () => {
	const lastSearchValue = useRef( '' );
	const { initialCategories, totalCount } = useSelect(
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore
		( select: WCDataSelector ) => {
			const { getProductCategories, getProductCategoriesTotalCount } =
				select( EXPERIMENTAL_PRODUCT_CATEGORIES_STORE_NAME );
			return {
				initialCategories: getProductCategories(
					productCategoryQueryObject
				),
				totalCount: getProductCategoriesTotalCount(
					productCategoryQueryObject
				),
			};
		}
	);
	const [ isSearching, setIsSearching ] = useState( true );
	const [ categoriesAndNewItem, setCategoriesAndNewItem ] = useState<
		[
			ProductCategory[],
			CategoryTreeItem[],
			Record< number, CategoryTreeItem >
		]
	>( [ [], [], {} ] );
	const isAsync =
		! initialCategories ||
		( initialCategories.length > 0 && totalCount > PAGE_SIZE );

	useEffect( () => {
		if (
			initialCategories &&
			initialCategories.length > 0 &&
			( categoriesAndNewItem[ 0 ].length === 0 ||
				lastSearchValue.current.length === 0 )
		) {
			setIsSearching( true );
			getCategoriesTreeWithMissingParents(
				[ ...initialCategories ],
				''
			).then(
				( categoryTree ) => {
					setCategoriesAndNewItem( categoryTree );
					setIsSearching( false );
				},
				() => {
					setIsSearching( false );
				}
			);
		}
	}, [ initialCategories ] );

	const searchCategories = useCallback(
		async ( search?: string ): Promise< CategoryTreeItem[] > => {
			lastSearchValue.current = search || '';
			if ( ! isAsync && initialCategories.length > 0 ) {
				return getCategoriesTreeWithMissingParents(
					[ ...initialCategories ],
					search || ''
				).then( ( categoryData ) => {
					setCategoriesAndNewItem( categoryData );
					return categoryData[ 1 ];
				} );
			}
			setIsSearching( true );
			try {
				const newCategories = await resolveSelect(
					EXPERIMENTAL_PRODUCT_CATEGORIES_STORE_NAME
				).getProductCategories( {
					search,
					per_page: PAGE_SIZE,
				} );

				const categoryTreeData =
					await getCategoriesTreeWithMissingParents(
						newCategories as ProductCategory[],
						search || ''
					);
				setIsSearching( false );
				setCategoriesAndNewItem( categoryTreeData );
				return categoryTreeData[ 1 ];
			} catch ( e ) {
				setIsSearching( false );
				return [];
			}
		},
		[ initialCategories ]
	);

	const categoryTreeKeyValues = categoriesAndNewItem[ 2 ];

	const getFilteredItemsForSelectTree = useCallback(
		(
			allItems: TreeItemType[],
			inputValue: string,
			selectedItems: TreeItemType[]
		) => {
			const searchRegex = new RegExp( escapeRegExp( inputValue ), 'i' );
			return allItems.filter(
				( item ) =>
					selectedItems.indexOf( item ) < 0 &&
					( searchRegex.test( item.label ) ||
						( categoryTreeKeyValues[ +item.value ] &&
							categoryTreeKeyValues[ +item.value ].isOpen ) )
			);
		},
		[ categoriesAndNewItem ]
	);

	return {
		searchCategories,
		getFilteredItemsForSelectTree,
		categoriesSelectList: categoriesAndNewItem[ 0 ],
		categories: categoriesAndNewItem[ 1 ],
		isSearching,
		categoryTreeKeyValues,
	};
};
