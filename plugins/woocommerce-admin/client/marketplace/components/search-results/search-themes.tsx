/**
 * Internal dependencies
 */
import './search-results.scss';
import { Product, ProductType } from '../product-list/types';
import Extensions from '../extensions/extensions';

export interface SearchExtensionsProps {
	products: Product[];
}

export default function SearchExtensions(
	props: SearchExtensionsProps
): JSX.Element {
	const extensions = props.products.filter(
		( product ) => product.type === ProductType.theme
	);

	return (
		<div className="woocommerce-marketplace__search-results">
			<Extensions
				products={ extensions }
				categorySelector={ true }
				label={ 'theme' }
				labelPlural={ 'themes' }
			/>
		</div>
	);
}
