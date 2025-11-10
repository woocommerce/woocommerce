/**
 * External dependencies
 */
import { ProductResponseItemData, CartVariationItem } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import ProductDetails from '../product-details';
import './style.scss';

interface ProductMetadataProps {
	shortDescription?: string;
	fullDescription?: string;
	itemData: ProductResponseItemData[];
	variation?: CartVariationItem[];
}

const ProductMetadata = ( {
	itemData = [],
	variation = [],
}: ProductMetadataProps ): JSX.Element => {
	return (
		<div className="wc-block-components-product-metadata">
			<ProductDetails details={ itemData } />
			<ProductDetails
				details={ variation.map( ( { attribute = '', value } ) => ( {
					key: attribute,
					value,
				} ) ) }
			/>
		</div>
	);
};

export default ProductMetadata;
