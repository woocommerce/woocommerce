/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { useContext } from '@wordpress/element';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './extensions.scss';
import { MarketplaceContext } from '../../contexts/marketplace-context';
import CategorySelector from '../category-selector/category-selector';
import ProductListContent from '../product-list-content/product-list-content';
import ProductLoader from '../product-loader/product-loader';
import NoResults from '../product-list-content/no-results';
import { Product, ProductType } from '../product-list/types';
import { MARKETPLACE_ITEMS_PER_PAGE } from '../constants';

interface ExtensionsProps {
	products?: Product[];
	perPage?: number;
	label: string;
	labelPlural: string;
}

export default function Extensions( props: ExtensionsProps ): JSX.Element {
	const marketplaceContextValue = useContext( MarketplaceContext );
	const { isLoading } = marketplaceContextValue;
	// const of ProductType based on the props.label
	const type =
		props.label === 'extension' ? ProductType.extension : ProductType.theme;

	const products =
		props.products?.slice(
			0,
			props.perPage ?? MARKETPLACE_ITEMS_PER_PAGE
		) ?? [];

	let title = sprintf(
		// translators: %s: plural item type (e.g. extensions, themes)
		__( '0 %s found', 'woocommerce' ),
		props.labelPlural
	);

	if ( products.length > 0 ) {
		title = sprintf(
			// translators: %1$s: number of items, %2$s: singular item label, %3$s: plural item label
			_n( '%1$s %2$s', '%1$s %3$s', products.length, 'woocommerce' ),
			products.length,
			props.label,
			props.labelPlural
		);
	}

	const baseContainerClassName = 'woocommerce-marketplace__';
	const containerClassName = classnames(
		baseContainerClassName + props.labelPlural
	);

	const baseProductListTitleClassName =
		'woocommerce-marketplace__product-list-title--';
	const productListTitleClassName = classnames(
		'woocommerce-marketplace__product-list-title',
		baseProductListTitleClassName + props.labelPlural
	);

	function content() {
		if ( isLoading ) {
			return <ProductLoader />;
		}

		if ( products.length === 0 ) {
			return <NoResults type={ type } />;
		}

		return (
			<>
				<CategorySelector type={ type } />
				<ProductListContent products={ products } type={ type } />
			</>
		);
	}

	return (
		<div className={ containerClassName }>
			<h2 className={ productListTitleClassName }>{ title }</h2>
			{ content() }
		</div>
	);
}
