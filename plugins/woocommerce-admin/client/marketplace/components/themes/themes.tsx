/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { useContext } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './themes.scss';
import { MarketplaceContext } from '../../contexts/marketplace-context';
import CategorySelector from '../category-selector/category-selector';
import ProductListContent from '../product-list-content/product-list-content';
import ProductLoader from '../product-loader/product-loader';
import NoResults from '../product-list-content/no-results';
import { Product, ProductType } from '../product-list/types';
import { MARKETPLACE_ITEMS_PER_PAGE } from '../constants';

interface ThemeProps {
	products?: Product[];
	perPage?: number;
}

export default function Themes( props: ThemeProps ): JSX.Element {
	const marketplaceContextValue = useContext( MarketplaceContext );
	const { isLoading } = marketplaceContextValue;
	const products =
		props.products?.slice(
			0,
			props.perPage ?? MARKETPLACE_ITEMS_PER_PAGE
		) ?? [];

	let title = __( '0 themes found', 'woocommerce' );

	if ( products.length > 0 ) {
		title = sprintf(
			// translators: %s: number of themes
			_n( '%s theme', '%s themes', products.length, 'woocommerce' ),
			products.length
		);
	}

	function content() {
		if ( isLoading ) {
			return <ProductLoader />;
		}

		if ( products.length === 0 ) {
			return <NoResults />;
		}

		return (
			<>
				<CategorySelector type={ ProductType.theme } />
				<ProductListContent
					products={ products }
					type={ ProductType.theme }
				/>
			</>
		);
	}

	return (
		<div className="woocommerce-marketplace__themes">
			<h2 className="woocommerce-marketplace__product-list-title  woocommerce-marketplace__product-list-title--themes">
				{ title }
			</h2>
			{ content() }
		</div>
	);
}
