/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import clsx from 'clsx';
import { Component, Fragment } from '@wordpress/element';
import { Button, Tooltip } from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { withSelect, withDispatch } from '@wordpress/data';
import PropTypes from 'prop-types';
import StarIcon from 'gridicons/dist/star';
import StarOutlineIcon from 'gridicons/dist/star-outline';
import interpolateComponents from '@automattic/interpolate-components';
import {
	Link,
	ReviewRating,
	ProductImage,
	Section,
} from '@woocommerce/components';
import { getAdminLink } from '@woocommerce/settings';
import { get, isNull } from 'lodash';
import { REVIEWS_STORE_NAME } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { CurrencyContext } from '@woocommerce/currency';

/**
 * Internal dependencies
 */
import './style.scss';
import {
	ActivityCard,
	ActivityCardPlaceholder,
} from '~/activity-panel/activity-card';
import CheckmarkCircleIcon from './checkmark-circle-icon';
import sanitizeHTML from '../../../lib/sanitize-html';
import { REVIEW_PAGE_LIMIT, unapprovedReviewsQuery } from './utils';

const reviewsQuery = {
	page: 1,
	per_page: REVIEW_PAGE_LIMIT,
	status: 'hold',
	_embed: 1,
};

class ReviewsPanel extends Component {
	recordReviewEvent( eventName, eventData ) {
		recordEvent( `reviews_${ eventName }`, eventData || {} );
	}

	deleteReview( reviewId ) {
		const { deleteReview, createNotice, updateReview, clearReviewsCache } =
			this.props;
		if ( reviewId ) {
			deleteReview( reviewId )
				.then( () => {
					clearReviewsCache();
					createNotice(
						'success',
						__( 'Review successfully deleted.', 'woocommerce' ),
						{
							actions: [
								{
									label: __( 'Undo', 'woocommerce' ),
									onClick: () => {
										updateReview(
											reviewId,
											{
												status: 'untrash',
											},
											{
												_embed: 1,
											}
										).then( () => clearReviewsCache() );
									},
								},
							],
						}
					);
				} )
				.catch( () => {
					createNotice(
						'error',
						__( 'Review could not be deleted.', 'woocommerce' )
					);
				} );
		}
	}

	updateReviewStatus( reviewId, newStatus, oldStatus ) {
		const { createNotice, updateReview, clearReviewsCache } = this.props;
		if ( reviewId ) {
			updateReview( reviewId, { status: newStatus } )
				.then( () => {
					clearReviewsCache();
					createNotice(
						'success',
						__( 'Review successfully updated.', 'woocommerce' ),
						{
							actions: [
								{
									label: __( 'Undo', 'woocommerce' ),
									onClick: () => {
										updateReview(
											reviewId,
											{
												status: oldStatus,
											},
											{
												_embed: 1,
											}
										).then( () => clearReviewsCache() );
									},
								},
							],
						}
					);
				} )
				.catch( () => {
					createNotice(
						'error',
						__( 'Review could not be updated.', 'woocommerce' )
					);
				} );
		}
	}

	renderReview( review ) {
		const product =
			( review &&
				review._embedded &&
				review._embedded.up &&
				review._embedded.up[ 0 ] ) ||
			null;

		if ( review.isUpdating ) {
			return (
				<ActivityCardPlaceholder
					key={ review.id }
					className="woocommerce-review-activity-card"
					hasAction
					hasDate
					lines={ 1 }
				/>
			);
		}
		if ( isNull( product ) || review.status !== reviewsQuery.status ) {
			return null;
		}

		const title = interpolateComponents( {
			mixedString: sprintf(
				/* translators: product reviewer as author, and product name  */
				__(
					'{{authorLink}}%1$s{{/authorLink}}{{verifiedCustomerIcon/}} reviewed {{productLink}}%2$s{{/productLink}}',
					'woocommerce'
				),
				review.reviewer,
				product.name
			),
			components: {
				productLink: (
					<Link
						href={ product.permalink }
						onClick={ () => this.recordReviewEvent( 'product' ) }
						type="external"
					/>
				),
				authorLink: (
					<Link
						href={ getAdminLink(
							'admin.php?page=wc-admin&path=%2Fcustomers&search=' +
								review.reviewer
						) }
						onClick={ () => this.recordReviewEvent( 'customer' ) }
						type="external"
					/>
				),
				verifiedCustomerIcon: review.verified ? (
					<span className="woocommerce-review-activity-card__verified">
						<Tooltip text={ __( 'Verified owner', 'woocommerce' ) }>
							<span>
								<CheckmarkCircleIcon />
							</span>
						</Tooltip>
					</span>
				) : null,
			},
		} );

		const subtitle = (
			<Fragment>
				<ReviewRating
					review={ review }
					icon={ StarOutlineIcon }
					outlineIcon={ StarIcon }
					size={ 13 }
				/>
			</Fragment>
		);

		const productImage =
			get( product, [ 'images', 0 ] ) || get( product, [ 'image' ] );
		const productImageClasses = clsx(
			'woocommerce-review-activity-card__image-overlay__product',
			{
				'is-placeholder': ! productImage || ! productImage.src,
			}
		);
		const icon = (
			<div className="woocommerce-review-activity-card__image-overlay">
				<div className={ productImageClasses }>
					<ProductImage
						product={ product }
						width={ 33 }
						height={ 33 }
					/>
				</div>
			</div>
		);

		const manageReviewEvent = {
			date: review.date_created_gmt,
			status: review.status,
		};

		const cardActions = [
			<Button
				key="approve-action"
				isSecondary
				onClick={ () => {
					this.recordReviewEvent( 'approve', manageReviewEvent );
					this.updateReviewStatus(
						review.id,
						'approved',
						review.status
					);
				} }
			>
				{ __( 'Approve', 'woocommerce' ) }
			</Button>,
			<Button
				key="spam-action"
				isTertiary
				onClick={ () => {
					this.recordReviewEvent( 'mark_as_spam', manageReviewEvent );
					this.updateReviewStatus( review.id, 'spam', review.status );
				} }
			>
				{ __( 'Mark as spam', 'woocommerce' ) }
			</Button>,
			<Button
				key="delete-action"
				isDestructive
				isTertiary
				onClick={ () => {
					this.recordReviewEvent( 'delete', manageReviewEvent );
					this.deleteReview( review.id );
				} }
			>
				{ __( 'Delete', 'woocommerce' ) }
			</Button>,
		];

		return (
			<ActivityCard
				className="woocommerce-review-activity-card"
				key={ review.id }
				title={ title }
				subtitle={ subtitle }
				date={ review.date_created_gmt }
				icon={ icon }
				actions={ cardActions }
			>
				<span
					dangerouslySetInnerHTML={ sanitizeHTML( review.review ) }
				/>
			</ActivityCard>
		);
	}

	renderReviews( reviews ) {
		const renderedReviews = reviews.map( ( review ) =>
			this.renderReview( review, this.props )
		);
		if ( renderedReviews.filter( Boolean ).length === 0 ) {
			return <></>;
		}
		return (
			<>
				{ renderedReviews }
				<Link
					href={ getAdminLink(
						'edit.php?post_type=product&page=product-reviews'
					) }
					onClick={ () => this.recordReviewEvent( 'reviews_manage' ) }
					className="woocommerce-layout__activity-panel-outbound-link woocommerce-layout__activity-panel-empty"
					type="wp-admin"
				>
					{ __( 'Manage all reviews', 'woocommerce' ) }
				</Link>
			</>
		);
	}

	render() {
		const { isRequesting, isError, reviews } = this.props;

		if ( isError ) {
			throw new Error(
				'Failed to load reviews, Raise error to trigger ErrorBoundary'
			);
		}

		return (
			<Fragment>
				<Section>
					{ isRequesting || ! reviews.length ? (
						<ActivityCardPlaceholder
							className="woocommerce-review-activity-card"
							hasAction
							hasDate
							lines={ 1 }
						/>
					) : (
						<>{ this.renderReviews( reviews ) }</>
					) }
				</Section>
			</Fragment>
		);
	}
}

ReviewsPanel.propTypes = {
	reviews: PropTypes.array.isRequired,
	isError: PropTypes.bool,
	isRequesting: PropTypes.bool,
};

ReviewsPanel.defaultProps = {
	reviews: [],
	isError: false,
	isRequesting: false,
};

ReviewsPanel.contextType = CurrencyContext;

export { ReviewsPanel };

export default compose( [
	withSelect( ( select, props ) => {
		const { hasUnapprovedReviews } = props;
		const { getReviews, getReviewsError, isResolving } =
			select( REVIEWS_STORE_NAME );
		let reviews = [];
		let isError = false;
		let isRequesting = false;
		if ( hasUnapprovedReviews ) {
			reviews = getReviews( reviewsQuery );
			isError = Boolean( getReviewsError( reviewsQuery ) );
			isRequesting = isResolving( 'getReviews', [ reviewsQuery ] );
		}

		return {
			reviews,
			isError,
			isRequesting,
		};
	} ),
	withDispatch( ( dispatch, props ) => {
		const { deleteReview, updateReview, invalidateResolution } =
			dispatch( REVIEWS_STORE_NAME );
		const { createNotice } = dispatch( 'core/notices' );

		const clearReviewsCache = () => {
			invalidateResolution( 'getReviews', [ reviewsQuery ] );
			if ( props.reviews && props.reviews.length < 2 ) {
				invalidateResolution( 'getReviewsTotalCount', [
					unapprovedReviewsQuery,
				] );
			}
		};

		return {
			deleteReview,
			createNotice,
			updateReview,
			clearReviewsCache,
		};
	} ),
] )( ReviewsPanel );
