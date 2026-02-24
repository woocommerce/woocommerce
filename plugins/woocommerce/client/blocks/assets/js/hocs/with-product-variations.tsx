/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import { createHigherOrderComponent } from '@wordpress/compose';
import isShallowEqual from '@wordpress/is-shallow-equal';
import { getProductVariationsWithTotal } from '@woocommerce/editor-components/utils';
import { ErrorObject } from '@woocommerce/editor-components/error-placeholder';
import {
	ProductResponseItem,
	ProductResponseVariationsItem,
} from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { formatError } from '../base/utils/errors';

interface WithProductVariationsProps {
	selected: number[];
	showVariations: boolean;
	products: ProductResponseItem[];
	isLoading?: boolean;
	error?: ErrorObject;
}

interface State {
	error: ErrorObject | null;
	loading: boolean;
	variations: { [ key: number ]: ProductResponseVariationsItem[] | null };
	totalVariations: { [ key: number ]: number | null };
}

/**
 * HOC that queries variations for a component.
 *
 * @param OriginalComponent Component being wrapped.
 */
const withProductVariations = createHigherOrderComponent(
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore ignoring this line because @wordpress/compose does not expose the correct type for createHigherOrderComponent
	( OriginalComponent ) => {
		class WrappedComponent extends Component<
			WithProductVariationsProps,
			State
		> {
			state: State = {
				error: null,
				loading: false,
				variations: {},
				totalVariations: {},
			};

			private prevSelectedItem?: number;

			componentDidMount() {
				const { selected, showVariations } = this.props;

				if ( selected && showVariations ) {
					this.loadVariations();
				}
			}

			componentDidUpdate( prevProps: WithProductVariationsProps ) {
				const { isLoading, selected, showVariations } = this.props;

				if (
					showVariations &&
					( ! isShallowEqual( prevProps.selected, selected ) ||
						( prevProps.isLoading && ! isLoading ) )
				) {
					this.loadVariations();
				}
			}

			loadVariations = ( { offset = 0 }: { offset?: number } = {} ) => {
				const { products } = this.props;
				const { loading, variations, totalVariations } = this.state;

				if ( loading ) {
					return;
				}

				const expandedProduct = this.getExpandedProduct();

				if ( ! expandedProduct ) {
					return;
				}

				if ( ! offset && variations?.[ expandedProduct ] ) {
					return;
				}

				if (
					variations?.[ expandedProduct ] &&
					totalVariations?.[ expandedProduct ] &&
					variations[ expandedProduct ].length >=
						totalVariations[ expandedProduct ]
				) {
					return;
				}

				const productDetails = products.find(
					( findProduct ) => findProduct.id === expandedProduct
				);

				if (
					! productDetails?.variations ||
					productDetails.variations.length === 0
				) {
					this.setState( {
						variations: {
							...this.state.variations,
							[ expandedProduct ]: null,
						},
						loading: false,
						error: null,
					} );
					return;
				}

				this.setState( { loading: true } );

				const alreadyLoadedVariations =
					this.state.variations[ expandedProduct ] || [];

				(
					getProductVariationsWithTotal( expandedProduct, {
						offset,
					} ) as Promise< {
						variations: ProductResponseVariationsItem[];
						total: number;
					} >
				 )
					.then( ( { variations: variationsData, total } ) => {
						const newVariations = variationsData.map(
							( variation ) => ( {
								...variation,
								parent: expandedProduct,
							} )
						);
						this.setState( {
							variations: {
								...this.state.variations,
								[ expandedProduct ]: [
									...alreadyLoadedVariations,
									...newVariations,
								],
							},
							totalVariations: {
								...this.state.totalVariations,
								[ expandedProduct ]: total,
							},
							loading: false,
							error: null,
						} );
					} )
					.catch( async ( e ) => {
						const error = await formatError( e );

						this.setState( {
							variations: {
								...this.state.variations,
								[ expandedProduct ]: null,
							},
							totalVariations: {
								...this.state.totalVariations,
								[ expandedProduct ]: null,
							},
							loading: false,
							error,
						} );
					} );
			};

			isProductId( itemId: number ) {
				const { products } = this.props;
				return products.some( ( p ) => p.id === itemId );
			}

			findParentProduct( variationId: number ) {
				const { products } = this.props;
				const parentProduct = products.filter(
					( p ) =>
						p.variations &&
						p.variations.find( ( { id } ) => id === variationId )
				);
				return parentProduct[ 0 ]?.id;
			}

			getExpandedProduct() {
				const { isLoading, selected, showVariations } = this.props;

				if ( ! showVariations ) {
					return null;
				}

				let selectedItem =
					selected && selected.length ? selected[ 0 ] : null;

				// If there is no selected item, check if there was one in the past, so we
				// can keep the same product expanded.
				if ( selectedItem ) {
					this.prevSelectedItem = selectedItem;
				} else if (
					this.prevSelectedItem &&
					! isLoading &&
					! this.isProductId( this.prevSelectedItem )
				) {
					// If previous selected item was a variation
					selectedItem = this.prevSelectedItem;
				}

				if ( ! isLoading && selectedItem ) {
					return this.isProductId( selectedItem )
						? selectedItem
						: this.findParentProduct( selectedItem );
				}

				return null;
			}

			render() {
				const { error: propsError, isLoading } = this.props;
				const { error, loading, variations, totalVariations } =
					this.state;
				const expandedProduct = this.getExpandedProduct();
				const offset = expandedProduct
					? variations[ expandedProduct ]?.length || 0
					: 0;

				return (
					// eslint-disable-next-line @typescript-eslint/ban-ts-comment
					// @ts-ignore ignoring this line because @wordpress/compose does not expose the correct type for createHigherOrderComponent
					<OriginalComponent
						{ ...this.props }
						error={ error || propsError }
						onLoadMoreVariations={ () =>
							this.loadVariations( {
								offset,
							} )
						}
						expandedProduct={ this.getExpandedProduct() }
						isLoading={ isLoading }
						totalVariations={ totalVariations }
						variations={ variations }
						variationsLoading={ loading }
					/>
				);
			}
		}

		return WrappedComponent;
	},
	'withProductVariations'
);

export default withProductVariations;
