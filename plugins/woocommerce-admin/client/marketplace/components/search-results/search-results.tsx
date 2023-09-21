/**
 * External dependencies
 */
import { useContext } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './search-results.scss';
import { Product, ProductType } from '../product-list/types';
import Extensions from '../extensions/extensions';
import Themes from '../themes/themes';
import { MARKETPLACE_SEARCH_RESULTS_PER_PAGE } from '../constants';
import NoResults from '../product-list-content/no-results';
import { MarketplaceContext } from '../../contexts/marketplace-context';
import ProductLoader from '../product-loader/product-loader';

export interface SearchResultProps {
	products?: Product[];
}

export default function SearchResults( props: SearchResultProps ): JSX.Element {
	const marketplaceContextValue = useContext( MarketplaceContext );
	const { isLoading } = marketplaceContextValue;
	const extensions = props.products?.filter(
		( product ) => product.type === ProductType.extension
	);
	const themes = props.products?.filter(
		( product ) => product.type === ProductType.theme
	);

	function content() {
		if ( isLoading ) {
			return <ProductLoader />;
		}

		if ( props.products?.length === 0 ) {
			return <NoResults />;
		}

		return (
			<>
				{ !! extensions?.length && (
					<Extensions
						products={ extensions }
						perPage={ MARKETPLACE_SEARCH_RESULTS_PER_PAGE }
					/>
				) }
				{ !! themes?.length && (
					<Themes
						products={ themes }
						perPage={ MARKETPLACE_SEARCH_RESULTS_PER_PAGE }
					/>
				) }
			</>
		);
	}

	return (
		<div className="woocommerce-marketplace__search-results">
			{ content() }
		</div>
	);
}
