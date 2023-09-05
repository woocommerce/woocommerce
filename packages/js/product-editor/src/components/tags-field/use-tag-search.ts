/**
 * External dependencies
 */
import { useCallback, useEffect, useRef, useState } from '@wordpress/element';
import { resolveSelect } from '@wordpress/data';
import {
	EXPERIMENTAL_PRODUCT_TAGS_STORE_NAME,
	ProductTag,
} from '@woocommerce/data';

export type ProductTagNode = Pick< ProductTag, 'id' | 'name' >;

/**
 * Filters the provided list of tags based on a given search term by checking
 * if the term is included in the tag's slug.
 */
function getFilteredTags( items: ProductTag[] = [], search: string ) {
	return items.filter( ( tag ) => tag.slug?.includes( search ) );
}

async function fetchProductTags( search?: string ): Promise< ProductTag[] > {
	const query = search !== undefined ? { search } : '';
	return resolveSelect( EXPERIMENTAL_PRODUCT_TAGS_STORE_NAME ).getProductTags(
		query
	);
}

/**
 * A hook used to handle all the search logic for the tag search component.
 */
export const useTagSearch = () => {
	const lastSearchValue = useRef( '' );
	const [ fetchedTags, setFetchedTags ] = useState< ProductTag[] >( [] );
	const [ isSearching, setIsSearching ] = useState( true );
	const [ tagsAndNewItem, setTagsAndNewItem ] = useState< ProductTag[] >(
		[]
	);

	useEffect( () => {
		const fetchAndSetTags = async () => {
			try {
				const tags = await fetchProductTags();
				setFetchedTags( tags );
				setIsSearching( false );
			} catch ( error ) {
				setIsSearching( false );
			}
		};

		fetchAndSetTags();
	}, [ tagsAndNewItem ] );

	useEffect( () => {
		if (
			fetchedTags &&
			fetchedTags.length > 0 &&
			( tagsAndNewItem.length === 0 ||
				lastSearchValue.current.length === 0 )
		) {
			setTagsAndNewItem( fetchedTags );
			setIsSearching( false );
		}
	}, [ fetchedTags ] );

	const searchTags = useCallback(
		async ( search?: string ): Promise< ProductTag[] > => {
			lastSearchValue.current = search || '';
			if ( fetchedTags.length > 0 ) {
				const tags = getFilteredTags(
					// initialTags,
					fetchedTags,
					lastSearchValue.current.toLowerCase()
				);
				setTagsAndNewItem( tags );
				setIsSearching( false );
				return tags;
			}
			setIsSearching( true );
			try {
				const newTags = await fetchProductTags( search );

				setIsSearching( false );
				setTagsAndNewItem( newTags );
				return newTags;
			} catch ( e ) {
				setIsSearching( false );
				return [];
			}
		},
		[ fetchedTags ]
	);

	return {
		searchTags,
		tagsSelectList: tagsAndNewItem,
		isSearching,
	};
};
