/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { speak } from '@wordpress/a11y';
import { Component } from '@wordpress/element';
import { Review } from '@woocommerce/base-components/reviews/types';

/**
 * Internal dependencies
 */
import { getSortArgs } from './utils';
import FrontendBlock from './frontend-block';
import { ReviewBlockAttributes } from './attributes';

type FrontendContainerBlockProps = {
	attributes: ReviewBlockAttributes;
};

/**
 * Container of the block rendered in the frontend.
 */
class FrontendContainerBlock extends Component<
	FrontendContainerBlockProps,
	{ orderby: string; reviewsToDisplay: number }
> {
	constructor( props: FrontendContainerBlockProps ) {
		super( props );
		const { attributes } = this.props;

		this.state = {
			orderby: attributes?.orderby,
			reviewsToDisplay: this.getReviewsOnPageLoad(),
		};

		this.onAppendReviews = this.onAppendReviews.bind( this );
		this.onChangeOrderby = this.onChangeOrderby.bind( this );
	}

	getReviewsOnPageLoad() {
		const { attributes } = this.props;

		return typeof attributes.reviewsOnPageLoad === 'number'
			? attributes.reviewsOnPageLoad
			: parseInt( attributes.reviewsOnPageLoad, 10 );
	}

	getReviewsOnLoadMore() {
		const { attributes } = this.props;

		return typeof attributes.reviewsOnLoadMore === 'number'
			? attributes.reviewsOnLoadMore
			: parseInt( attributes.reviewsOnLoadMore, 10 );
	}

	onAppendReviews() {
		const { reviewsToDisplay } = this.state;

		this.setState( {
			reviewsToDisplay: reviewsToDisplay + this.getReviewsOnLoadMore(),
		} );
	}

	onChangeOrderby( event: React.ChangeEvent< HTMLSelectElement > ) {
		this.setState( {
			orderby: event.target.value,
			reviewsToDisplay: this.getReviewsOnPageLoad(),
		} );
	}

	onReviewsAppended( { newReviews }: { newReviews: Review[] } ) {
		speak(
			sprintf(
				/* translators: %d is the count of reviews loaded. */
				_n(
					'%d review loaded.',
					'%d reviews loaded.',
					newReviews.length,
					'woo-gutenberg-products-block'
				),
				newReviews.length
			)
		);
	}

	onReviewsReplaced() {
		speak( __( 'Reviews list updated.', 'woo-gutenberg-products-block' ) );
	}

	onReviewsLoadError() {
		speak(
			__(
				'There was an error loading the reviews.',
				'woo-gutenberg-products-block'
			)
		);
	}

	render() {
		const { attributes } = this.props;
		const { categoryIds, productId } = attributes;
		const { reviewsToDisplay } = this.state;
		const { order, orderby } = getSortArgs( this.state.orderby );

		return (
			// @ts-expect-error - TODO: Refactor WrappedComponent
			<FrontendBlock
				attributes={ attributes }
				categoryIds={ categoryIds }
				onAppendReviews={ this.onAppendReviews }
				onChangeOrderby={ this.onChangeOrderby }
				onReviewsAppended={ this.onReviewsAppended }
				onReviewsLoadError={ this.onReviewsLoadError }
				onReviewsReplaced={ this.onReviewsReplaced }
				order={ order }
				orderby={ orderby }
				productId={ productId }
				reviewsToDisplay={ reviewsToDisplay }
				sortSelectValue={ this.state.orderby }
			/>
		);
	}
}

export default FrontendContainerBlock;
