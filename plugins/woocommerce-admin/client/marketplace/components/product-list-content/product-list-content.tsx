/**
 * Internal dependencies
 */
import './product-list-content.scss';
// eslint-disable-next-line @typescript-eslint/no-unused-vars, @woocommerce/dependency-group
import ProductCard from '../product-card/product-card';

export interface Product {
	id?: number;
	title: string;
	description: string;
	vendorName: string;
	vendorUrl: string;
	icon: string;
	url: string;
	price: string | number;
	productType?: string;
	averageRating?: number | null;
	reviewsCount?: number | null;
	currency?: string;
}
interface ProductListContentProps {
	products: Product[];
}

export default function ProductListContent(
	props: ProductListContentProps
): JSX.Element {
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
