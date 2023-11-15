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
	const extensionList = props.products.filter(
		( product ) => product.type === ProductType.extension
	);
	const themeList = props.products.filter(
		( product ) => product.type === ProductType.theme
	);

	const query = useQuery();
	const showCategorySelector = query.section ? true : false;

	const extensionComponent = (
		<Products
			products={ extensionList }
			type={ ProductType.extension }
			categorySelector={ showCategorySelector }
		/>
	);

	const themeComponent = (
		<Products
			products={ themeList }
			type={ ProductType.theme }
			categorySelector={ showCategorySelector }
		/>
	);

	const content = () => {
		if ( query?.section === SearchResultType.theme ) {
			return themeComponent;
		}
		if ( query?.section === SearchResultType.extension ) {
			return extensionComponent;
		}

		if ( extensionList.length === 0 && themeList.length > 0 ) {
			return (
				<>
					{ themeComponent }
					{ extensionComponent }
				</>
			);
		}

		return (
			<>
				{ extensionComponent }
				{ themeComponent }
			</>
		);
	};

	return (
		<div className="woocommerce-marketplace__search-results">
			{ content() }
		</div>
	);
}
