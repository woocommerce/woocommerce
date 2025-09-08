/**
 * External dependencies
 */
import { getConfig, getContext, store } from '@wordpress/interactivity';
import { SelectedAttributes } from '@woocommerce/stores/woocommerce/cart';

/**
 * Internal dependencies
 */
import { getMatchedVariation } from '../../utils/variations/get-matched-variation';

type ProductRef = {
	productId: number;
	variationId: number | null;
	selectedAttributes: SelectedAttributes[];
};

export type Context = ProductRef;

type ServerState = {
	templateState: ProductRef;
};

const productDataStore = store< {
	state: ProductRef & ServerState;
	actions: {
		setAttribute: ( attribute: string, value: string ) => void;
		removeAttribute: ( attribute: string ) => void;
		setSelectedVariationId: () => void;
	};
} >(
	'woocommerce/product-data',
	{
		state: {
			get productId(): number {
				const context = getContext< Context >(
					'woocommerce/single-product'
				);

				return (
					context?.productId ||
					productDataStore?.state?.templateState?.productId
				);
			},
			get variationId(): number | null {
				const context = getContext< Context >(
					'woocommerce/single-product'
				);

				return (
					context?.variationId ||
					productDataStore?.state?.templateState?.variationId
				);
			},
			get selectedAttributes(): SelectedAttributes[] {
				const context = getContext< Context >(
					'woocommerce/single-product'
				);

				const attributes =
					context?.selectedAttributes ||
					productDataStore?.state?.templateState?.selectedAttributes;

				return Array.isArray( attributes ) ? attributes : [];
			},
		},
		actions: {
			setAttribute( attribute: string, value: string ) {
				const selectedAttributes =
					productDataStore.state.selectedAttributes;
				const index = selectedAttributes.findIndex(
					( selectedAttribute ) =>
						selectedAttribute.attribute === attribute
				);

				if ( value === '' ) {
					if ( index >= 0 ) {
						selectedAttributes.splice( index, 1 );
					}
					return;
				}

				if ( index >= 0 ) {
					selectedAttributes[ index ] = {
						attribute,
						value,
					};
				} else {
					selectedAttributes.push( {
						attribute,
						value,
					} );
				}
			},
			removeAttribute( attribute: string ) {
				const selectedAttributes =
					productDataStore.state.selectedAttributes;
				const index = selectedAttributes.findIndex(
					( selectedAttribute ) =>
						selectedAttribute.attribute === attribute
				);
				if ( index >= 0 ) {
					selectedAttributes.splice( index, 1 );
				}
			},
			setSelectedVariationId: () => {
				const { products } = getConfig( 'woocommerce' );

				if (
					! products ||
					! products[ productDataStore.state.productId ]
				) {
					return;
				}

				const variations =
					products[ productDataStore.state.productId ].variations ||
					[];

				const matchedVariation = getMatchedVariation(
					variations,
					productDataStore.state.selectedAttributes
				);

				const matchedVariationId = matchedVariation?.variation_id;

				if ( typeof matchedVariationId !== 'number' ) {
					return;
				}

				const context = getContext< Context >(
					'woocommerce/single-product'
				);

				if ( context?.variationId !== undefined ) {
					context.variationId = matchedVariationId;
				} else if (
					productDataStore?.state?.templateState?.variationId !==
					undefined
				) {
					productDataStore.state.templateState.variationId =
						matchedVariationId;
				}
			},
		},
	},
	{ lock: true }
);

export type ProductDataStore = typeof productDataStore;
