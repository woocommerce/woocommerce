/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { navigateTo, getNewPath } from '@woocommerce/navigation';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './search-results.scss';
import { Product, ProductType } from '../product-list/types';
import Extensions from '../extensions/extensions';
import { MARKETPLACE_SEARCH_RESULTS_PER_PAGE } from '../constants';

export interface SearchResultProps {
	products: Product[];
}

const ALL_CATEGORIES_SLUGS = {
	[ ProductType.extension ]: '_all',
	[ ProductType.theme ]: 'themes',
};
const TAB = {
	[ ProductType.extension ]: 'search-extensions',
	[ ProductType.theme ]: 'search-themes',
};

export default function SearchResults( props: SearchResultProps ): JSX.Element {
	const extensions = props.products.filter(
		( product ) => product.type === ProductType.extension
	);
	const themes = props.products.filter(
		( product ) => product.type === ProductType.theme
	);

	function navigateToTab( tab: ProductType ) {
		navigateTo( {
			url: getNewPath( {
				category: ALL_CATEGORIES_SLUGS[ tab ],
				tab: TAB[ tab ],
			} ),
		} );
	}

	return (
		<div className="woocommerce-marketplace__search-results">
			<Extensions
				products={ extensions }
				perPage={ MARKETPLACE_SEARCH_RESULTS_PER_PAGE }
				label={ 'extension' }
				labelPlural={ 'extensions' }
			/>
			<Button
				className={ classnames(
					'woocommerce-marketplace__view-all-button',
					'woocommerce-marketplace__button-extensions'
				) }
				variant="secondary"
				text="View all"
				onClick={ () => navigateToTab( ProductType.extension ) }
			/>
			<Extensions
				products={ themes }
				perPage={ MARKETPLACE_SEARCH_RESULTS_PER_PAGE }
				label={ 'theme' }
				labelPlural={ 'themes' }
			/>
			<Button
				className={ classnames(
					'woocommerce-marketplace__view-all-button',
					'woocommerce-marketplace__button-themes'
				) }
				variant="secondary"
				text="View all"
				onClick={ () => navigateToTab( ProductType.theme ) }
			/>
		</div>
	);
}
