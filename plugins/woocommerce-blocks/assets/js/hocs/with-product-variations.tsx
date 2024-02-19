/**
 * External dependencies
 */
import { Component, ReactNode } from '@wordpress/element';
import { createHigherOrderComponent } from '@wordpress/compose';
import isShallowEqual from '@wordpress/is-shallow-equal';
import { getProductVariations } from '@woocommerce/editor-components/utils';

/**
 * Internal dependencies
 */
import { formatError } from '../base/utils/errors';

// Define TypeScript interfaces for props and state
interface Product {
	id: string;
	variations?: Variation[];
}

interface Variation {
	id: string;
	// Add other properties of variations here
}

interface Props {
	selected: string[];
	showVariations: boolean;
	products: Product[];
	isLoading?: boolean;
	error?: Error; // Assuming error is of type Error, adjust as necessary
}

interface State {
	error: Error | null;
	loading: boolean;
	variations: { [ key: string ]: Variation[] | null };
}

/**
 * HOC that queries variations for a component.
 *
 * @param OriginalComponent Component being wrapped.
 */
const withProductVariations = createHigherOrderComponent(
	( OriginalComponent: React.ComponentType< Props > ) => {
		class WrappedComponent extends Component< Props, State > {
			state: State = {
				error: null,
				loading: false,
				variations: {},
			};

			// Assuming there's a mechanism to store prevSelectedItem
			private prevSelectedItem?: string;

			componentDidMount() {
				const { selected, showVariations } = this.props;

				if ( selected && showVariations ) {
					this.loadVariations();
				}
			}

			componentDidUpdate( prevProps: Props ) {
				const { isLoading, selected, showVariations } = this.props;

				if (
					showVariations &&
					( ! isShallowEqual( prevProps.selected, selected ) ||
						( prevProps.isLoading && ! isLoading ) )
				) {
					this.loadVariations();
				}
			}

			loadVariations = (): void => {
				const { products } = this.props;
				const { loading, variations } = this.state;

				if ( loading ) {
					return;
				}

				const expandedProduct = this.getExpandedProduct();

				if ( ! expandedProduct || variations[ expandedProduct ] ) {
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

				getProductVariations( expandedProduct )
					.then( ( expandedProductVariations ) => {
						const newVariations = expandedProductVariations.map(
							( variation ) => ( {
								...variation,
								parent: expandedProduct,
							} )
						);
						this.setState( {
							variations: {
								...this.state.variations,
								[ expandedProduct ]: newVariations,
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
							loading: false,
							error,
						} );
					} );
			};

			isProductId( itemId: string ): boolean {
				const { products } = this.props;
				return products.some( ( p ) => p.id === itemId );
			}

			findParentProduct( variationId: string ): string | undefined {
				const { products } = this.props;
				const parentProduct = products.find( ( p ) =>
					p.variations?.some( ( { id } ) => id === variationId )
				);
				return parentProduct?.id;
			}

			getExpandedProduct(): string | null {
				const { isLoading, selected, showVariations } = this.props;

				if ( ! showVariations ) {
					return null;
				}

				let selectedItem =
					selected && selected.length ? selected[ 0 ] : null;

				// If there is no selected item, check if there was one in the past
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

				if ( selectedItem ) {
					return this.isProductId( selectedItem )
						? selectedItem
						: this.findParentProduct( selectedItem );
				}

				return null;
			}

			render(): ReactNode {
				const { error: propsError, isLoading } = this.props;
				const { error, loading, variations } = this.state;

				return (
					<OriginalComponent
						{ ...this.props }
						error={ error || propsError }
						expandedProduct={ this.getExpandedProduct() }
						isLoading={ isLoading }
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
