/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
import './search-results.scss';
import { Product, ProductType } from '../product-list/types';
import Extensions from '../extensions/extensions';
import Themes from '../themes/themes';
import { MARKETPLACE_SEARCH_RESULTS_PER_PAGE } from '../constants';

export interface SearchResultProps {
	products: Product[];
}

export default function SearchResults( props: SearchResultProps ): JSX.Element {
	const extensions = props.products.filter(
		( product ) => product.type === ProductType.extension
	);
	const themes = props.products.filter(
		( product ) => product.type === ProductType.theme
	);

	return (
		<div className="woocommerce-marketplace__search-results">
			<Extensions
				products={ extensions }
				perPage={ MARKETPLACE_SEARCH_RESULTS_PER_PAGE }
			/>
			<Themes
				products={ themes }
				perPage={ MARKETPLACE_SEARCH_RESULTS_PER_PAGE }
			/>
		</div>
	);
}
