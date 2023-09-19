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

export interface SearchResultProps {
	products?: Product[];
}

export default function SearchResults( props: SearchResultProps ): JSX.Element {
	const extensions = props.products
		?.filter( ( product ) => product.type === ProductType.extension )
		.slice( 0, 60 );
	const themes = props.products
		?.filter( ( product ) => product.type === ProductType.theme )
		.slice( 0, 60 );

	return (
		<div className="woocommerce-marketplace__search-results">
			<Extensions products={ extensions } />
			<Themes products={ themes } />
		</div>
	);
}
