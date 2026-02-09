/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useId } from '@wordpress/element';
import clsx from 'clsx';
import ReadMore from '@woocommerce/base-components/read-more';
import { ReviewBlockAttributes } from '@woocommerce/blocks/reviews/attributes';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Internal dependencies
 */
import './style.scss';
import type { Review } from '../types';

function getReviewImage(
	review: Review,
	imageType: string,
	isLoading: boolean
): JSX.Element {
	if ( isLoading || ! review ) {
		return (
			<div className="wc-block-review-list-item__image wc-block-components-review-list-item__image" />
		);
	}

	return (
		<div className="wc-block-review-list-item__image wc-block-components-review-list-item__image">
			{ imageType === 'product' ? (
				<img
					aria-hidden="true"
					alt={ review.product_image?.alt || '' }
					src={ review.product_image?.thumbnail || '' }
				/>
			) : (
				// The alt text is left empty on purpose, as it's considered a decorative image.
				// More can be found here: https://www.w3.org/WAI/tutorials/images/decorative/.
				// Github discussion for a context: https://github.com/woocommerce/woocommerce-blocks/pull/7651#discussion_r1019560494.
				<img
					aria-hidden="true"
					alt=""
					src={ review.reviewer_avatar_urls[ '96' ] || '' }
				/>
			) }
			{ review.verified && (
				<div
					className="wc-block-review-list-item__verified wc-block-components-review-list-item__verified"
					title={ __( 'Verified buyer', 'woocommerce' ) }
				>
					{ __( 'Verified buyer', 'woocommerce' ) }
				</div>
			) }
		</div>
	);
}

function getReviewContent( review: Review ): JSX.Element {
	return (
		<ReadMore
			maxLines={ 10 }
			moreText={ __( 'Read full review', 'woocommerce' ) }
			lessText={ __( 'Hide full review', 'woocommerce' ) }
			className="wc-block-review-list-item__text wc-block-components-review-list-item__text"
		>
			<div
				dangerouslySetInnerHTML={ {
					// `content` is the `review` parameter returned by the `reviews` endpoint.
					// It's filtered with `wp_filter_post_kses()`, which removes dangerous HTML tags,
					// so using it inside `dangerouslySetInnerHTML` is safe.
					__html: review.review || '',
				} }
			/>
		</ReadMore>
	);
}

function getReviewProductName(
	review: Review,
	reviewRatingId: string
): JSX.Element {
	return (
		<div className="wc-block-review-list-item__product wc-block-components-review-list-item__product">
			<a
				href={ review.product_permalink }
				aria-labelledby={ reviewRatingId }
			>
				{ decodeEntities( review.product_name ) }
			</a>
		</div>
	);
}

function getReviewerName( review: Review ): JSX.Element {
	const { reviewer = '' } = review;
	return (
		<div className="wc-block-review-list-item__author wc-block-components-review-list-item__author">
			{ reviewer }
		</div>
	);
}

function getReviewDate( review: Review ): JSX.Element {
	const {
		date_created: dateCreated,
		formatted_date_created: formattedDateCreated,
	} = review;
	return (
		<time
			className="wc-block-review-list-item__published-date wc-block-components-review-list-item__published-date"
			dateTime={ dateCreated }
		>
			{ formattedDateCreated }
		</time>
	);
}

function getReviewRating(
	review: Review,
	reviewRatingId: string
): JSX.Element {
	const { rating } = review;
	const starStyle = {
		width: ( rating / 5 ) * 100 + '%' /* stylelint-disable-line */,
	};
	const ratingText = sprintf(
		/* translators: %f is referring to the average rating value */
		__( 'Rated %f out of 5', 'woocommerce' ),
		rating
	);
	const ratingHTML = {
		__html: sprintf(
			/* translators: %s is referring to the average rating value */
			__( 'Rated %s out of 5', 'woocommerce' ),
			sprintf( '<strong class="rating">%f</strong>', rating )
		),
	};
	return (
		<div
			id={ reviewRatingId }
			aria-label={ `${ decodeEntities(
				review.product_name
			) } ${ ratingText }` }
			className="wc-block-review-list-item__rating wc-block-components-review-list-item__rating"
		>
			<div
				aria-hidden="true"
				className={ `wc-block-review-list-item__rating__stars wc-block-components-review-list-item__rating__stars wc-block-review-list-item__rating__stars--${ rating }` }
				role="img"
			>
				<span
					style={ starStyle }
					dangerouslySetInnerHTML={ ratingHTML }
				/>
			</div>
		</div>
	);
}

interface ReviewListItemProps {
	attributes: ReviewBlockAttributes;
	review?: Review;
}

const ReviewListItem = ( { attributes, review = {} }: ReviewListItemProps ) => {
	const {
		imageType,
		showReviewDate,
		showReviewerName,
		showReviewImage,
		showReviewRating: showReviewRatingAttr,
		showReviewContent,
		showProductName,
	} = attributes;
	const { rating } = review;
	const isLoading = ! ( Object.keys( review ).length > 0 );
	const showReviewRating = Number.isFinite( rating ) && showReviewRatingAttr;
	const reviewRatingId = useId();

	return (
		<li
			className={ clsx(
				'wc-block-review-list-item__item',
				'wc-block-components-review-list-item__item',
				{
					'is-loading': isLoading,
					'wc-block-components-review-list-item__item--has-image':
						showReviewImage,
				}
			) }
			aria-hidden={ isLoading }
		>
			{ ( showProductName ||
				showReviewDate ||
				showReviewerName ||
				showReviewImage ||
				showReviewRating ) && (
				<div className="wc-block-review-list-item__info wc-block-components-review-list-item__info">
					{ showReviewImage &&
						getReviewImage( review, imageType, isLoading ) }
					{ ( showProductName ||
						showReviewerName ||
						showReviewRating ||
						showReviewDate ) && (
						<div className="wc-block-review-list-item__meta wc-block-components-review-list-item__meta">
							{ showReviewRating &&
								getReviewRating( review, reviewRatingId ) }
							{ showProductName &&
								getReviewProductName( review, reviewRatingId ) }
							{ showReviewerName && getReviewerName( review ) }
							{ showReviewDate && getReviewDate( review ) }
						</div>
					) }
				</div>
			) }
			{ showReviewContent && getReviewContent( review ) }
		</li>
	);
};

/**
 * BE AWARE. ReviewListItem expects product data that is equivalent to what is
 * made available for output in a public view. Thus content that may contain
 * html data is not sanitized further.
 *
 * Currently the following data is trusted (assumed to already be sanitized):
 * - `review.review` (review content)
 * - `review.product_name` (the product title)
 */
export default ReviewListItem;
