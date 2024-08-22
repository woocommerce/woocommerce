/**
 * External dependencies
 */
import {
	useInnerBlockLayoutContext,
	useProductDataContext,
} from '@woocommerce/shared-context';
import { useStyleProps } from '@woocommerce/base-hooks';
import { withProductDataContext } from '@woocommerce/shared-hocs';
import {
	ProductRating,
	getAverageRating,
	getRatingCount,
} from '@woocommerce/editor-components/product-rating';

/**
 * Internal dependencies
 */
import './style.scss';

type ProductRatingProps = {
	className?: string;
	textAlign?: string;
	isDescendentOfSingleProductBlock: boolean;
	isDescendentOfQueryLoop: boolean;
	postId: number;
	productId: number;
	shouldDisplayMockedReviewsWhenProductHasNoReviews: boolean;
};

export const Block = ( props: ProductRatingProps ): JSX.Element | undefined => {
	const {
		textAlign = '',
		isDescendentOfSingleProductBlock,
		shouldDisplayMockedReviewsWhenProductHasNoReviews,
	} = props;
	const styleProps = useStyleProps( props );
	const { parentClassName } = useInnerBlockLayoutContext();
	const { product } = useProductDataContext();
	const rating = getAverageRating( product );
	const reviews = getRatingCount( product );

	const className = 'wc-block-components-product-rating';

	if ( reviews || shouldDisplayMockedReviewsWhenProductHasNoReviews ) {
		return (
			<ProductRating
				className={ className }
				showReviewCount={ isDescendentOfSingleProductBlock }
				showMockedReviews={
					shouldDisplayMockedReviewsWhenProductHasNoReviews
				}
				styleProps={ styleProps }
				parentClassName={ parentClassName }
				reviews={ reviews }
				rating={ rating }
				textAlign={ textAlign }
			/>
		);
	}
};

export default withProductDataContext( Block );
