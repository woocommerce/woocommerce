/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useCallback, useState } from '@wordpress/element';
import { addQueryArgs } from '@wordpress/url';
import { getSetting } from '@woocommerce/settings';
import {
	useAsyncFilter,
	__experimentalSelectControl as SelectControl,
} from '@woocommerce/components';
import { useUser } from '@woocommerce/data';
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

function getCategoryTermLabel( item: CategoryTerm | null ): string {
	return item?.name || '';
}

function getCategoryTermKey( item: CategoryTerm | null ): string {
	return String( item?.term_id );
}

export const CategoryAddNew: React.FC< {
	selectedCategoryTerms: CategoryTerm[];
	onChange: ( selected: CategoryTerm[] ) => void;
} > = ( { selectedCategoryTerms, onChange } ) => {
	const [ showAddNew, setShowAddNew ] = useState( false );
	const [ newCategoryName, setNewCategoryName ] = useState( '' );
	const [ categoryCreateError, setCategoryCreateError ] = useState( '' );
	const [ categoryParent, setCategoryParent ] = useState< CategoryTerm >();
	const [ fetchedItems, setFetchedItems ] = useState< CategoryTerm[] >( [] );
	const { currentUserCan } = useUser();

	const canEditTerms = currentUserCan( 'edit_product_terms' );

	const onCreate = ( event: React.MouseEvent< HTMLInputElement > ) => {
		event.preventDefault();
		if ( ! newCategoryName ) {
			return;
		}

		const data = {
			name: newCategoryName,
			parent: categoryParent?.term_id ?? -1,
		};
		setCategoryCreateError( '' );
		apiFetch< {
			id: number;
			name: string;
			count: number;
			parent: number;
		} >( {
			path: '/wc/v3/products/categories',
			data,
			method: 'POST',
		} )
			.then( ( res ) => {
				if ( res ) {
					recordEvent( 'product_category_add', {
						category_id: res.id,
						parent_id: res.parent,
						parent_category: res.parent > 0 ? 'Other' : 'None',
						page: 'product',
						async: true,
					} );
					onChange( [
						...selectedCategoryTerms,
						{ term_id: res.id, name: res.name, count: res.count },
					] );
					setNewCategoryName( '' );
					setCategoryParent( undefined );
					setShowAddNew( false );
				}
			} )
			.catch( ( error ) => {
				if ( error && error.message ) {
					setCategoryCreateError( error.message );
				}
			} );
	};

	const filter: ( value: string ) => Promise< CategoryTerm[] > = useCallback(
		async ( value = '' ) => {
			setFetchedItems( [] );
			return apiFetch< CategoryTerm[] >( {
				url: addQueryArgs(
					new URL(
						'admin-ajax.php',
						getSetting( 'adminUrl' )
					).toString(),
					{
						term: value,
						action: 'woocommerce_json_search_categories',
						// eslint-disable-next-line no-undef, camelcase
						security:
							wc_product_category_metabox_params.search_categories_nonce,
					}
				),
				method: 'GET',
			} ).then( ( response ) => {
				if ( response ) {
					setFetchedItems( Object.values( response ) );
				}
				return [];
			} );
		},
		[]
	);

	const { isFetching, ...selectProps } = useAsyncFilter< CategoryTerm >( {
		filter,
	} );

	if ( ! canEditTerms ) {
		return null;
	}

	return (
		<div id={ CATEGORY_TERM_NAME + '-adder' }>
			<a
				id="product_cat-add-toggle"
				href={ '#taxonomy-' + CATEGORY_TERM_NAME }
				className="taxonomy-add-new"
				onClick={ () => setShowAddNew( ! showAddNew ) }
				aria-label={ __( 'Add new category', 'woocommerce' ) }
			>
				{ __( '+ Add new category', 'woocommerce' ) }
			</a>
			{ showAddNew && (
				<div id="product_cat-add" className="category-add">
					<label
						className="screen-reader-text"
						htmlFor="newproduct_cat"
					>
						{ __( 'Add new category', 'woocommerce' ) }
					</label>
					<input
						type="text"
						name="newproduct_cat"
						id="newproduct_cat"
						className="form-required"
						placeholder={ __( 'New category name', 'woocommerce' ) }
						value={ newCategoryName }
						onChange={ ( event ) =>
							setNewCategoryName( event.target.value )
						}
						aria-required="true"
					/>
					<label
						className="screen-reader-text"
						htmlFor="newproduct_cat_parent"
					>
						{ __( 'Parent category:', 'woocommerce' ) }
					</label>
					<SelectControl< CategoryTerm >
						{ ...selectProps }
						label={ __( 'Parent category:', 'woocommerce' ) }
						items={ fetchedItems }
						selected={ categoryParent || null }
						placeholder={ __( 'Find category', 'woocommerce' ) }
						onSelect={ setCategoryParent }
						getItemLabel={ getCategoryTermLabel }
						getItemValue={ getCategoryTermKey }
						onRemove={ () => setCategoryParent( undefined ) }
					/>
					{ categoryCreateError && (
						<p className="category-add__error">
							{ categoryCreateError }
						</p>
					) }
					<input
						type="button"
						id="product_cat-add-submit"
						className="button category-add-submit"
						value={ __( 'Add new category', 'woocommerce' ) }
						disabled={ ! newCategoryName.length }
						onClick={ onCreate }
					/>
				</div>
			) }
		</div>
	);
};
