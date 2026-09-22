/**
 * External dependencies
 */
import {
	InnerBlockLayoutContextProvider,
	ProductDataContextProvider,
} from '@woocommerce/shared-context';
import { ProductResponseItem } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { Block as ProductImage } from '../../../product-element-blocks/image/block';
import { Block as ProductName } from '../../../product-element-blocks/title/block';
import { Block as ProductRating } from '../../../product-element-blocks/rating-stars/block';
import { Block as ProductPrice } from '../../../product-element-blocks/price/block';
import { Block as ProductButton } from '../../../product-element-blocks/button/block';
import { ImageSizing } from '../../../product-element-blocks/image/types';

interface CrossSellsProductProps {
	product: ProductResponseItem;
	isLoading: boolean;
}

const CartCrossSellsProduct = ( {
	product,
}: CrossSellsProductProps ): JSX.Element => {
	return (
		<div className="cross-sells-product">
			<InnerBlockLayoutContextProvider
				parentName={ 'woocommerce/cart-cross-sells-block' }
				parentClassName={ 'wp-block-cart-cross-sells-product' }
			>
				<ProductDataContextProvider
					isLoading={ false }
					product={ product }
				>
					<div>
						<ProductImage
							className={ '' }
							showSaleBadge={ true }
							productId={ product.id }
							showProductLink={ true }
							saleBadgeAlign={ 'left' }
							imageSizing={ ImageSizing.SINGLE }
							scale={ 'cover' }
							aspectRatio={ '1:1' }
						/>
						<ProductName
							align={ '' }
							headingLevel={ 3 }
							showProductLink={ true }
						/>
						<ProductRating
							productId={ product.id }
							postId={ 0 }
							shouldDisplayMockedReviewsWhenProductHasNoReviews={
								false
							}
						/>
						<ProductPrice />
					</div>
					<ProductButton />
				</ProductDataContextProvider>
			</InnerBlockLayoutContextProvider>
		</div>
	);
};

export default CartCrossSellsProduct;
