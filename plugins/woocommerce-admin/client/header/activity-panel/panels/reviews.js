/** @format */
/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import classnames from 'classnames';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import Gridicon from 'gridicons';
import interpolateComponents from 'interpolate-components';
import { get, noop, isNull } from 'lodash';
import PropTypes from 'prop-types';
import { withDispatch } from '@wordpress/data';

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
	SplitButton,
} from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { ActivityCard, ActivityCardPlaceholder } from '../activity-card';
import ActivityHeader from '../activity-header';
import { DEFAULT_REVIEW_STATUSES, QUERY_DEFAULTS } from 'wc-api/constants';
import sanitizeHTML from 'lib/sanitize-html';
import withSelect from 'wc-api/with-select';

class ReviewsPanel extends Component {
	constructor() {
		super();

		this.mountTime = new Date().getTime();
	}

	componentWillUnmount() {
		const userDataFields = {
			[ 'activity_panel_reviews_last_read' ]: this.mountTime,
		};
		this.props.updateCurrentUserData( userDataFields );
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

		const cardActions = () => {
			const mainLabel =
				'approved' === review.status
					? __( 'Unapprove', 'woocommerce-admin' )
					: __( 'Approve', 'woocommerce-admin' );
			return (
				<SplitButton
					mainLabel={ mainLabel }
					menuLabel={ __( 'Select an action', 'woocommerce-admin' ) }
					onClick={ noop }
					controls={ [
						{
							label: __( 'Reply', 'woocommerce-admin' ),
							onClick: noop,
						},
						{
							label: __( 'Spam', 'woocommerce-admin' ),
							onClick: noop,
						},
						{
							label: __( 'Trash', 'woocommerce-admin' ),
							onClick: noop,
						},
					] }
				/>
			);
		};

		return (
			<ActivityCard
				className="woocommerce-review-activity-card"
				key={ review.id }
				title={ title }
				subtitle={ subtitle }
				date={ review.date_created_gmt }
				icon={ icon }
				actions={ cardActions() }
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

	renderPlaceholders() {
		const { numberOfReviews } = this.props;
		const placeholders = new Array( numberOfReviews );
		return placeholders
			.fill( 0 )
			.map( ( p, i ) => (
				<ActivityCardPlaceholder
					className="woocommerce-review-activity-card"
					key={ i }
					hasAction
					hasDate
					lines={ 2 }
				/>
			) );
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

		return (
			<Fragment>
				<ActivityHeader title={ __( 'Reviews', 'woocommerce-admin' ) } />
				<Section>
					{ isRequesting ? (
						this.renderPlaceholders()
					) : (
						<Fragment>
							{ reviews.map( review => this.renderReview( review, this.props ) ) }
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
	numberOfReviews: PropTypes.number,
};

ReviewsPanel.defaultProps = {
	reviews: [],
	isError: false,
	isRequesting: false,
	numberOfReviews: 0,
};

export default compose(
	withSelect( ( select, props ) => {
		const { numberOfReviews } = props;
		const { getCurrentUserData, getReviews, getReviewsError, isGetReviewsRequesting } = select(
			'wc-api'
		);
		if ( numberOfReviews === 0 ) {
			return {};
		}
		const userData = getCurrentUserData();
		const reviewsQuery = {
			page: 1,
			per_page: QUERY_DEFAULTS.pageSize,
			status: DEFAULT_REVIEW_STATUSES,
			_embed: 1,
		};

		const reviews = getReviews( reviewsQuery );
		const isError = Boolean( getReviewsError( reviewsQuery ) );
		const isRequesting = isGetReviewsRequesting( reviewsQuery );

		return { reviews, isError, isRequesting, lastRead: userData.activity_panel_reviews_last_read };
	} ),
	withDispatch( dispatch => {
		const { updateCurrentUserData } = dispatch( 'wc-api' );

		return {
			updateCurrentUserData,
		};
	} )
)( ReviewsPanel );
