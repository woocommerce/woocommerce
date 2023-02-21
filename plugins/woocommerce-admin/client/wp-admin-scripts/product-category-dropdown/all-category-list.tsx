/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';
import { addQueryArgs } from '@wordpress/url';
import { TreeSelectControl } from '@woocommerce/components';
import { getSetting } from '@woocommerce/settings';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { CATEGORY_TERM_NAME, syncWithMostUsed } from './category-handlers';
import { CategoryTerm } from './popular-category-list';

type CategoryTreeItem = CategoryTerm & {
	children?: CategoryTreeItem[];
};

type CategoryTreeItemLabelValue = {
	children: CategoryTreeItemLabelValue[];
	label: string;
	value: string;
};

const categoryLibrary: Record< number, CategoryTreeItem > = {};
function convertTreeToLabelValue(
	tree: CategoryTreeItem[],
	newTree: CategoryTreeItemLabelValue[] = []
) {
	for ( const child of tree ) {
		const newItem = {
			label: child.name,
			value: child.term_id.toString(),
			children: [],
		};
		categoryLibrary[ child.term_id ] = child;
		newTree.push( newItem );
		if ( child.children && child.children.length > 0 ) {
			convertTreeToLabelValue( child.children, newItem.children );
		}
	}
	return newTree;
}
declare const wc_enhanced_select_params: {
	search_categories_nonce: string;
};

export const AllCategoryList: React.FC< {
	selected: CategoryTerm[];
	onChange: ( selected: CategoryTerm[] ) => void;
} > = ( { selected, onChange } ) => {
	const [ filter, setFilter ] = useState( '' );
	const [ treeItems, setTreeItems ] = useState<
		CategoryTreeItemLabelValue[]
	>( [] );

	useEffect( () => {
		apiFetch< CategoryTreeItem[] >( {
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
		<>
			<div className="product-add-category__tree-control">
				<TreeSelectControl
					alwaysShowPlaceholder={ true }
					options={ treeItems }
					value={ selected.map( ( cat ) => cat.term_id.toString() ) }
					onChange={ ( sel: number[] ) => {
						onChange( sel.map( ( id ) => categoryLibrary[ id ] ) );
						syncWithMostUsed( sel );
					} }
					selectAllLabel={ false }
					onInputChange={ setFilter }
					placeholder="Add category"
					includeParent={ true }
				/>
			</div>
			<ul
				className="categorychecklist form-no-clear tagchecklist"
				id={ CATEGORY_TERM_NAME + 'checklist' }
			>
				{ selected.map( ( cat ) => (
					<li key={ cat.term_id }>
						<button
							type="button"
							className="ntdelbutton"
							onClick={ () => {
								const newSelectedItems = selected.filter(
									( sel ) => sel.term_id !== cat.term_id
								);
								onChange( newSelectedItems );
								syncWithMostUsed(
									newSelectedItems.map(
										( sel ) => sel.term_id
									)
								);
							} }
						>
							<span
								className="remove-tag-icon"
								aria-hidden="true"
							></span>
							<span className="screen-reader-text">
								{ sprintf(
									__( 'Remove term: %s', 'woocommerce' ),
									cat.name
								) }
							</span>
						</button>
						&nbsp;
						{ cat.name }
					</li>
				) ) }
			</ul>
		</>
	);
};
