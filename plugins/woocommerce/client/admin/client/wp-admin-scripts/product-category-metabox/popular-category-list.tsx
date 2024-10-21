/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import { addQueryArgs } from '@wordpress/url';
import { getSetting } from '@woocommerce/settings';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { CATEGORY_TERM_NAME } from './category-handlers';

declare const wc_product_category_metabox_params: {
	search_taxonomy_terms_nonce: string;
};

export type CategoryTerm = {
	name: string;
	term_id: number;
	count: number;
};

export const PopularCategoryList: React.FC< {
	selected: CategoryTerm[];
	onChange: ( selected: CategoryTerm[] ) => void;
} > = ( { selected, onChange } ) => {
	const [ popularCategories, setPopularCategories ] = useState<
		CategoryTerm[]
	>( [] );

	useEffect( () => {
		apiFetch< CategoryTerm[] >( {
			url: addQueryArgs(
				new URL(
					'admin-ajax.php',
					getSetting( 'adminUrl' )
				).toString(),
				{
					action: 'woocommerce_json_search_taxonomy_terms',
					taxonomy: CATEGORY_TERM_NAME,
					limit: 10,
					orderby: 'count',
					order: 'DESC',
					// eslint-disable-next-line no-undef, camelcase
					security:
						wc_product_category_metabox_params.search_taxonomy_terms_nonce,
				}
			),
			method: 'GET',
		} ).then( ( res ) => {
			if ( res ) {
				setPopularCategories( res.filter( ( cat ) => cat.count > 0 ) );
			}
		} );
	}, [] );

	const selectedIds = selected.map( ( sel ) => sel.term_id );

	return (
		<ul
			className="categorychecklist form-no-clear"
			id={ CATEGORY_TERM_NAME + 'checklist-pop' }
		>
			{ popularCategories.map( ( cat ) => {
				const categoryCheckboxId = `in-popular-${ CATEGORY_TERM_NAME }-${ cat.term_id }`;
				return (
					<li key={ cat.term_id } className="popular-category">
						<label
							className="selectit"
							htmlFor={ categoryCheckboxId }
						>
							<input
								type="checkbox"
								id={ categoryCheckboxId }
								checked={ selectedIds.includes( cat.term_id ) }
								onChange={ () => {
									if ( selectedIds.includes( cat.term_id ) ) {
										onChange(
											selected.filter(
												( sel ) =>
													sel.term_id !== cat.term_id
											)
										);
									} else {
										onChange( [ ...selected, cat ] );
									}
								} }
							/>
							{ cat.name }
						</label>
					</li>
				);
			} ) }
		</ul>
	);
};
