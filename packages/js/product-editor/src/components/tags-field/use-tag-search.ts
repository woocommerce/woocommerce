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

export type ProductTagNode = Pick< ProductTag, 'id' | 'name' >;

/**
 * A hook used to handle all the search logic for the tag search component.
 */
export const useTagSearch = () => {
	const lastSearchValue = useRef( '' );
	const { initialTags } = useSelect(
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore
		( select: WCDataSelector ) => {
			const { getProductTags } = select(
				EXPERIMENTAL_PRODUCT_TAGS_STORE_NAME
			);
			return {
				initialTags: getProductTags(),
			};
		}
	);
	const [ isSearching, setIsSearching ] = useState( true );
	const [ tagsAndNewItem, setTagsAndNewItem ] = useState< ProductTag[] >(
		[]
	);
	const isAsync = ! initialTags || initialTags.length > 0;

	useEffect( () => {
		if (
			initialTags &&
			initialTags.length > 0 &&
			( tagsAndNewItem.length === 0 ||
				lastSearchValue.current.length === 0 )
		) {
			setTagsAndNewItem( initialTags );
			setIsSearching( false );
		}
	}, [ initialTags ] );

	const searchTags = useCallback(
		async ( search?: string ): Promise< ProductTag[] > => {
			lastSearchValue.current = search || '';
			if ( ! isAsync && initialTags.length > 0 ) {
				setTagsAndNewItem( initialTags );
				setIsSearching( false );
				return initialTags;
			}
			setIsSearching( true );
			try {
				const newTags: ProductTag[] = await resolveSelect(
					EXPERIMENTAL_PRODUCT_TAGS_STORE_NAME
				).getProductTags( {
					search,
				} );

				setIsSearching( false );
				setTagsAndNewItem( newTags );
				return newTags;
			} catch ( e ) {
				setIsSearching( false );
				return [];
			}
		},
		[ initialTags ]
	);

	return {
		searchTags,
		tagsSelectList: tagsAndNewItem,
		isSearching,
	};
};
