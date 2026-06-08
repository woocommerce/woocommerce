/**
 * External dependencies
 */
import { store, getContext } from '@wordpress/interactivity';
import type { ProductResponseItem } from '@woocommerce/types';
import type {
	SelectedAttributes,
	ClientCartItem,
} from '@woocommerce/stores/woocommerce/cart';

export type ProductAddToCartError = {
	code: string;
	group: string;
	message: string;
};

export type ProductAddToCartContext = {
	quantity?: Record< number, number >;
	selectedAttributes?: SelectedAttributes[];
	validationErrors?: ProductAddToCartError[];
	groupedProductIds?: number[];
};

export type ProductAddToCartStore = {
	state: {
		quantity: Record< number, number >;
		selectedAttributes: SelectedAttributes[];
		validationErrors: ProductAddToCartError[];
		quantityInContext: Record< number, number >;
		selectedAttributesInContext: SelectedAttributes[];
		validationErrorsInContext: ProductAddToCartError[];
	};
	actions: {
		initializeQuantity: ( productId: number, value: number ) => void;
		setQuantity: ( productId: number, value: number ) => void;
		setAttribute: ( attribute: string, value: string ) => void;
		removeAttribute: ( attribute: string ) => void;
		addError: ( error: ProductAddToCartError ) => string;
		clearErrors: ( group?: string ) => void;
	};
};

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const normalizeAttributeName = ( name: string ): string =>
	name
		.replace( /^attribute_(pa_)?/, '' )
		.replace( /-/g, ' ' )
		.toLowerCase();

const attributeNamesMatch = ( a: string, b: string ): boolean =>
	normalizeAttributeName( a ) === normalizeAttributeName( b );

const fallbackState: Pick<
	ProductAddToCartStore[ 'state' ],
	'quantity' | 'selectedAttributes' | 'validationErrors'
> = {
	quantity: {},
	selectedAttributes: [],
	validationErrors: [],
};

let productAddToCartState: ProductAddToCartStore[ 'state' ];

const getProductAddToCartContext = (): ProductAddToCartContext | undefined => {
	try {
		const context = getContext< ProductAddToCartContext >(
			'woocommerce/product-add-to-cart'
		);

		return context &&
			( 'quantity' in context ||
				'selectedAttributes' in context ||
				'validationErrors' in context ||
				'groupedProductIds' in context )
			? context
			: undefined;
	} catch {
		return undefined;
	}
};

const getQuantityTarget = (): Record< number, number > => {
	const context = getProductAddToCartContext();
	if ( context ) {
		context.quantity = context.quantity || {};
		return context.quantity;
	}

	return productAddToCartState?.quantity ?? fallbackState.quantity;
};

const getSelectedAttributesTarget = (): SelectedAttributes[] => {
	const context = getProductAddToCartContext();
	if ( context ) {
		context.selectedAttributes = context.selectedAttributes || [];
		return context.selectedAttributes;
	}

	return (
		productAddToCartState?.selectedAttributes ??
		fallbackState.selectedAttributes
	);
};

const getValidationErrorsTarget = (): ProductAddToCartError[] => {
	const context = getProductAddToCartContext();
	if ( context ) {
		context.validationErrors = context.validationErrors || [];
		return context.validationErrors;
	}

	return (
		productAddToCartState?.validationErrors ?? fallbackState.validationErrors
	);
};

export const getProductAddToCartPayload = (
	product: ProductResponseItem,
	fallbackQuantity = 1
): ClientCartItem => {
	const quantity = productAddToCartState.quantityInContext[ product.id ];
	const selectedAttributes = productAddToCartState.selectedAttributesInContext;

	const payload: ClientCartItem = {
		id: product.id,
		quantityToAdd: quantity ?? fallbackQuantity,
		type: product.type,
	};

	if ( selectedAttributes.length ) {
		payload.variation = selectedAttributes;
	}

	return payload;
};

( { state: productAddToCartState } = store< ProductAddToCartStore >(
	'woocommerce/product-add-to-cart',
	{
		state: {
			...fallbackState,
			get quantityInContext(): Record< number, number > {
				return getQuantityTarget();
			},
			get selectedAttributesInContext(): SelectedAttributes[] {
				return getSelectedAttributesTarget();
			},
			get validationErrorsInContext(): ProductAddToCartError[] {
				return getValidationErrorsTarget();
			},
		},
		actions: {
			initializeQuantity( productId: number, value: number ) {
				const quantity = getQuantityTarget();
				if ( quantity[ productId ] === undefined ) {
					quantity[ productId ] = value;
				}
			},
			setQuantity( productId: number, value: number ) {
				const quantity = getQuantityTarget();
				quantity[ productId ] = value;
			},
			setAttribute( attribute: string, value: string ) {
				const selectedAttributes = getSelectedAttributesTarget();
				const index = selectedAttributes.findIndex( ( selectedAttribute ) =>
					attributeNamesMatch( selectedAttribute.attribute, attribute )
				);

				if ( value === '' ) {
					if ( index >= 0 ) {
						selectedAttributes.splice( index, 1 );
					}
					return;
				}

				if ( index >= 0 ) {
					selectedAttributes[ index ] = { attribute, value };
				} else {
					selectedAttributes.push( { attribute, value } );
				}
			},
			removeAttribute( attribute: string ) {
				const selectedAttributes = getSelectedAttributesTarget();
				const index = selectedAttributes.findIndex( ( selectedAttribute ) =>
					attributeNamesMatch( selectedAttribute.attribute, attribute )
				);

				if ( index >= 0 ) {
					selectedAttributes.splice( index, 1 );
				}
			},
			addError( error: ProductAddToCartError ): string {
				getValidationErrorsTarget().push( error );
				return error.code;
			},
			clearErrors( group?: string ): void {
				const validationErrors = getValidationErrorsTarget();
				if ( group ) {
					const remaining = validationErrors.filter(
						( error ) => error.group !== group
					);
					validationErrors.splice(
						0,
						validationErrors.length,
						...remaining
					);
					return;
				}

				validationErrors.length = 0;
			},
		},
	},
	{ lock: universalLock }
) );
