/**
 * External dependencies
 */
import { useQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import './search-results.scss';
import { Product, ProductType, SearchResultType } from '../product-list/types';
import Products from '../products/products';

export interface SearchResultProps {
	products: Product[];
	type: SearchResultType;
}

export default function SearchResults( props: SearchResultProps ): JSX.Element {
	const extensions = props.products.filter(
		( product ) => product.type === ProductType.extension
	);
	const themes = props.products.filter(
		( product ) => product.type === ProductType.theme
	);

	const query = useQuery();
	const showCategorySelector = query.section ? true : false;

	return (
		<div className="woocommerce-marketplace__search-results">
			{ query?.section !== SearchResultType.theme && (
				<Products
					products={ extensions }
					type={ ProductType.extension }
					categorySelector={ showCategorySelector }
				/>
			) }
			{ query?.section !== SearchResultType.extension && (
				<Products
					products={ themes }
					type={ ProductType.theme }
					categorySelector={ showCategorySelector }
				/>
			) }
		</div>
	);
}
