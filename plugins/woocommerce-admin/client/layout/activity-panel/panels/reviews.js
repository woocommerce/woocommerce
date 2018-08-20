/** @format */
/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import Gridicon from 'gridicons';
import interpolateComponents from 'interpolate-components';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import { ActivityCard, ActivityCardPlaceholder } from '../activity-card';
import ActivityHeader from '../activity-header';
import { Gravatar, Link, ProductImage, ReviewRating, SplitButton } from '@woocommerce/components';
import { Section } from 'layout/section';

// TODO Pull proper data from the API
const demoReviews = [
	{
		id: 1,
		product_id: 1,
		reviewer: 'Justin Shreve',
		reviewer_email: 'justin@automattic.com',
		rating: 3,
		verified: true,
		review:
			'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Quisque finibus hendrerit finibus.' +
			'Integer tristique turpis a aliquam aliquam. Phasellus sapien lectus, sodales in sagittis nec, placerat a augue.',
		status: 'pending',
		date_created: '2018-07-10T02:49:00Z',
	},
];

const demoProducts = [
	{
		id: 1,
		name: 'WordPress Shirt',
		permalink: '#',
		images: [
			{
				id: 1,
				src: 'https://cldup.com/5QyaPgyfFo-3000x3000.png',
			},
		],
	},
];

class ReviewsPanel extends Component {
	constructor( props ) {
		super( props );
		this.state = {
			loading: true,
			reviews: [],
		};
	}

	componentDidMount() {
		this.interval = setTimeout( () => {
			this.setState( {
				loading: false,
				reviews: demoReviews,
			} );
		}, 5000 );
	}

	componentWillUnmount() {
		clearTimeout( this.interval );
	}

	renderReview( review ) {
		const product = demoProducts[ 0 ];

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
				{ review.review }
			</ActivityCard>
		);
	}

	render() {
		const { loading = true, reviews = [] } = this.state;
		return (
			<Fragment>
				<ActivityHeader title={ __( 'Reviews', 'wc-admin' ) } />
				<Section>
					{ loading ? (
						<ActivityCardPlaceholder hasAction hasSubtitle hasDate lines={ 2 } />
					) : (
						reviews.map( this.renderReview )
					) }
				</Section>
			</Fragment>
		);
	}
}

export default ReviewsPanel;
