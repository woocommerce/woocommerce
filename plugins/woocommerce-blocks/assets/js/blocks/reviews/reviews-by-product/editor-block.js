/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component } from 'react';
import PropTypes from 'prop-types';
import { debounce } from 'lodash';
import { Disabled } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { getOrderArgs, getReviews } from '../utils';
import LoadMoreButton from '../../../base/components/load-more-button';
import ReviewList from '../../../base/components/review-list';
import ReviewOrderSelect from '../../../base/components/review-order-select';
import withComponentId from '../../../base/hocs/with-component-id';
import NoReviewsPlaceholder from './no-reviews-placeholder.js';
import { ENABLE_REVIEW_RATING } from '../../../constants';
/**
 * Block rendered in the editor.
 */
class EditorBlock extends Component {
	constructor() {
		super( ...arguments );

		this.state = {
			reviews: [],
			totalReviews: 0,
			isLoading: true,
		};

		this.debouncedLoadFirstReviews = debounce( this.loadFirstReviews.bind( this ), 400 );
	}

	componentDidMount() {
		this.loadFirstReviews();
	}

	componentDidUpdate( prevProps ) {
		if (
			prevProps.attributes.orderby !== this.props.attributes.orderby ||
			prevProps.attributes.productId !== this.props.attributes.productId ||
			prevProps.attributes.reviewsOnPageLoad !== this.props.attributes.reviewsOnPageLoad
		) {
			this.debouncedLoadFirstReviews();
		}
	}

	getDefaultArgs() {
		const { attributes } = this.props;
		const { order, orderby } = getOrderArgs( attributes.orderby );
		const { productId, reviewsOnPageLoad } = attributes;

		return {
			order,
			orderby,
			per_page: reviewsOnPageLoad,
			product_id: productId,
		};
	}

	loadFirstReviews() {
		getReviews( this.getDefaultArgs() ).then( ( { reviews, totalReviews } ) => {
			this.setState( { reviews, totalReviews, isLoading: false } );
		} ).catch( () => {
			this.setState( { reviews: [], isLoading: false } );
		} );
	}

	render() {
		const { attributes, componentId } = this.props;
		const { reviews, totalReviews, isLoading } = this.state;

		if ( 0 === reviews.length && ! isLoading ) {
			return <NoReviewsPlaceholder attributes={ attributes } />;
		}

		return (
			<Disabled>
				{ ( attributes.showOrderby && ENABLE_REVIEW_RATING ) && (
					<ReviewOrderSelect
						componentId={ componentId }
						readOnly
						value={ attributes.orderby }
					/>
				) }
				<ReviewList
					attributes={ attributes }
					componentId={ componentId }
					reviews={ reviews }
				/>
				{ ( attributes.showLoadMore && totalReviews > reviews.length ) && (
					<LoadMoreButton
						screenReaderLabel={ __( 'Load more reviews', 'woo-gutenberg-products-block' ) }
					/>
				) }
			</Disabled>
		);
	}
}

EditorBlock.propTypes = {
	/**
	 * The attributes for this block.
	 */
	attributes: PropTypes.object.isRequired,
	// from withComponentId
	componentId: PropTypes.number,
};

export default withComponentId( EditorBlock );
