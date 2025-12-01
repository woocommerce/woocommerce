/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import clsx from 'clsx';
import type { CSSProperties } from 'react';
import { isNumber, ProductResponseItem } from '@woocommerce/types';
import { ProductEntityResponse } from '@woocommerce/entities';

type RatingProps = {
	className: string;
	reviews: number;
	rating: number;
	parentClassName?: string;
};

export const getAverageRating = (
	product:
		| ( Omit< ProductResponseItem, 'average_rating' > & {
				average_rating: string;
		  } )
		| ProductEntityResponse
) => {
	const rating = parseFloat( product.average_rating );

	return Number.isFinite( rating ) && rating > 0 ? rating : 0;
};

export const getRatingCount = (
	product: ProductResponseItem | ProductEntityResponse
) => {
	if ( 'review_count' in product ) {
		const count = isNumber( product.review_count )
			? product.review_count
			: parseInt( product.review_count, 10 );

		return Number.isFinite( count ) && count > 0 ? count : 0;
	}

	if ( 'rating_count' in product ) {
		const count = isNumber( product.rating_count )
			? product.rating_count
			: parseInt( product.rating_count, 10 );

		return Number.isFinite( count ) && count > 0 ? count : 0;
	}

	return 0;
};

const getStarStyle = ( rating: number ) => ( {
	width: ( rating / 5 ) * 100 + '%',
} );

const NoRating = ( {
	className,
	parentClassName,
}: {
	className: string;
	parentClassName: string;
} ) => {
	const starStyle = getStarStyle( 0 );

	return (
		<div
			className={ clsx(
				`${ className }__norating-container`,
				`${ parentClassName }-product-rating__norating-container`
			) }
		>
			<div className={ `${ className }__norating` } role="img">
				<span style={ starStyle } />
			</div>
			<span>{ __( 'No Reviews', 'woocommerce' ) }</span>
		</div>
	);
};

const Rating = ( props: RatingProps ): JSX.Element => {
	const { className, rating, reviews, parentClassName } = props;

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
				`${ className }__stars`,
				`${ parentClassName }__product-rating__stars`
			) }
			role="img"
			aria-label={ ratingText }
		>
			<span style={ starStyle } dangerouslySetInnerHTML={ ratingHTML } />
		</div>
	);
};

const ReviewsCount = ( props: {
	className: string;
	reviews: number;
} ): JSX.Element => {
	const { className, reviews } = props;

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
		<span className={ `${ className }__reviews_count` }>
			{ reviewsCount }
		</span>
	);
};

type ProductRatingProps = {
	className: string;
	showReviewCount?: boolean;
	showMockedReviews?: boolean;
	parentClassName?: string;
	rating: number;
	reviews: number;
	styleProps: {
		className: string;
		style: CSSProperties;
	};
	textAlign?: string;
};

export const ProductRating = (
	props: ProductRatingProps
): JSX.Element | null => {
	const {
		className = 'wc-block-components-product-rating',
		showReviewCount,
		showMockedReviews,
		parentClassName = '',
		rating,
		reviews,
		styleProps,
		textAlign,
	} = props;

	const wrapperClassName = clsx( styleProps.className, className, {
		[ `${ parentClassName }__product-rating` ]: parentClassName,
		[ `has-text-align-${ textAlign }` ]: textAlign,
	} );

	const mockedRatings = showMockedReviews && (
		<NoRating className={ className } parentClassName={ parentClassName } />
	);

	const content = reviews ? (
		<Rating
			className={ className }
			rating={ rating }
			reviews={ reviews }
			parentClassName={ parentClassName }
		/>
	) : (
		mockedRatings
	);

	const isReviewCountVisible = reviews && showReviewCount;

	return (
		<div className={ wrapperClassName } style={ styleProps.style }>
			<div className={ `${ className }__container` }>
				{ content }
				{ isReviewCountVisible ? (
					<ReviewsCount className={ className } reviews={ reviews } />
				) : null }
			</div>
		</div>
	);
};
