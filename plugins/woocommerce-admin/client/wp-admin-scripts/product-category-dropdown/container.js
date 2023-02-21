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
import {
	getSelectedCategoryData,
	CATEGORY_TERM_NAME,
	syncWithMostUsed,
	onMostUsedChanged,
	removeOnMostUsedChanged,
} from './category-handlers';

const categoryLibrary = {};
function convertTreeToLabelValue( tree, newTree = [] ) {
	for ( const child of tree ) {
		const newItem = {
			label: child.name,
			value: child.term_id,
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

const selectedCategories = getSelectedCategoryData();
export const CategoryDropdownContainer = () => {
	const [ filter, setFilter ] = useState( '' );
	const [ selected, setSelected ] = useState( selectedCategories );
	const [ treeItems, setTreeItems ] = useState( [] );

	useEffect( () => {
		const callback = ( event ) => {
			if ( event.target && event.target.checked ) {
				const id = parseInt( event.target.value, 10 );
				if (
					selected.findIndex( ( sel ) => sel.term_id === id ) === -1
				) {
					setSelected( [
						...selected,
						categoryLibrary[ id ] || {
							term_id: id,
							name: event.target.parentElement.textContent.trim(),
						},
					] );
				}
			} else if ( event.target && ! event.target.checked ) {
				setSelected(
					selected.filter(
						( sel ) =>
							sel.term_id !== parseInt( event.target.value, 10 )
					)
				);
			}
		};
		onMostUsedChanged( callback );

		return () => {
			removeOnMostUsedChanged( callback );
		};
	}, [ selected ] );

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
		<>
			<div className="product-add-category__tree-control">
				<TreeSelectControl
					alwaysShowPlaceholder={ true }
					options={ treeItems }
					value={ selected.map( ( cat ) => cat.term_id ) }
					onChange={ ( sel ) => {
						setSelected(
							sel.map( ( id ) => categoryLibrary[ id ] )
						);
						syncWithMostUsed( sel );
					} }
					selectAllLabel={ false }
					onInputChange={ setFilter }
					placeholder="Add category"
					includeParent={ true }
				/>
				{ ( selected || [] ).map( ( sel ) => (
					<input
						key={ sel.term_id }
						type="hidden"
						value={ sel.term_id }
						name={ 'tax_input[' + CATEGORY_TERM_NAME + '][]' }
					/>
				) ) }
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
								setSelected( newSelectedItems );
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
