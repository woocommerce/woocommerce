/** @format */
/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import classnames from 'classnames';
import { Component, Fragment } from '@wordpress/element';
import { Button } from '@wordpress/components';
import Gridicon from 'gridicons';
import interpolateComponents from 'interpolate-components';
import { get, isNull } from 'lodash';
import PropTypes from 'prop-types';

/**
 * WooCommerce dependencies
 */
import {
	EmptyContent,
	Gravatar,
	Link,
	ProductImage,
	ReviewRating,
	Section,
} from '@woocommerce/components';
import { getAdminLink } from '@woocommerce/wc-admin-settings';

/**
 * Internal dependencies
 */
import { ActivityCard, ActivityCardPlaceholder } from '../activity-card';
import ActivityHeader from '../activity-header';
import { QUERY_DEFAULTS } from 'wc-api/constants';
import sanitizeHTML from 'lib/sanitize-html';
import withSelect from 'wc-api/with-select';
import { recordEvent } from 'lib/tracks';

class ReviewsPanel extends Component {
	constructor() {
		super();

		this.mountTime = new Date().getTime();
	}

	renderReview( review, props ) {
		const { lastRead } = props;
		const product =
			( review && review._embedded && review._embedded.up && review._embedded.up[ 0 ] ) || null;

		if ( isNull( product ) ) {
			return null;
		}

		const title = interpolateComponents( {
			mixedString: sprintf(
				__(
					'{{productLink}}%s{{/productLink}} reviewed by {{authorLink}}%s{{/authorLink}}',
					'woocommerce-admin'
				),
				product.name,
				review.reviewer
			),
			components: {
				productLink: <Link href={ product.permalink } type="external" />,
				authorLink: <Link href={ 'mailto:' + review.reviewer_email } type="external" />,
			},
		} );

		const subtitle = (
			<Fragment>
				<ReviewRating review={ review } />
				{ review.verified && (
					<span className="woocommerce-review-activity-card__verified">
						<Gridicon icon="checkmark" size={ 18 } />
						{ __( 'Verified customer', 'woocommerce-admin' ) }
					</span>
				) }
			</Fragment>
		);

		const productImage = get( product, [ 'images', 0 ] ) || get( product, [ 'image' ] );
		const productImageClasses = classnames(
			'woocommerce-review-activity-card__image-overlay__product',
			{
				'is-placeholder': ! productImage || ! productImage.src,
			}
		);
		const icon = (
			<div className="woocommerce-review-activity-card__image-overlay">
				<Gravatar user={ review.reviewer_email } size={ 24 } />
				<div className={ productImageClasses }>
					<ProductImage product={ product } />
				</div>
			</div>
		);

		const manageReviewEvent = {
			date: review.date_created_gmt,
			status: review.status,
		};

		const cardActions = (
			<Button
				isDefault
				onClick={ () => recordEvent( 'review_manage_click', manageReviewEvent ) }
				href={ getAdminLink( 'comment.php?action=editcomment&c=' + review.id ) }
			>
				{ __( 'Manage', 'woocommerce-admin' ) }
			</Button>
		);

		return (
			<ActivityCard
				className="woocommerce-review-activity-card"
				key={ review.id }
				title={ title }
				subtitle={ subtitle }
				date={ review.date_created_gmt }
				icon={ icon }
				actions={ cardActions }
				unread={
					review.status === 'hold' ||
					! lastRead ||
					! review.date_created_gmt ||
					new Date( review.date_created_gmt + 'Z' ).getTime() > lastRead
				}
			>
				<span dangerouslySetInnerHTML={ sanitizeHTML( review.review ) } />
			</ActivityCard>
		);
	}

	renderEmptyMessage() {
		const { lastApprovedReviewTime } = this.props;

		const title = __( 'You have no reviews to moderate', 'woocommerce-admin' );
		let buttonUrl = '';
		let buttonTarget = '';
		let buttonText = '';
		let content = '';

		if ( lastApprovedReviewTime ) {
			const now = new Date();
			const DAY = 24 * 60 * 60 * 1000;
			if ( ( now.getTime() - lastApprovedReviewTime ) / DAY > 30 ) {
				buttonUrl = 'https://woocommerce.com/posts/reviews-woocommerce-best-practices/';
				buttonTarget = '_blank';
				buttonText = __( 'Learn more', 'woocommerce-admin' );
				content = (
					<Fragment>
						<p>
							{ __(
								"We noticed that it's been a while since your products had any reviews.",
								'woocommerce-admin'
							) }
						</p>
						<p>
							{ __(
								'Take some time to learn about best practices for collecting and using your reviews.',
								'woocommerce-admin'
							) }
						</p>
					</Fragment>
				);
			} else {
				buttonUrl = getAdminLink( 'edit-comments.php?comment_type=review' );
				buttonText = __( 'View all Reviews', 'woocommerce-admin' );
				content = (
					<p>
						{ __(
							/* eslint-disable max-len */
							"Awesome, you've moderated all of your product reviews. How about responding to some of those negative reviews?",
							'woocommerce-admin'
							/* eslint-enable */
						) }
					</p>
				);
			}
		} else {
			buttonUrl = 'https://woocommerce.com/posts/reviews-woocommerce-best-practices/';
			buttonTarget = '_blank';
			buttonText = __( 'Learn more', 'woocommerce-admin' );
			content = (
				<Fragment>
					<p>
						{ __( "Your customers haven't started reviewing your products.", 'woocommerce-admin' ) }
					</p>
					<p>
						{ __(
							'Take some time to learn about best practices for collecting and using your reviews.',
							'woocommerce-admin'
						) }
					</p>
				</Fragment>
			);
		}

		return (
			<ActivityCard
				className="woocommerce-empty-activity-card"
				title={ title }
				icon={ <Gridicon icon="time" size={ 48 } /> }
				actions={
					<Button href={ buttonUrl } target={ buttonTarget } isDefault>
						{ buttonText }
					</Button>
				}
			>
				{ content }
			</ActivityCard>
		);
	}

	render() {
		const { isError, isRequesting, reviews } = this.props;

		if ( isError ) {
			const title = __(
				'There was an error getting your reviews. Please try again.',
				'woocommerce-admin'
			);
			const actionLabel = __( 'Reload', 'woocommerce-admin' );
			const actionCallback = () => {
				window.location.reload();
			};

			return (
				<Fragment>
					<EmptyContent
						title={ title }
						actionLabel={ actionLabel }
						actionURL={ null }
						actionCallback={ actionCallback }
					/>
				</Fragment>
			);
		}

		const title =
			isRequesting || reviews.length
				? __( 'Reviews', 'woocommerce-admin' )
				: __( 'No reviews to moderate', 'woocommerce-admin' );

		return (
			<Fragment>
				<ActivityHeader title={ title } />
				<Section>
					{ isRequesting ? (
						<ActivityCardPlaceholder
							className="woocommerce-review-activity-card"
							hasAction
							hasDate
							lines={ 2 }
						/>
					) : (
						<Fragment>
							{ reviews.length
								? reviews.map( review => this.renderReview( review, this.props ) )
								: this.renderEmptyMessage() }
						</Fragment>
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

export default withSelect( ( select, props ) => {
	const { hasUnapprovedReviews } = props;
	const { getReviews, getReviewsError, isGetReviewsRequesting } = select( 'wc-api' );
	let reviews = [];
	let isError = false;
	let isRequesting = false;
	let lastApprovedReviewTime = null;
	if ( hasUnapprovedReviews ) {
		const reviewsQuery = {
			page: 1,
			per_page: QUERY_DEFAULTS.pageSize,
			status: 'hold',
			_embed: 1,
		};
		reviews = getReviews( reviewsQuery );
		isError = Boolean( getReviewsError( reviewsQuery ) );
		isRequesting = isGetReviewsRequesting( reviewsQuery );
	} else {
		const approvedReviewsQuery = {
			page: 1,
			per_page: 1,
			status: 'approved',
			_embed: 1,
		};
		const approvedReviews = getReviews( approvedReviewsQuery );
		if ( approvedReviews.length ) {
			const lastApprovedReview = approvedReviews[ 0 ];
			if ( lastApprovedReview.date_created_gmt ) {
				const creationDate = new Date( lastApprovedReview.date_created_gmt );
				lastApprovedReviewTime = creationDate.getTime();
			}
		}

		isError = Boolean( getReviewsError( approvedReviewsQuery ) );
		isRequesting = isGetReviewsRequesting( approvedReviewsQuery );
	}

	return {
		reviews,
		isError,
		isRequesting,
		lastApprovedReviewTime,
	};
} )( ReviewsPanel );
