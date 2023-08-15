/**
 * Internal dependencies
 */
import './product-list-content.scss';
import ProductCard from '../product-card/product-card';
import { Product } from '../product-list/types';

export default function ProductListContent( props: {
	products: Product[];
} ): JSX.Element {
	const { products } = props;
	return (
		<div className="woocommerce-marketplace__product-list-content">
			{ products.map( ( product ) => (
				<ProductCard
					key={ product.id }
					type="classic"
					product={ {
						title: product.title,
						icon: product.icon,
						vendorName: product.vendorName,
						vendorUrl: product.vendorUrl,
						price: product.price,
						url: product.url,
						description: product.description,
					} }
				/>
			) ) }
		</div>
	);
}
