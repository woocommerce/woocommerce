/**
 * Internal dependencies
 */
import './search-results.scss';
import { Product, ProductType } from '../product-list/types';
import Extensions from '../extensions/extensions';
//import { MARKETPLACE_SEARCH_RESULTS_PER_PAGE } from '../constants';

export interface SearchExtensionsProps {
	products: Product[];
}

export default function SearchExtensions(
	props: SearchExtensionsProps
): JSX.Element {
	const extensions = props.products.filter(
		( product ) => product.type === ProductType.extension
	);

	return (
		<div className="woocommerce-marketplace__search-results">
			<Extensions
				products={ extensions }
				categorySelector={ true }
				label={ 'extension' }
				labelPlural={ 'extensions' }
			/>
		</div>
	);
}
