/**
 * External dependencies
 */
import { useCallback, useEffect, useRef, useState } from '@wordpress/element';
import { useSelect, resolveSelect } from '@wordpress/data';
import {
	EXPERIMENTAL_PRODUCT_TAGS_STORE_NAME,
	WCDataSelector,
	ProductTag,
} from '@woocommerce/data';
import { escapeRegExp } from 'lodash';
import { TreeItemType } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { TagTreeItem } from './tag-field-item';

const PAGE_SIZE = 100;

export type ProductTagNode = Pick< ProductTag, 'id' | 'name' >;

/**
 * Sort function for tag tree items, sorts by popularity and then alphabetically.
 */
export const sortTagTreeItems = ( menuItems: TagTreeItem[] ): TagTreeItem[] => {
	return menuItems.sort( ( a, b ) => {
		if ( a.data.count === b.data.count ) {
			return a.data.name.localeCompare( b.data.name );
		}
		return b.data.count - a.data.count;
	} );
};

/**
 * Flattens the tag tree into a single list, also sorts the children of any parent tree item.
 */
function flattenTagTreeAndSortChildren(
	items: ProductTag[] = [],
	treeItems: TagTreeItem[]
) {
	for ( const treeItem of treeItems ) {
		items.push( treeItem.data );
		if ( treeItem.children.length > 0 ) {
			treeItem.children = sortTagTreeItems( treeItem.children );
			flattenTagTreeAndSortChildren( items, treeItem.children );
		}
	}
	return items;
}

/**
 * Recursive function to turn a tag list into a tree and retrieve any missing parents.
 * It checks if any parents are missing, and then does a single request to retrieve those, running this function again after.
 */
export async function getTagsTreeWithMissingParents(
	newTags: ProductTag[],
	search: string
): Promise< [ ProductTag[], TagTreeItem[], Record< number, TagTreeItem > ] > {
	const items: Record< number, TagTreeItem > = {};
	const missingParents: number[] = [];

	for ( const cat of newTags ) {
		items[ cat.id ] = {
			data: cat,
			children: [],
			isOpen: false,
		};
	}

	// Retrieve the missing parent objects incase not all of them were included.
	if ( missingParents.length > 0 ) {
		return (
			resolveSelect( EXPERIMENTAL_PRODUCT_TAGS_STORE_NAME )
				.getProductTags( {
					include: missingParents,
				} )
				// eslint-disable-next-line @typescript-eslint/ban-ts-comment
				// @ts-ignore
				.then( ( parentTags ) => {
					return getTagsTreeWithMissingParents(
						[ ...( parentTags as ProductTag[] ), ...newTags ],
						search
					);
				} )
		);
	}
	const tagTreeList = sortTagTreeItems(
		Object.values( items ).filter( ( item ) => item )
	);
	const tagCheckboxList = flattenTagTreeAndSortChildren( [], tagTreeList );

	return Promise.resolve( [ tagCheckboxList, tagTreeList, items ] );
}

const productTagQueryObject = {
	per_page: PAGE_SIZE,
};

/**
 * A hook used to handle all the search logic for the tag search component.
 * This hook also handles the data structure and provides a tree like structure see: TagTreeItema.
 */
export const useTagSearch = () => {
	const lastSearchValue = useRef( '' );
	const { initialTags, totalCount } = useSelect(
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore
		( select: WCDataSelector ) => {
			const { getProductTags, getProductTagsTotalCount } = select(
				EXPERIMENTAL_PRODUCT_TAGS_STORE_NAME
			);
			return {
				// eslint-disable-next-line @typescript-eslint/ban-ts-comment
				// @ts-ignore
				initialTags: getProductTags( productTagQueryObject ),
				// eslint-disable-next-line @typescript-eslint/ban-ts-comment
				// @ts-ignore
				totalCount: getProductTagsTotalCount( productTagQueryObject ),
			};
		}
	);
	const [ isSearching, setIsSearching ] = useState( true );
	const [ tagsAndNewItem, setTagsAndNewItem ] = useState<
		[ ProductTag[], TagTreeItem[], Record< number, TagTreeItem > ]
	>( [ [], [], {} ] );
	const isAsync =
		! initialTags || ( initialTags.length > 0 && totalCount > PAGE_SIZE );

	useEffect( () => {
		if (
			initialTags &&
			initialTags.length > 0 &&
			( tagsAndNewItem[ 0 ].length === 0 ||
				lastSearchValue.current.length === 0 )
		) {
			setIsSearching( true );
			getTagsTreeWithMissingParents( [ ...initialTags ], '' ).then(
				( tagTree ) => {
					setTagsAndNewItem( tagTree );
					setIsSearching( false );
				},
				() => {
					setIsSearching( false );
				}
			);
		}
	}, [ initialTags ] );

	const searchTags = useCallback(
		async ( search?: string ): Promise< TagTreeItem[] > => {
			lastSearchValue.current = search || '';
			if ( ! isAsync && initialTags.length > 0 ) {
				return getTagsTreeWithMissingParents(
					[ ...initialTags ],
					search || ''
				).then( ( tagData ) => {
					setTagsAndNewItem( tagData );
					return tagData[ 1 ];
				} );
			}
			setIsSearching( true );
			try {
				const newTags = await resolveSelect(
					EXPERIMENTAL_PRODUCT_TAGS_STORE_NAME
				).getProductTags( {
					search,
					per_page: PAGE_SIZE,
				} );

				const tagTreeData = await getTagsTreeWithMissingParents(
					newTags as ProductTag[],
					search || ''
				);
				setIsSearching( false );
				setTagsAndNewItem( tagTreeData );
				return tagTreeData[ 1 ];
			} catch ( e ) {
				setIsSearching( false );
				return [];
			}
		},
		[ initialTags ]
	);

	const tagTreeKeyValues = tagsAndNewItem[ 2 ];

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
						( tagTreeKeyValues[ +item.value ] &&
							tagTreeKeyValues[ +item.value ].isOpen ) )
			);
		},
		[ tagsAndNewItem ]
	);

	return {
		searchTags,
		getFilteredItemsForSelectTree,
		tagsSelectList: tagsAndNewItem[ 0 ],
		tags: tagsAndNewItem[ 1 ],
		isSearching,
	};
};
