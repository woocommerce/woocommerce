/**
 * External dependencies
 */
import {
	store,
	getContext,
	getConfig,
	withSyncEvent,
} from '@wordpress/interactivity';
import '@woocommerce/stores/woocommerce';
import type {
	WooCommerceStore,
	ProductScopeContext,
} from '@woocommerce/stores/woocommerce';
import type { Store as StoreNotices } from '@woocommerce/stores/store-notices';

/**
 * Internal dependencies
 */
import type { GroupedProductAddToCartWithOptionsStore } from './grouped-product-selector/frontend';
import type { Context as QuantitySelectorContext } from './quantity-selector/frontend';
import type { VariableProductAddToCartWithOptionsStore } from './variation-selector/frontend';

export type Context = {
	initialQuantity: Record< number, number >;
	validationErrors: AddToCartError[];
	noticeIds: string[];
	groupedProductIds: number[];
	/** Each grouped child's own scope name, in `groupedProductIds` order. */
	groupedScopeNames: string[];
};

export type AddToCartError = {
	code: string;
	group: string;
	message: string;
};

/**
 * Manually dispatches a 'change' event on the quantity input element.
 *
 * When users click the plus/minus stepper buttons, no 'change' event is fired
 * since there is no direct interaction with the input. However, some extensions
 * rely on the change event to detect quantity changes. This function ensures
 * those extensions continue working by programmatically dispatching the event.
 *
 * @see https://github.com/woocommerce/woocommerce/issues/53031
 *
 * @param inputElement - The quantity input element to dispatch the event on.
 */
const dispatchChangeEvent = ( inputElement: HTMLInputElement ) => {
	const event = new Event( 'change', { bubbles: true } );
	inputElement.dispatchEvent( event );
};

const { state: wooState, actions: wooActions } = store< WooCommerceStore >(
	'woocommerce',
	{},
	{
		lock: 'I acknowledge that using a private store means my plugin will inevitably break on the next store release.',
	}
);

export type AddToCartWithOptionsStore = {
	state: {
		noticeIds: string[];
		validationErrors: AddToCartError[];
		isFormValid: boolean;
		allowsAddingToCart: boolean;
		/**
		 * The effective quantity of the scope the reading element sits in:
		 * the typed quantity when the scope has a record, and its
		 * `initialQuantity` entry otherwise.
		 */
		effectiveQuantity: number;
	};
	actions: {
		validateQuantity: ( value?: number ) => void;
		setQuantity: ( value: number ) => void;
		addError: ( error: AddToCartError ) => string;
		clearErrors: ( group?: string ) => void;
		addToCart: ( event: SubmitEvent ) => void;
	};
};

type MergedAddToCartWithOptionsStores = AddToCartWithOptionsStore &
	Partial< GroupedProductAddToCartWithOptionsStore > &
	Partial< VariableProductAddToCartWithOptionsStore >;

const { state } = store< MergedAddToCartWithOptionsStores >(
	'woocommerce/add-to-cart-with-options',
	{},
	{
		lock: 'I acknowledge that using a private store means my plugin will inevitably break on the next store release.',
	}
);
const { actions } = store< MergedAddToCartWithOptionsStores >(
	'woocommerce/add-to-cart-with-options',
	{
		state: {
			get noticeIds(): string[] {
				const context = getContext< Context >();
				return context?.noticeIds ?? [];
			},
			get validationErrors(): Array< AddToCartError > {
				const context = getContext< Context >();

				if ( context && context.validationErrors ) {
					return context.validationErrors;
				}

				return [];
			},
			get isFormValid(): boolean {
				return state.validationErrors.length === 0;
			},
			get allowsAddingToCart(): boolean {
				const product = wooState.productScope.product;

				if ( ! product ) {
					return false;
				}

				// For grouped products, the button should always be visible.
				// Its enabled/disabled state is controlled by isFormValid which
				// checks whether any child products are selected.
				if ( product.type === 'grouped' ) {
					return true;
				}

				return product.is_purchasable && product.is_in_stock;
			},
			get effectiveQuantity(): number {
				const scopeName =
					getContext< ProductScopeContext >( 'woocommerce' )
						?.scopeName ?? '_default';
				// Read the record directly rather than through
				// `draftCartItem.quantity`: the draft's fallback of `1`
				// cannot tell "typed 1" from "nothing typed".
				const typedQuantity =
					wooState.productScopes[ scopeName ]?.draftCartItem
						?.quantity;

				if ( typeof typedQuantity === 'number' ) {
					return typedQuantity;
				}

				const { initialQuantity } = getContext< Context >();
				return (
					initialQuantity?.[ wooState.productScope.productId ] ?? 0
				);
			},
		},
		actions: {
			validateQuantity( value?: number ) {
				actions.clearErrors( 'invalid-quantities' );

				if ( typeof value !== 'number' ) {
					return;
				}

				// If selected quantity is invalid, add an error.
				const product = wooState.productScope.product;

				if (
					value === 0 ||
					( product &&
						( value < product.add_to_cart.minimum ||
							value > product.add_to_cart.maximum ) )
				) {
					const { errorMessages } = getConfig();

					actions.addError( {
						code: 'invalidQuantities',
						message: errorMessages?.invalidQuantities || '',
						group: 'invalid-quantities',
					} );
				}
			},
			setQuantity( value: number ) {
				const quantitySelectorContext =
					getContext< QuantitySelectorContext >(
						'woocommerce/add-to-cart-with-options-quantity-selector'
					);
				const inputElement = quantitySelectorContext?.inputElement;
				const isValueNaN = Number.isNaN( inputElement?.valueAsNumber );
				const { draftCartItem } = wooState.productScope;

				if ( draftCartItem ) {
					if ( isValueNaN ) {
						// Modify the value first before setting the real
						// value to ensure that a signal update happens.
						draftCartItem.quantity = NaN;
					}

					draftCartItem.quantity = value;
				}

				// `validateGroupedProductQuantity` reads every child's own
				// quantity, so a change from inside any one child's own
				// scope still needs to trigger it; `groupedProductIds`
				// inherits down from the form's own context regardless of
				// which scope this element sits in.
				const { groupedProductIds } = getContext< Context >();
				if ( groupedProductIds && groupedProductIds.length > 0 ) {
					actions.validateGroupedProductQuantity();
				} else {
					actions.validateQuantity( value );
				}

				if ( inputElement ) {
					dispatchChangeEvent( inputElement );
				}
			},
			addError: ( error: AddToCartError ): string => {
				const { validationErrors } = state;

				validationErrors.push( error );

				return error.code;
			},
			clearErrors: ( group?: string ): void => {
				const { validationErrors } = state;

				if ( group ) {
					const remaining = validationErrors.filter(
						( error ) => error.group !== group
					);
					validationErrors.splice(
						0,
						validationErrors.length,
						...remaining
					);
				} else {
					// Clear all.
					validationErrors.length = 0;
				}
			},
			addToCart: withSyncEvent( function* ( event: SubmitEvent ) {
				event.preventDefault();

				const { isFormValid } = state;

				if ( ! isFormValid ) {
					// Dynamically import the store module first
					yield import( '@woocommerce/stores/store-notices' );

					const { actions: noticeActions } = store< StoreNotices >(
						'woocommerce/store-notices',
						{},
						{
							lock: 'I acknowledge that using a private store means my plugin will inevitably break on the next store release.',
						}
					);

					const { noticeIds, validationErrors } = state;

					// Clear previous notices.
					noticeIds.forEach( ( id ) => {
						noticeActions.removeNotice( id );
					} );
					noticeIds.splice( 0, noticeIds.length );

					// Add new notices and track their IDs.
					const newNoticeIds = validationErrors.map( ( error ) =>
						noticeActions.addNotice( {
							notice: error.message,
							type: 'error',
							dismissible: true,
						} )
					);

					// Store the new IDs in-place.
					noticeIds.push( ...newNoticeIds );

					return;
				}

				const product = wooState.productScope.product;

				if ( ! product ) {
					return;
				}

				if ( product.type === 'grouped' ) {
					yield actions.batchAddToCart();
					return;
				}

				// The payload form: it removes no record, so the form keeps
				// its selection and quantity after adding.
				const scopeName =
					getContext< ProductScopeContext >( 'woocommerce' )
						?.scopeName ?? '_default';
				const record =
					wooState.productScopes[ scopeName ]?.draftCartItem;
				const { variation } = wooState.productScope;

				yield wooActions.addCartItem(
					{
						...record,
						id: product.id,
						variation,
						quantity: state.effectiveQuantity,
					},
					{
						showCartUpdatesNotices: false,
					}
				);
			} ),
		},
	},
	{
		lock: 'I acknowledge that using a private store means my plugin will inevitably break on the next store release.',
	}
);
