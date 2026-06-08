/**
 * External dependencies
 */
import { store, getContext, getElement } from '@wordpress/interactivity';
import '@woocommerce/stores/woocommerce/products';
import type { ProductsStore } from '@woocommerce/stores/woocommerce/products';
import '@woocommerce/stores/woocommerce/add-to-cart';
import type { AddToCartStore } from '@woocommerce/stores/woocommerce/add-to-cart';

/**
 * Internal dependencies
 */
import type {
	AddToCartWithOptionsStore,
	Context as AddToCartWithOptionsContext,
} from '../frontend';

export type Context = {
	allowZero?: boolean;
	inputElement?: HTMLInputElement | null;
};

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const { state: productsState } = store< ProductsStore >(
	'woocommerce/products',
	{},
	{ lock: universalLock }
);

type OptionalAddToCartWithOptionsStore = {
	state: Partial< AddToCartWithOptionsStore[ 'state' ] >;
	actions: Partial< AddToCartWithOptionsStore[ 'actions' ] >;
};

const addToCartWithOptionsStore = store< OptionalAddToCartWithOptionsStore >(
	'woocommerce/add-to-cart-with-options',
	{},
	{ lock: universalLock }
);

const addToCartStore = store< AddToCartStore >(
	'woocommerce/add-to-cart',
	{},
	{ lock: universalLock }
);

const getProductsContext = () => {
	try {
		return getContext< { productId?: number; variationId?: number | null } >(
			'woocommerce/products'
		);
	} catch {
		return undefined;
	}
};

const getProductInContext = () => {
	try {
		const product = productsState.productInContext;
		if ( product ) {
			return product;
		}
	} catch {
		// Fall back to raw IDs below.
	}

	const productsContext = getProductsContext();
	const productId =
		productsContext && 'productId' in productsContext
			? productsContext.productId
			: productsState.productId;
	const variationId =
		productsContext && 'variationId' in productsContext
			? productsContext.variationId
			: productsState.variationId;

	return (
		( variationId && productsState.productVariations?.[ variationId ] ) ||
		( productId && productsState.products?.[ productId ] ) ||
		null
	);
};

const getAddToCartWithOptionsContext = () => {
	try {
		return getContext< AddToCartWithOptionsContext >(
			'woocommerce/add-to-cart-with-options'
		);
	} catch {
		return undefined;
	}
};

const getCurrentQuantity = ( productId: number, fallback = 0 ): number => {
	const addToCartWithOptionsContext = getAddToCartWithOptionsContext();
	const addToCartWithOptionsQuantity =
		addToCartWithOptionsContext?.quantity?.[ productId ] ??
		addToCartWithOptionsStore.state.quantity?.[ productId ];
	const addToCartQuantity =
		addToCartStore.state.quantityInContext?.[ productId ];
	const quantity = addToCartQuantity ?? addToCartWithOptionsQuantity;

	return quantity === undefined ? fallback : quantity;
};

const setQuantity = ( productId: number, value: number ) => {
	addToCartStore.actions.setQuantity( productId, value );

	if (
		getAddToCartWithOptionsContext()?.quantity &&
		addToCartWithOptionsStore.actions.setQuantity
	) {
		addToCartWithOptionsStore.actions.setQuantity( productId, value );
	}

	if ( Number.isNaN( value ) ) {
		return;
	}

	document
		.querySelectorAll< HTMLInputElement >(
			`[data-wc-product-add-to-cart-product-id="${ productId }"] .qty`
		)
		.forEach( ( input ) => {
			input.value = value.toString();
		} );
};

export type QuantitySelectorStore = {
	state: {
		allowsQuantityChange: boolean;
		allowsDecrease: boolean;
		allowsIncrease: boolean;
		inputQuantity: number;
	};
	actions: {
		increaseQuantity: () => void;
		decreaseQuantity: () => void;
		handleQuantityBlur: () => void;
		handleQuantityCheckboxChange: () => void;
	};
	callbacks: {
		storeInputElementRef: () => void;
	};
};

store< QuantitySelectorStore >(
	'woocommerce/add-to-cart-with-options-quantity-selector',
	{
		state: {
			get allowsQuantityChange(): boolean {
				const product = getProductInContext();

				if ( ! product ) {
					return true;
				}

				return product.is_in_stock && ! product.sold_individually;
			},
			get allowsDecrease() {
				const product = getProductInContext();

				if ( ! product ) {
					return true;
				}

				const { id, add_to_cart: addToCart } = product;
				const currentQuantity = getCurrentQuantity( id );

				const { allowZero } = getContext< Context >();
				return (
					( allowZero && currentQuantity > 0 ) ||
					currentQuantity - addToCart.multiple_of >= addToCart.minimum
				);
			},
			get allowsIncrease() {
				const product = getProductInContext();

				if ( ! product ) {
					return true;
				}

				const { id, add_to_cart: addToCart } = product;
				const currentQuantity = getCurrentQuantity( id );

				return (
					currentQuantity + addToCart.multiple_of <= addToCart.maximum
				);
			},
			get inputQuantity(): number {
				const product = getProductInContext();

				if ( ! product ) {
					return 0;
				}

				return getCurrentQuantity(
					product.id,
					product.add_to_cart.minimum
				);
			},
		},
		actions: {
			increaseQuantity: () => {
				const { inputElement } = getContext< Context >();

				if ( ! ( inputElement instanceof HTMLInputElement ) ) {
					return;
				}

				const product = getProductInContext();

				if ( ! product ) {
					return;
				}

				const currentValue = Number( inputElement.value ) || 0;
				const { id: productId, add_to_cart: addToCart } = product;
				const { minimum, maximum, multiple_of: multipleOf } = addToCart;

				const newValue = Math.max(
					minimum,
					Math.min( maximum, currentValue + multipleOf )
				);

				setQuantity( productId, newValue );
			},
			decreaseQuantity: () => {
				const { allowZero, inputElement } = getContext< Context >();

				if ( ! ( inputElement instanceof HTMLInputElement ) ) {
					return;
				}

				const product = getProductInContext();

				if ( ! product ) {
					return;
				}

				const currentValue = Number( inputElement.value ) || 0;
				const { id: productId, add_to_cart: addToCart } = product;
				const { minimum, maximum, multiple_of: multipleOf } = addToCart;

				let newValue = currentValue - multipleOf;
				if (
					allowZero &&
					newValue < minimum &&
					currentValue === minimum
				) {
					newValue = 0;
				} else {
					newValue = Math.min(
						maximum,
						Math.max( minimum, newValue )
					);
				}

				if ( newValue !== currentValue ) {
					setQuantity( productId, newValue );
				}
			},
			// We need to listen to blur events instead of change events because
			// the change event isn't triggered in invalid numbers (ie: writing
			// letters) if the current value is already invalid or an empty string.
			handleQuantityBlur: () => {
				const { allowZero, inputElement } = getContext< Context >();

				const product = getProductInContext();

				if ( ! product ) {
					return;
				}

				const { id: productId, add_to_cart: addToCart } = product;
				const isValueNaN = Number.isNaN( inputElement?.valueAsNumber );

				if (
					allowZero &&
					( isValueNaN || inputElement?.valueAsNumber === 0 )
				) {
					setQuantity( productId, 0 );
					return;
				}

				// In other product types, we reset inputs to `minimum` if they
				// are 0 or NaN.
				const value = inputElement?.valueAsNumber ?? NaN;
				const newValue =
					! isNaN( value ) && value > 0 ? value : addToCart.minimum;

				setQuantity( productId, newValue );
			},
			handleQuantityCheckboxChange: () => {
				const element = getElement();

				if ( ! ( element.ref instanceof HTMLInputElement ) ) {
					return;
				}

				const product = getProductInContext();

				if ( ! product ) {
					return;
				}

				setQuantity( product.id, element.ref.checked ? 1 : 0 );
			},
		},
		callbacks: {
			storeInputElementRef: () => {
				const { ref } = getElement();
				if ( ref ) {
					const context = getContext< Context >();
					const inputElement =
						ref.querySelector< HTMLInputElement >( '.qty' );
					context.inputElement = inputElement;

					const product = getProductInContext();
					if ( product && inputElement ) {
						const inputValue = inputElement.valueAsNumber;
						const initialQuantity = Number.isNaN( inputValue )
							? product.add_to_cart.minimum
							: inputValue;

						addToCartStore.actions.initializeQuantity(
							product.id,
							initialQuantity
						);
					}
				}
			},
		},
	},
	{ lock: universalLock }
);
