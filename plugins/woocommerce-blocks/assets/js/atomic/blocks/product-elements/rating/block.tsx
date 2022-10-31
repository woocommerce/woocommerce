/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import classnames from 'classnames';
import {
	useInnerBlockLayoutContext,
	useProductDataContext,
} from '@woocommerce/shared-context';
import {
	useColorProps,
	useSpacingProps,
	useTypographyProps,
} from '@woocommerce/base-hooks';
import { withProductDataContext } from '@woocommerce/shared-hocs';
import { isNumber, ProductResponseItem } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import './style.scss';

type Props = {
	className?: string;
};

const getAverageRating = (
	product: Omit< ProductResponseItem, 'average_rating' > & {
		average_rating: string;
	}
) => {
	const rating = parseFloat( product.average_rating );

	return Number.isFinite( rating ) && rating > 0 ? rating : 0;
};

const getRatingCount = ( product: ProductResponseItem ) => {
	const count = isNumber( product.review_count )
		? product.review_count
		: parseInt( product.review_count, 10 );

	return Number.isFinite( count ) && count > 0 ? count : 0;
};

/**
 * Product Rating Block Component.
 *
 * @param {Object} props             Incoming props.
 * @param {string} [props.className] CSS Class name for the component.
 * @return {*} The component.
 */
export const Block = ( props: Props ): JSX.Element | null => {
	const { parentClassName } = useInnerBlockLayoutContext();
	const { product } = useProductDataContext();
	const rating = getAverageRating( product );
	const colorProps = useColorProps( props );
	const typographyProps = useTypographyProps( props );
	const spacingProps = useSpacingProps( props );

	if ( ! rating ) {
		return null;
	}

	const starStyle = {
		width: ( rating / 5 ) * 100 + '%',
	};

	const ratingText = sprintf(
		/* translators: %f is referring to the average rating value */
		__( 'Rated %f out of 5', 'woo-gutenberg-products-block' ),
		rating
	);

	const reviews = getRatingCount( product );
	const ratingHTML = {
		__html: sprintf(
			/* translators: %1$s is referring to the average rating value, %2$s is referring to the number of ratings */
			_n(
				'Rated %1$s out of 5 based on %2$s customer rating',
				'Rated %1$s out of 5 based on %2$s customer ratings',
				reviews,
				'woo-gutenberg-products-block'
			),
			sprintf( '<strong class="rating">%f</strong>', rating ),
			sprintf( '<span class="rating">%d</span>', reviews )
		),
	};

	return (
		<div
			className={ classnames(
				colorProps.className,
				'wc-block-components-product-rating',
				{
					[ `${ parentClassName }__product-rating` ]: parentClassName,
				}
			) }
			style={ {
				...colorProps.style,
				...typographyProps.style,
				...spacingProps.style,
			} }
		>
			<div
				className={ classnames(
					'wc-block-components-product-rating__stars',
					`${ parentClassName }__product-rating__stars`
				) }
				role="img"
				aria-label={ ratingText }
			>
				<span
					style={ starStyle }
					dangerouslySetInnerHTML={ ratingHTML }
				/>
			</div>
		</div>
	);
};

export default withProductDataContext( Block );
