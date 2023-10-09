/**
 * Internal dependencies
 */
import './product-list-content.scss';
import ProductCard from '../product-card/product-card';
import { Product, ProductType } from '../product-list/types';
import { appendURLParams } from '../../utils/functions';
import { getAdminSetting } from '../../../utils/admin-settings';

export default function ProductListContent( props: {
	products: Product[];
	type: ProductType;
} ): JSX.Element {
	const wccomHelperSettings = getAdminSetting( 'wccomHelper', {} );

	return (
		<div className="woocommerce-marketplace__product-list-content">
			{ props.products.map( ( product ) => (
				<ProductCard
					key={ product.id }
					type={ props.type }
					product={ {
						title: product.title,
						image: product.image,
						type: product.type,
						icon: product.icon,
						vendorName: product.vendorName,
						vendorUrl: product.vendorUrl
							? appendURLParams( product.vendorUrl, [
									[ 'utm_source', 'extensionsscreen' ],
									[ 'utm_medium', 'product' ],
									[ 'utm_campaign', 'wcaddons' ],
									[ 'utm_content', 'devpartner' ],
							  ] )
							: '',
						price: product.price,
						url: appendURLParams(
							product.url,
							Object.entries(
								wccomHelperSettings.inAppPurchaseURLParams
							)
						),
						description: product.description,
					} }
				/>
			) ) }
		</div>
	);
}
