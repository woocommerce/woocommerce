/**
 * External dependencies
 */
import { getContext, store } from '@wordpress/interactivity';
import type { SelectedAttributes } from '@woocommerce/stores/woocommerce/cart';
import type { ProductResponseItem } from '@woocommerce/types';

type ProductRef = {
	productId: number;
	variationId: number | null;
	products: ProductResponseItem[];
	productVariations: ProductResponseItem[];
};

export type Context = ProductRef;

type ServerState = {
	templateState: ProductRef;
};

const productDataStore = store< {
	state: ProductRef & ServerState;
	actions: {
		setVariationId: ( variationId: number | null ) => void;
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
					context?.productId ??
					productDataStore?.state?.templateState?.productId
				);
			},
			get variationId(): number | null {
				const context = getContext< Context >(
					'woocommerce/single-product'
				);

				return (
					context?.variationId ??
					productDataStore?.state?.templateState?.variationId
				);
			},
		},
		actions: {
			setVariationId: ( variationId: number | null ) => {
				const context = getContext< Context >(
					'woocommerce/single-product'
				);

				if ( context?.variationId !== undefined ) {
					context.variationId = variationId;
				} else if (
					productDataStore?.state?.templateState?.variationId !==
					undefined
				) {
					productDataStore.state.templateState.variationId =
						variationId;
				}
			},
		},
	},
	{ lock: true }
);

export type ProductDataStore = typeof productDataStore;

// Expect state.products and state.productVariations to be populated properly from the server and client.
// This will change depending on what we decide about state.product, state.productVariations, or state.parentProduct.
export const getProductData = (
	id: number,
	selectedAttributes: SelectedAttributes[] = []
): ProductResponseItem | null => {
	// The logic will change depending on where the variations are stored.

	const { products, productVariations } = productDataStore.state;

	if ( ! products || ! products[ id ] ) {
		return null;
	}

	const parentProduct =
		products.find( ( product ) => product.id === id ) || null;

	// If the product is not found in the products list,
	// check if the id corresponds to a variation directly.
	if ( ! parentProduct ) {
		return (
			productVariations.find( ( variation ) => variation.id === id ) ||
			null
		);
	}

	// If no attributes are selected, just return the parent.
	if ( selectedAttributes.length === 0 ) {
		return parentProduct;
	}
	// If not, check the variation that matches.
	const matchedVariation = parentProduct.variations?.find( ( variation ) => {
		return variation.attributes.every(
			( {
				// eslint-disable-next-line
				name,
				value,
			} ) =>
				selectedAttributes.some( ( item: SelectedAttributes ) => {
					return (
						item.attribute === name &&
						( item.value.toLowerCase() === value.toLowerCase() ||
							( item.value && value === '' ) ||
							null ) // Handle "any" attribute type
					);
				} )
		);
	} );

	return (
		productVariations.find(
			( variation ) => variation.id === matchedVariation?.id
		) || null
	);
};
