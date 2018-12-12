/** @format */
/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import Gridicon from 'gridicons';
import interpolateComponents from 'interpolate-components';
import { noop, isNull } from 'lodash';
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
	SplitButton,
} from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { ActivityCard, ActivityCardPlaceholder } from '../activity-card';
import ActivityHeader from '../activity-header';
import { QUERY_DEFAULTS } from 'store/constants';
import sanitizeHTML from 'lib/sanitize-html';
import withSelect from 'wc-api/with-select';

class ReviewsPanel extends Component {
	renderReview( review ) {
		const product =
			( review && review._embedded && review._embedded.up && review._embedded.up[ 0 ] ) || null;

		if ( isNull( product ) ) {
			return null;
		}

		const title = interpolateComponents( {
			mixedString: sprintf(
				__(
					'{{productLink}}%s{{/productLink}} reviewed by {{authorLink}}%s{{/authorLink}}',
					'wc-admin'
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
						{ __( 'Verified customer', 'wc-admin' ) }
					</span>
				) }
			</Fragment>
		);

		const icon = (
			<div className="woocommerce-review-activity-card__image-overlay">
				<Gravatar user={ review.reviewer_email } size={ 24 } />
				<ProductImage product={ product } />
			</div>
		);

		const cardActions = () => {
			const mainLabel =
				'approved' === review.status ? __( 'Unapprove', 'wc-admin' ) : __( 'Approve', 'wc-admin' );
			return (
				<SplitButton
					mainLabel={ mainLabel }
					menuLabel={ __( 'Select an action', 'wc-admin' ) }
					onClick={ noop }
					controls={ [
						{
							label: __( 'Reply', 'wc-admin' ),
							onClick: noop,
						},
						{
							label: __( 'Spam', 'wc-admin' ),
							onClick: noop,
						},
						{
							label: __( 'Trash', 'wc-admin' ),
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
				date={ review.date_created }
				icon={ icon }
				actions={ cardActions() }
			>
				<span dangerouslySetInnerHTML={ sanitizeHTML( review.review ) } />
			</ActivityCard>
		);
	}

	render() {
		const { isError, isRequesting, reviews } = this.props;

		if ( isError ) {
			const title = __( 'There was an error getting your reviews. Please try again.', 'wc-admin' );
			const actionLabel = __( 'Reload', 'wc-admin' );
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
				<ActivityHeader title={ __( 'Reviews', 'wc-admin' ) } />
				<Section>
					{ isRequesting ? (
						<ActivityCardPlaceholder
							className="woocommerce-review-activity-card"
							hasAction
							hasDate
							lines={ 2 }
						/>
					) : (
						<Fragment>{ reviews.map( this.renderReview ) }</Fragment>
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

export default compose(
	withSelect( select => {
		const { getReviews, isGetReviewsError, isGetReviewsRequesting } = select( 'wc-api' );
		const reviewsQuery = {
			page: 1,
			per_page: QUERY_DEFAULTS.pageSize,
			_embed: 1,
		};

		const reviews = getReviews( reviewsQuery );
		const isError = isGetReviewsError( reviewsQuery );
		const isRequesting = isGetReviewsRequesting( reviewsQuery );

		return { reviews, isError, isRequesting };
	} )
)( ReviewsPanel );
