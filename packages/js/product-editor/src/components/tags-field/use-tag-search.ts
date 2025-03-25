/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { resolveSelect } from '@wordpress/data';
import { ProductTag, experimentalProductTagsStore } from '@woocommerce/data';

/**
 * A hook used to handle all the search logic for the tag search component.
 */
export const useTagSearch = () => {
	const [ fetchedTags, setFetchedTags ] = useState< ProductTag[] >( [] );
	const [ isSearching, setIsSearching ] = useState( true );

	const fetchProductTags = ( search?: string ) => {
		setIsSearching( true );
		const query = search !== undefined ? { search } : undefined;
		resolveSelect( experimentalProductTagsStore )
			.getProductTags( { ...query } )
			.then( ( tags ) => {
				setFetchedTags( tags ?? [] );
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
