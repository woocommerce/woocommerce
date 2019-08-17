/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { Component, Fragment } from 'react';
import PropTypes from 'prop-types';
import { speak } from '@wordpress/a11y';

/**
 * Internal dependencies
 */
import { getOrderArgs, getReviews } from './utils';
import LoadMoreButton from '../../base/components/load-more-button';
import ReviewOrderSelect from '../../base/components/review-order-select';
import ReviewList from '../../base/components/review-list';
import withComponentId from '../../base/hocs/with-component-id';
import { ENABLE_REVIEW_RATING } from '../../constants';

/**
 * Block rendered in the frontend.
 */
class FrontendBlock extends Component {
	constructor() {
		super( ...arguments );
		const { attributes } = this.props;

		this.state = {
			orderby: attributes.orderby,
			reviews: [],
			totalReviews: 0,
		};

		this.onChangeOrderby = this.onChangeOrderby.bind( this );
		this.appendReviews = this.appendReviews.bind( this );
	}

	componentDidMount() {
		this.loadFirstReviews();
	}

	getDefaultArgs() {
		const { attributes } = this.props;
		const { order, orderby } = getOrderArgs( this.state.orderby );
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
			this.setState( { reviews, totalReviews } );
		} ).catch( () => {
			this.setState( { reviews: [] } );
			speak(
				__( 'There was an error loading the reviews.', 'woo-gutenberg-products-block' )
			);
		} );
	}

	appendReviews() {
		const { attributes } = this.props;
		const { reviewsOnLoadMore } = attributes;
		const { reviews, totalReviews } = this.state;

		const reviewsToLoad = Math.min( totalReviews - reviews.length, reviewsOnLoadMore );
		this.setState( { reviews: reviews.concat( Array( reviewsToLoad ).fill( {} ) ) } );

		const args = {
			...this.getDefaultArgs(),
			offset: reviews.length,
			per_page: reviewsOnLoadMore,
		};
		getReviews( args ).then( ( { reviews: newReviews, totalReviews: newTotalReviews } ) => {
			this.setState( {
				reviews: reviews.filter( ( review ) => Object.keys( review ).length ).concat( newReviews ),
				totalReviews: newTotalReviews,
			} );
			speak(
				sprintf(
					_n(
						'%d review loaded.',
						'%d reviews loaded.',
						'woo-gutenberg-products-block'
					),
					newReviews.length
				)
			);
		} ).catch( () => {
			this.setState( { reviews: [] } );
			speak(
				__( 'There was an error loading the reviews.', 'woo-gutenberg-products-block' )
			);
		} );
	}

	onChangeOrderby( event ) {
		const { attributes } = this.props;
		const { reviewsOnPageLoad } = attributes;
		const { totalReviews } = this.state;
		const { order, orderby } = getOrderArgs( event.target.value );
		const newReviews = Math.min( totalReviews, reviewsOnPageLoad );

		this.setState( {
			reviews: Array( newReviews ).fill( {} ),
			orderby: event.target.value,
		} );

		const args = {
			...this.getDefaultArgs(),
			order,
			orderby,
			per_page: reviewsOnPageLoad,
		};
		getReviews( args ).then( ( { reviews, totalReviews: newTotalReviews } ) => {
			this.setState( { reviews, totalReviews: newTotalReviews } );
			speak( __( 'Reviews order updated.', 'woo-gutenberg-products-block' ) );
		} ).catch( () => {
			this.setState( { reviews: [] } );
			speak(
				__( 'There was an error loading the reviews.', 'woo-gutenberg-products-block' )
			);
		} );
	}

	render() {
		const { attributes, componentId } = this.props;
		const { orderby, reviews, totalReviews } = this.state;

		return (
			<Fragment>
				{ ( attributes.showOrderby && ENABLE_REVIEW_RATING ) && (
					<ReviewOrderSelect
						componentId={ componentId }
						onChange={ this.onChangeOrderby }
						value={ orderby }
					/>
				) }
				<ReviewList
					attributes={ attributes }
					componentId={ componentId }
					reviews={ reviews }
				/>
				{ ( attributes.showLoadMore && totalReviews > reviews.length ) && (
					<LoadMoreButton
						onClick={ this.appendReviews }
						screenReaderLabel={ __( 'Load more reviews', 'woo-gutenberg-products-block' ) }
					/>
				) }
			</Fragment>
		);
	}
}

FrontendBlock.propTypes = {
	/**
	 * The attributes for this block.
	 */
	attributes: PropTypes.object.isRequired,
	// from withComponentId
	componentId: PropTypes.number,
};

export default withComponentId( FrontendBlock );
