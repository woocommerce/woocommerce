/**
 * External dependencies
 */
import clsx from 'clsx';
import { __, sprintf } from '@wordpress/i18n';
import type { RatingValues } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import './style.scss';

const Rating = ( {
	className,
	rating,
	ratedProductsCount,
}: RatingProps ): JSX.Element => {
	const ratingClassName = clsx(
		'wc-block-components-product-rating',
		className
	);

	const starStyle = {
		width: ( rating / 5 ) * 100 + '%',
	};

	const ratingText = sprintf(
		/* translators: %f is referring to the average rating value */
		__( 'Rated %f out of 5', 'woocommerce' ),
		rating
	);

	const ratingHTML = {
		__html: sprintf(
			/* translators: %s is the rating value wrapped in HTML strong tags. */
			__( 'Rated %s out of 5', 'woocommerce' ),
			sprintf( '<strong class="rating">%f</strong>', rating )
		),
	};

	return (
		<div className={ ratingClassName }>
			<div
				className={ 'wc-block-components-product-rating__stars' }
				role="img"
				aria-label={ ratingText }
			>
				<span
					style={ starStyle }
					dangerouslySetInnerHTML={ ratingHTML }
				/>
			</div>
			{ ratedProductsCount !== null ? (
				<span className={ 'wc-block-components-product-rating-count' }>
					({ ratedProductsCount })
				</span>
			) : null }
		</div>
	);
};

interface RatingProps {
	className?: string;
	rating: RatingValues;
	ratedProductsCount?: number | null;
}

export default Rating;
