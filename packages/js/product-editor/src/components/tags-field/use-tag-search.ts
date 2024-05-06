/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { resolveSelect } from '@wordpress/data';
import {
	EXPERIMENTAL_PRODUCT_TAGS_STORE_NAME,
	ProductTag,
} from '@woocommerce/data';

/**
 * A hook used to handle all the search logic for the tag search component.
 */
export const useTagSearch = () => {
	const [ fetchedTags, setFetchedTags ] = useState< ProductTag[] >( [] );
	const [ isSearching, setIsSearching ] = useState( true );

	const fetchProductTags = ( search?: string ) => {
		setIsSearching( true );
		const query = search !== undefined ? { search } : '';
		resolveSelect( EXPERIMENTAL_PRODUCT_TAGS_STORE_NAME )
			.getProductTags( query )
			.then( ( tags ) => {
				setFetchedTags( tags as ProductTag[] );
			} )
			.finally( () => {
				setIsSearching( false );
			} );
	};

	return {
		searchTags: fetchProductTags,
		tagsSelectList: fetchedTags,
		isSearching,
	};
};
