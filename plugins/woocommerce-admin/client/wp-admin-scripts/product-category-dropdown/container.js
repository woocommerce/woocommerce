/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import { addQueryArgs } from '@wordpress/url';
import { TreeSelectControl } from '@woocommerce/components';
import { getSetting } from '@woocommerce/settings';
import apiFetch from '@wordpress/api-fetch';

function convertTreeToLabelValue( tree, newTree = [] ) {
	for ( const child of tree ) {
		const newItem = {
			label: child.name,
			value: child.term_id,
			children: [],
		};
		newTree.push( newItem );
		if ( child.children && child.children.length > 0 ) {
			convertTreeToLabelValue( child.children, newItem.children );
		}
	}
	return newTree;
}

export const CategoryDropdownContainer = () => {
	const [ filter, setFilter ] = useState( '' );
	const [ selected, setSelected ] = useState( [] );
	const [ treeItems, setTreeItems ] = useState( [] );

	useEffect( () => {
		apiFetch( {
			url: addQueryArgs( getSetting( 'adminUrl' ) + 'admin-ajax.php', {
				term: filter,
				action: 'woocommerce_json_search_categories_tree',
				// eslint-disable-next-line no-undef, camelcase
				security: wc_enhanced_select_params.search_categories_nonce,
			} ),
			method: 'GET',
		} ).then( ( res ) => {
			if ( res ) {
				setTreeItems( convertTreeToLabelValue( Object.values( res ) ) );
			}
		} );
	}, [ filter ] );

	return (
		<div className="product-add-category__tree-control">
			<TreeSelectControl
				options={ treeItems }
				value={ selected }
				onChange={ setSelected }
				selectAllLabel={ false }
				onInputChange={ setFilter }
				placeholder="Add category"
			/>
		</div>
	);
};
