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
		if ( child.children?.length ) {
			convertTreeToLabelValue( child.children, newItem.children );
		}
	}
	newTree.sort(
		( a: CategoryTreeItemLabelValue, b: CategoryTreeItemLabelValue ) => {
			const nameA = a.label.toUpperCase();
			const nameB = b.label.toUpperCase();
			if ( nameA < nameB ) {
				return -1;
			}
			if ( nameA > nameB ) {
				return 1;
			}
			return 0;
		}
	);
	return newTree;
}

async function getTreeItems( filter: string ) {
	const resp = await apiFetch< CategoryTreeItem[] >( {
		url: addQueryArgs(
			new URL( 'admin-ajax.php', getSetting( 'adminUrl' ) ).toString(),
			{
				term: filter,
				action: 'woocommerce_json_search_categories_tree',
				// eslint-disable-next-line no-undef, camelcase
				security:
					wc_product_category_metabox_params.search_categories_nonce,
			}
		),
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
		selectedCategoryTerms: CategoryTerm[];
		onChange: ( selected: CategoryTerm[] ) => void;
	}
>( ( { selectedCategoryTerms, onChange }, ref ) => {
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
					value={ selectedCategoryTerms.map( ( category ) =>
						category.term_id.toString()
					) }
					onChange={ ( selectedCategoryIds: number[] ) => {
						onChange(
							selectedCategoryIds.map(
								( id ) => categoryLibrary[ id ]
							)
						);
						recordEvent( 'product_category_update', {
							page: 'product',
							async: true,
							selected: selectedCategoryIds.length,
						} );
					} }
					selectAllLabel={ false }
					onInputChange={ setFilter }
					placeholder={ __( 'Add category', 'woocommerce' ) }
					includeParent={ true }
					minFilterQueryLength={ 2 }
					clearOnSelect={ false }
					individuallySelectParent={ true }
				/>
			</div>
			<ul
				// Adding tagchecklist class to make use of already existing styling for the selected categories.
				className="categorychecklist form-no-clear tagchecklist"
				id={ CATEGORY_TERM_NAME + 'checklist' }
			>
				{ selectedCategoryTerms.map( ( selectedCategory ) => (
					<li key={ selectedCategory.term_id }>
						<button
							type="button"
							className="ntdelbutton"
							onClick={ () => {
								const newSelectedItems =
									selectedCategoryTerms.filter(
										( category ) =>
											category.term_id !==
											selectedCategory.term_id
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
									selectedCategory.name
								) }
							</span>
						</button>
						{ selectedCategory.name }
					</li>
				) ) }
			</ul>
		</>
	);
} );
