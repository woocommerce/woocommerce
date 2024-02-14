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
import { Block as ProductImage } from '../../../atomic/blocks/product-elements/image/block';
import { Block as ProductName } from '../../../atomic/blocks/product-elements/title/block';
import { Block as ProductRating } from '../../../atomic/blocks/product-elements/rating-stars/block';
import { Block as ProductPrice } from '../../../atomic/blocks/product-elements/price/block';
import { Block as ProductButton } from '../../../atomic/blocks/product-elements/button/block';
import { ImageSizing } from '../../../atomic/blocks/product-elements/image/types';

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
							isDescendentOfQueryLoop={ false }
							scale={ 'cover' }
							aspectRatio={ '1:1' }
						/>
						<ProductName
							align={ '' }
							headingLevel={ 3 }
							showProductLink={ true }
						/>
						<ProductRating
							isDescendentOfQueryLoop={ false }
							isDescendentOfSingleProductBlock={ false }
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
