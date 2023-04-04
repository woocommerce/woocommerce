/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import {
	forwardRef,
	useCallback,
	useEffect,
	useImperativeHandle,
	useState,
} from '@wordpress/element';
import { addQueryArgs } from '@wordpress/url';
import { useDebounce } from '@wordpress/compose';
import { TreeSelectControl } from '@woocommerce/components';
import { getSetting } from '@woocommerce/settings';
import { recordEvent } from '@woocommerce/tracks';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { CATEGORY_TERM_NAME } from './category-handlers';
import { CategoryTerm } from './popular-category-list';

declare const wc_product_category_metabox_params: {
	search_categories_nonce: string;
};

type CategoryTreeItem = CategoryTerm & {
	children?: CategoryTreeItem[];
};

type CategoryTreeItemLabelValue = {
	children: CategoryTreeItemLabelValue[];
	label: string;
	value: string;
};

export const DEFAULT_DEBOUNCE_TIME = 250;

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

async function getTreeItems( filter: string ) {
	const resp = await apiFetch< CategoryTreeItem[] >( {
		url: addQueryArgs( getSetting( 'adminUrl' ) + 'admin-ajax.php', {
			term: filter,
			action: 'woocommerce_json_search_categories_tree',
			// eslint-disable-next-line no-undef, camelcase
			security:
				wc_product_category_metabox_params.search_categories_nonce,
		} ),
		method: 'GET',
	} );
	if ( resp ) {
		return convertTreeToLabelValue( Object.values( resp ) );
	}
	return [];
}

export const AllCategoryList = forwardRef<
	{ resetInitialValues: () => void },
	{
		selected: CategoryTerm[];
		onChange: ( selected: CategoryTerm[] ) => void;
	}
>( ( { selected, onChange }, ref ) => {
	const [ filter, setFilter ] = useState( '' );
	const [ treeItems, setTreeItems ] = useState<
		CategoryTreeItemLabelValue[]
	>( [] );

	const searchCategories = useCallback(
		( value: string ) => {
			if ( value && value.length > 0 ) {
				recordEvent( 'product_category_search', {
					page: 'product',
					async: true,
					search_string_length: value.length,
				} );
			}
			getTreeItems( value ).then( ( res ) => {
				setTreeItems( Object.values( res ) );
			} );
		},
		[ setTreeItems ]
	);
	const searchCategoriesDebounced = useDebounce(
		searchCategories,
		DEFAULT_DEBOUNCE_TIME
	);

	useEffect( () => {
		searchCategoriesDebounced( filter );
	}, [ filter ] );

	useImperativeHandle(
		ref,
		() => {
			return {
				resetInitialValues() {
					getTreeItems( '' ).then( ( res ) => {
						setTreeItems( Object.values( res ) );
					} );
				},
			};
		},
		[]
	);

	return (
		<>
			<div className="product-add-category__tree-control">
				<TreeSelectControl
					alwaysShowPlaceholder={ true }
					options={ treeItems }
					value={ selected.map( ( cat ) => cat.term_id.toString() ) }
					onChange={ ( sel: number[] ) => {
						onChange( sel.map( ( id ) => categoryLibrary[ id ] ) );
						recordEvent( 'product_category_update', {
							page: 'product',
							async: true,
							selected: sel.length,
						} );
					} }
					selectAllLabel={ false }
					onInputChange={ setFilter }
					placeholder={ __( 'Add category', 'woocommerce' ) }
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
} );
