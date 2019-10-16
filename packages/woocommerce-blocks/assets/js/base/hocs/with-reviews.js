/**
 * External dependencies
 */
import { Component } from 'react';
import PropTypes from 'prop-types';
import isShallowEqual from '@wordpress/is-shallow-equal';

/**
 * Internal dependencies
 */
import { getReviews } from '../../blocks/reviews/utils';

const withReviews = ( OriginalComponent ) => {
	class WrappedComponent extends Component {
		constructor() {
			super( ...arguments );

			this.state = {
				error: null,
				loading: true,
				reviews: [],
				totalReviews: 0,
			};

			this.setError = this.setError.bind( this );
			this.delayedAppendReviews = this.props.delayFunction( this.appendReviews );
		}

		componentDidMount() {
			this.replaceReviews();
		}

		componentDidUpdate( prevProps ) {
			if ( prevProps.reviewsToDisplay < this.props.reviewsToDisplay ) {
				// Since this attribute might be controlled via something with
				// short intervals between value changes, this allows for optionally
				// delaying review fetches via the provided delay function.
				this.delayedAppendReviews();
			} else if (
				this.shouldReplaceReviews( prevProps, this.props )
			) {
				this.replaceReviews();
			}
		}

		shouldReplaceReviews( prevProps, nextProps ) {
			return (
				prevProps.orderby !== nextProps.orderby ||
				prevProps.order !== nextProps.order ||
				prevProps.productId !== nextProps.productId ||
				! isShallowEqual( prevProps.categoryIds, nextProps.categoryIds )
			);
		}

		componentWillUnMount() {
			if ( this.delayedAppendReviews.cancel ) {
				this.delayedAppendReviews.cancel();
			}
		}

		getArgs( reviewsToSkip ) {
			const { categoryIds, order, orderby, productId, reviewsToDisplay } = this.props;
			const args = {
				order,
				orderby,
				per_page: reviewsToDisplay - reviewsToSkip,
				offset: reviewsToSkip,
			};

			if ( categoryIds && categoryIds.length ) {
				args.category_id = Array.isArray( categoryIds ) ? categoryIds.join( ',' ) : categoryIds;
			}

			if ( productId ) {
				args.product_id = productId;
			}

			return args;
		}

		replaceReviews() {
			const { onReviewsReplaced } = this.props;

			this.updateListOfReviews().then( onReviewsReplaced );
		}

		appendReviews() {
			const { onReviewsAppended, reviewsToDisplay } = this.props;
			const { reviews } = this.state;

			// Given that this function is delayed, props might have been updated since
			// it was called so we need to check again if fetching new reviews is necessary.
			if ( reviewsToDisplay <= reviews.length ) {
				return;
			}

			this.updateListOfReviews( reviews ).then( onReviewsAppended );
		}

		updateListOfReviews( oldReviews = [] ) {
			const { reviewsToDisplay } = this.props;
			const { totalReviews } = this.state;
			const reviewsToLoad = Math.min( totalReviews, reviewsToDisplay ) - oldReviews.length;

			this.setState( {
				loading: true,
				reviews: oldReviews.concat( Array( reviewsToLoad ).fill( {} ) ),
			} );

			return getReviews( this.getArgs( oldReviews.length ) )
				.then( ( { reviews: newReviews, totalReviews: newTotalReviews } ) => {
					this.setState( {
						reviews: oldReviews.filter( ( review ) => Object.keys( review ).length ).concat( newReviews ),
						totalReviews: newTotalReviews,
						loading: false,
						error: null,
					} );

					return { newReviews };
				} )
				.catch( this.setError );
		}

		setError( errorResponse ) {
			errorResponse.json().then( ( apiError ) => {
				const { onReviewsLoadError } = this.props;
				const error = typeof apiError === 'object' && apiError.hasOwnProperty( 'message' ) ? {
					apiMessage: apiError.message,
				} : {
					apiMessage: null,
				};

				this.setState( { reviews: [], loading: false, error } );

				onReviewsLoadError();
			} );
		}

		render() {
			const { reviewsToDisplay } = this.props;
			const { error, loading, reviews, totalReviews } = this.state;

			return <OriginalComponent
				{ ...this.props }
				error={ error }
				isLoading={ loading }
				reviews={ reviews.slice( 0, reviewsToDisplay ) }
				totalReviews={ totalReviews }
			/>;
		}
	}

	WrappedComponent.propTypes = {
		order: PropTypes.oneOf( [ 'asc', 'desc' ] ).isRequired,
		orderby: PropTypes.string.isRequired,
		reviewsToDisplay: PropTypes.number.isRequired,
		categoryIds: PropTypes.oneOfType( [ PropTypes.string, PropTypes.array ] ),
		delayFunction: PropTypes.func,
		onReviewsAppended: PropTypes.func,
		onReviewsLoadError: PropTypes.func,
		onReviewsReplaced: PropTypes.func,
		productId: PropTypes.oneOfType( [ PropTypes.string, PropTypes.number ] ),
	};

	WrappedComponent.defaultProps = {
		delayFunction: ( f ) => f,
		onReviewsAppended: () => {},
		onReviewsLoadError: () => {},
		onReviewsReplaced: () => {},
	};

	const { displayName = OriginalComponent.name || 'Component' } = OriginalComponent;
	WrappedComponent.displayName = `WithReviews( ${ displayName } )`;

	return WrappedComponent;
};

export default withReviews;
