/**
 * External dependencies
 */
import { store, getContext } from '@wordpress/interactivity';
import type { ProductResponseItem } from '@woocommerce/types';
import type {
	SelectedAttributes,
	ClientCartItem,
} from '@woocommerce/stores/woocommerce/cart';

export type AddToCartError = {
	code: string;
	group: string;
	message: string;
};

export type AddToCartContext = {
	quantity?: Record< number, number >;
	selectedAttributes?: SelectedAttributes[];
	validationErrors?: AddToCartError[];
	groupedProductIds?: number[];
};

export type AddToCartStore = {
	state: {
		quantity: Record< number, number >;
		selectedAttributes: SelectedAttributes[];
		validationErrors: AddToCartError[];
		quantityInContext: Record< number, number >;
		selectedAttributesInContext: SelectedAttributes[];
		validationErrorsInContext: AddToCartError[];
	};
	actions: {
		initializeQuantity: ( productId: number, value: number ) => void;
		setQuantity: ( productId: number, value: number ) => void;
		setAttribute: ( attribute: string, value: string ) => void;
		removeAttribute: ( attribute: string ) => void;
		addError: ( error: AddToCartError ) => string;
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
	AddToCartStore[ 'state' ],
	'quantity' | 'selectedAttributes' | 'validationErrors'
> = {
	quantity: {},
	selectedAttributes: [],
	validationErrors: [],
};

let addToCartState: AddToCartStore[ 'state' ];

const getAddToCartContext = (): AddToCartContext | undefined => {
	try {
		const context = getContext< AddToCartContext >(
			'woocommerce/add-to-cart'
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
	const context = getAddToCartContext();
	if ( context ) {
		context.quantity = context.quantity || {};
		return context.quantity;
	}

	return addToCartState?.quantity ?? fallbackState.quantity;
};

const getSelectedAttributesTarget = (): SelectedAttributes[] => {
	const context = getAddToCartContext();
	if ( context ) {
		context.selectedAttributes = context.selectedAttributes || [];
		return context.selectedAttributes;
	}

	return (
		addToCartState?.selectedAttributes ??
		fallbackState.selectedAttributes
	);
};

const getValidationErrorsTarget = (): AddToCartError[] => {
	const context = getAddToCartContext();
	if ( context ) {
		context.validationErrors = context.validationErrors || [];
		return context.validationErrors;
	}

	return (
		addToCartState?.validationErrors ?? fallbackState.validationErrors
	);
};

export const getAddToCartPayload = (
	product: ProductResponseItem,
	fallbackQuantity = 1
): ClientCartItem => {
	const quantity = addToCartState.quantityInContext[ product.id ];
	const selectedAttributes = addToCartState.selectedAttributesInContext;

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

( { state: addToCartState } = store< AddToCartStore >(
	'woocommerce/add-to-cart',
	{
		state: {
			...fallbackState,
			get quantityInContext(): Record< number, number > {
				return getQuantityTarget();
			},
			get selectedAttributesInContext(): SelectedAttributes[] {
				return getSelectedAttributesTarget();
			},
			get validationErrorsInContext(): AddToCartError[] {
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
			addError( error: AddToCartError ): string {
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
