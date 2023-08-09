/**
 * External dependencies
 */
import { useContext } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './extensions.scss';
import CategorySelector from '../category-selector/category-selector';
import { ProductListContext } from '../../contexts/product-list-context';
import ProductListContent from '../product-list-content/product-list-content';
import ProductLoader from '../product-loader/product-loader';

export default function Extensions(): JSX.Element {
	const productListContextValue = useContext( ProductListContext );

	const { productList, isLoading } = productListContextValue;
	const products = productList.slice( 0, 60 );

	let title = __( 'Extensions', 'woocommerce' );

	if ( products.length > 0 ) {
		title = sprintf(
			// translators: %s: number of extensions
			__( '%s extensions', 'woocommerce' ),
			products.length
		);
	}

	return (
		<div className="woocommerce-marketplace__extensions">
			<h2 className="woocommerce-marketplace__product-list-title  woocommerce-marketplace__product-list-title--extensions">
				{ title }
			</h2>
			<CategorySelector />
			{ isLoading ? (
				<ProductLoader />
			) : (
				<ProductListContent products={ products } />
			) }
		</div>
	);
}
