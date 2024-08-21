/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import clsx from 'clsx';
import type { CSSProperties } from 'react';

type RatingProps = {
	reviews: number;
	rating: number;
	parentClassName?: string;
};

const getStarStyle = ( rating: number ) => ( {
	width: ( rating / 5 ) * 100 + '%',
} );

const NoRating = ( { parentClassName }: { parentClassName: string } ) => {
	const starStyle = getStarStyle( 0 );

	return (
		<div
			className={ clsx(
				'wc-block-components-product-rating__norating-container',
				`${ parentClassName }-product-rating__norating-container`
			) }
		>
			<div
				className={ 'wc-block-components-product-rating__norating' }
				role="img"
			>
				<span style={ starStyle } />
			</div>
			<span>{ __( 'No Reviews', 'woocommerce' ) }</span>
		</div>
	);
};

const Rating = ( props: RatingProps ): JSX.Element => {
	const { rating, reviews, parentClassName } = props;

	const starStyle = getStarStyle( rating );

	const ratingText = sprintf(
		/* translators: %f is referring to the average rating value */
		__( 'Rated %f out of 5', 'woocommerce' ),
		rating
	);

	const ratingHTML = {
		__html: sprintf(
			/* translators: %1$s is referring to the average rating value, %2$s is referring to the number of ratings */
			_n(
				'Rated %1$s out of 5 based on %2$s customer rating',
				'Rated %1$s out of 5 based on %2$s customer ratings',
				reviews,
				'woocommerce'
			),
			sprintf( '<strong class="rating">%f</strong>', rating ),
			sprintf( '<span class="rating">%d</span>', reviews )
		),
	};
	return (
		<div
			className={ clsx(
				'wc-block-components-product-rating__stars',
				`${ parentClassName }__product-rating__stars`
			) }
			role="img"
			aria-label={ ratingText }
		>
			<span style={ starStyle } dangerouslySetInnerHTML={ ratingHTML } />
		</div>
	);
};

const ReviewsCount = ( props: { reviews: number } ): JSX.Element => {
	const { reviews } = props;

	const reviewsCount = sprintf(
		/* translators: %s is referring to the total of reviews for a product */
		_n(
			'(%s customer review)',
			'(%s customer reviews)',
			reviews,
			'woocommerce'
		),
		reviews
	);

	return (
		<span className="wc-block-components-product-rating__reviews_count">
			{ reviewsCount }
		</span>
	);
};

type ProductRatingProps = {
	className: string;
	isDescendentOfSingleProductBlock: boolean;
	shouldDisplayMockedReviewsWhenProductHasNoReviews?: boolean;
	style: CSSProperties;
	parentClassName?: string;
	rating: number;
	reviews: number;
};

const ProductRating = ( props: ProductRatingProps ): JSX.Element | null => {
	const {
		className,
		isDescendentOfSingleProductBlock,
		shouldDisplayMockedReviewsWhenProductHasNoReviews,
		style,
		parentClassName = '',
		rating,
		reviews,
	} = props;

	const mockedRatings = shouldDisplayMockedReviewsWhenProductHasNoReviews && (
		<NoRating parentClassName={ parentClassName } />
	);

	const content = reviews ? (
		<Rating
			rating={ rating }
			reviews={ reviews }
			parentClassName={ parentClassName }
		/>
	) : (
		mockedRatings
	);

	const isReviewCountVisible = reviews && isDescendentOfSingleProductBlock;

	return (
		<div className={ className } style={ style }>
			<div className="wc-block-components-product-rating__container">
				{ content }
				{ isReviewCountVisible && <ReviewsCount reviews={ reviews } /> }
			</div>
		</div>
	);
};

export default ProductRating;
