/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import ReadMore from '@woocommerce/base-components/read-more';

/**
 * Internal dependencies
 */
import './style.scss';

function getReviewImage( review, imageType, isLoading ) {
	if ( isLoading || ! review ) {
		return (
			<div
				className="wc-block-review-list-item__image wc-block-components-review-list-item__image"
				width="48"
				height="48"
			/>
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
				<img
					aria-hidden="true"
					alt=""
					src={ review.reviewer_avatar_urls[ '48' ] || '' }
					srcSet={ review.reviewer_avatar_urls[ '96' ] + ' 2x' }
				/>
			) }
			{ review.verified && (
				<div
					className="wc-block-review-list-item__verified wc-block-components-review-list-item__verified"
					title={ __(
						'Verified buyer',
						'woocommerce'
					) }
				>
					{ __( 'Verified buyer', 'woocommerce' ) }
				</div>
			) }
		</div>
	);
}

function getReviewContent( review ) {
	return (
		<ReadMore
			maxLines={ 10 }
			moreText={ __(
				'Read full review',
				'woocommerce'
			) }
			lessText={ __(
				'Hide full review',
				'woocommerce'
			) }
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

function getReviewProductName( review ) {
	return (
		<div className="wc-block-review-list-item__product wc-block-components-review-list-item__product">
			<a
				href={ review.product_permalink }
				dangerouslySetInnerHTML={ {
					// `product_name` might have html entities for things like
					// emdash. So to display properly we need to allow the
					// browser to render.
					__html: review.product_name,
				} }
			/>
		</div>
	);
}

function getReviewerName( review ) {
	const { reviewer = '' } = review;
	return (
		<div className="wc-block-review-list-item__author wc-block-components-review-list-item__author">
			{ reviewer }
		</div>
	);
}

function getReviewDate( review ) {
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

function getReviewRating( review ) {
	const { rating } = review;
	const starStyle = {
		width: ( rating / 5 ) * 100 + '%' /* stylelint-disable-line */,
	};
	return (
		<div className="wc-block-review-list-item__rating wc-block-components-review-list-item__rating">
			<div
				className="wc-block-review-list-item__rating__stars wc-block-components-review-list-item__rating__stars"
				role="img"
			>
				<span style={ starStyle }>
					{ sprintf(
						/* Translators: %f to the average rating for the review. */
						__(
							'Rated %f out of 5',
							'woocommerce'
						),
						rating
					) }
				</span>
			</div>
		</div>
	);
}

const ReviewListItem = ( { attributes, review = {} } ) => {
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
	const isLoading = ! Object.keys( review ).length > 0;
	const showReviewRating = Number.isFinite( rating ) && showReviewRatingAttr;

	return (
		<li
			className={ classNames(
				'wc-block-review-list-item__item',
				'wc-block-components-review-list-item__item',
				{
					'is-loading': isLoading,
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
							{ showReviewRating && getReviewRating( review ) }
							{ showProductName &&
								getReviewProductName( review ) }
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

ReviewListItem.propTypes = {
	attributes: PropTypes.object.isRequired,
	review: PropTypes.object,
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
