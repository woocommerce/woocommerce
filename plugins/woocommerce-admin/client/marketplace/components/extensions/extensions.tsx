/**
 * External dependencies
 */
import { useContext } from '@wordpress/element';
import { __, _n, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './extensions.scss';
import CategorySelector from '../category-selector/category-selector';
import { ProductListContext } from '../../contexts/product-list-context';
import ProductListContent from '../product-list-content/product-list-content';
import ProductLoader from '../product-loader/product-loader';
import NoResults from '../product-list-content/no-results';

export default function Extensions(): JSX.Element {
	const productListContextValue = useContext( ProductListContext );

	const { productList, isLoading } = productListContextValue;
	const products = productList.slice( 0, 60 );

	let title = __( '0 extensions found', 'woocommerce' );

	if ( products.length > 0 ) {
		title = sprintf(
			// translators: %s: number of extensions
			_n(
				'%s extension',
				'%s extensions',
				products.length,
				'woocommerce'
			),
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

		return <ProductListContent products={ products } />;
	}

	return (
		<div className="woocommerce-marketplace__extensions">
			<h2 className="woocommerce-marketplace__product-list-title  woocommerce-marketplace__product-list-title--extensions">
				{ title }
			</h2>
			<CategorySelector />
			{ content() }
		</div>
	);
}
