/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import isShallowEqual from '@wordpress/is-shallow-equal';
import type { Review } from '@woocommerce/base-components/reviews/types';
import { ErrorObject } from '@woocommerce/editor-components/error-placeholder';

/**
 * Internal dependencies
 */
import { getReviews } from '../../blocks/reviews/utils';
import { formatError } from '../utils/errors';

interface WithReviewsProps {
	order: 'asc' | 'desc';
	orderby: string;
	reviewsToDisplay: number;
	categoryIds?: string | string[];
	delayFunction?: ( f: () => void ) => DelayedFunction;
	onReviewsAppended?: () => void;
	onReviewsLoadError?: ( error: ErrorObject ) => void;
	onReviewsReplaced?: () => void;
	productId?: string | number;
	attributes: {
		previewReviews?: Review[];
	};
}

interface WithReviewsState {
	error: string | ErrorObject | null;
	loading: boolean;
	reviews: Review[];
	totalReviews: number;
}

type DelayedFunction = ( () => void ) & { cancel?: () => void };

/**
 * HOC that queries reviews for a component.
 */
const withReviews = (
	OriginalComponent: React.FunctionComponent< Record< string, unknown > >
) => {
	class WrappedComponent extends Component<
		WithReviewsProps,
		WithReviewsState
	> {
		isPreview = !! this.props.attributes.previewReviews;

		delayedAppendReviews = (
			this.props.delayFunction ?? ( ( f: () => void ) => f )
		)( this.appendReviews );

		isMounted = false;

		state: WithReviewsState = {
			error: null,
			loading: true,
			reviews:
				this.isPreview && this.props.attributes?.previewReviews
					? this.props.attributes.previewReviews
					: [],
			totalReviews:
				this.isPreview && this.props.attributes?.previewReviews
					? this.props.attributes.previewReviews.length
					: 0,
		};

		componentDidMount() {
			this.isMounted = true;
			this.replaceReviews();
		}

		componentDidUpdate( prevProps: WithReviewsProps ) {
			if ( prevProps.reviewsToDisplay < this.props.reviewsToDisplay ) {
				// Since this attribute might be controlled via something with
				// short intervals between value changes, this allows for optionally
				// delaying review fetches via the provided delay function.
				this.delayedAppendReviews();
			} else if ( this.shouldReplaceReviews( prevProps, this.props ) ) {
				this.replaceReviews();
			}
		}

		shouldReplaceReviews(
			prevProps: WithReviewsProps,
			nextProps: WithReviewsProps
		) {
			return (
				prevProps.orderby !== nextProps.orderby ||
				prevProps.order !== nextProps.order ||
				prevProps.productId !== nextProps.productId ||
				! isShallowEqual(
					prevProps.categoryIds as string[],
					nextProps.categoryIds as string[]
				)
			);
		}

		componentWillUnmount() {
			this.isMounted = false;
			if (
				'cancel' in this.delayedAppendReviews &&
				typeof this.delayedAppendReviews.cancel === 'function'
			) {
				this.delayedAppendReviews.cancel();
			}
		}

		getArgs( reviewsToSkip: number ) {
			const { categoryIds, order, orderby, productId, reviewsToDisplay } =
				this.props;
			const args: Record< string, string | number > = {
				order,
				orderby,
				per_page: reviewsToDisplay - reviewsToSkip,
				offset: reviewsToSkip,
			};

			if ( categoryIds ) {
				const categories = Array.isArray( categoryIds )
					? categoryIds
					: JSON.parse( categoryIds );

				args.category_id = Array.isArray( categories )
					? categories.join( ',' )
					: categories;
			}

			if ( productId ) {
				args.product_id = productId;
			}

			return args;
		}

		replaceReviews() {
			if ( this.isPreview ) {
				return;
			}

			const onReviewsReplaced =
				this.props.onReviewsReplaced ?? ( () => undefined );
			this.updateListOfReviews().then( onReviewsReplaced );
		}

		appendReviews() {
			if ( this.isPreview ) {
				return;
			}

			const onReviewsAppended =
				this.props.onReviewsAppended ?? ( () => undefined );
			const { reviewsToDisplay } = this.props;
			const { reviews } = this.state;

			// Given that this function is delayed, props might have been updated since
			// it was called so we need to check again if fetching new reviews is necessary.
			if ( reviewsToDisplay <= reviews.length ) {
				return;
			}

			this.updateListOfReviews( reviews ).then( onReviewsAppended );
		}

		updateListOfReviews( oldReviews: Review[] = [] ) {
			const { reviewsToDisplay } = this.props;
			const { totalReviews } = this.state;
			const reviewsToLoad =
				Math.min( totalReviews, reviewsToDisplay ) - oldReviews.length;

			this.setState( {
				loading: true,
				reviews: oldReviews.concat( Array( reviewsToLoad ).fill( {} ) ),
			} );

			return getReviews( this.getArgs( oldReviews.length ) )
				.then(
					( {
						reviews: newReviews,
						totalReviews: newTotalReviews,
					} ) => {
						if ( this.isMounted ) {
							this.setState( {
								reviews: oldReviews
									.filter(
										( review ) =>
											Object.keys( review ).length
									)
									.concat( newReviews ),
								totalReviews: newTotalReviews,
								loading: false,
								error: null,
							} );
						}
						return { newReviews };
					}
				)
				.catch( this.setError );
		}

		setError = async ( e: Error ) => {
			if ( ! this.isMounted ) {
				return;
			}

			const onReviewsLoadError =
				this.props.onReviewsLoadError ?? ( () => undefined );
			const error = await formatError( e );

			this.setState( { reviews: [], loading: false, error } );

			onReviewsLoadError( error );
		};

		render() {
			const { reviewsToDisplay } = this.props;
			const { error, loading, reviews, totalReviews } = this.state;

			return (
				<OriginalComponent
					{ ...this.props }
					error={ error }
					isLoading={ loading }
					reviews={ reviews.slice( 0, reviewsToDisplay ) }
					totalReviews={ totalReviews }
				/>
			);
		}
	}

	const { displayName = OriginalComponent.name || 'Component' } =
		OriginalComponent;
	(
		WrappedComponent as React.ComponentType< WithReviewsProps >
	 ).displayName = `WithReviews(${ displayName })`;
	return WrappedComponent;
};

export default withReviews;
