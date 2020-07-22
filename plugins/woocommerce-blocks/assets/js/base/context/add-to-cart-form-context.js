/**
 * External dependencies
 */
import {
	createContext,
	useContext,
	useState,
	useCallback,
} from '@wordpress/element';
import {
	useStoreAddToCart,
	useTriggerFragmentRefresh,
} from '@woocommerce/base-hooks';

/**
 * @typedef {import('@woocommerce/type-defs/contexts').AddToCartFormContext} AddToCartFormContext
 */

const AddToCartFormContext = createContext( {
	product: {},
	productId: 0,
	variationId: 0,
	variationData: {},
	cartItemData: {},
	quantity: 1,
	minQuantity: 1,
	maxQuantity: 99,
	quantityInCart: 0,
	setQuantity: ( quantity ) => void { quantity },
	setVariationId: ( variationId ) => void { variationId },
	setVariationData: ( variationData ) => void { variationData },
	setCartItemData: ( cartItemData ) => void { cartItemData },
	showFormElements: false,
	formInitialized: false,
	formDisabled: true,
	formSubmitting: false,
	onChange: () => void null,
	onSubmit: () => void null,
	onSuccess: () => void null,
	onFail: () => void null,
} );

/**
 * @return {AddToCartFormContext} Returns the add to cart form context value.
 */
export const useAddToCartFormContext = () => {
	return useContext( AddToCartFormContext );
};

/**
 * Provides an interface for blocks to control the add to cart form for a product.
 *
 * @param {Object} props                     Incoming props for the provider.
 * @param {*}      props.children            The children being wrapped.
 * @param {Object} [props.product]           The product for which the form belongs to.
 * @param {boolean} [props.showFormElements] Should form elements be shown.
 */
export const AddToCartFormContextProvider = ( {
	children,
	product: productProp,
	showFormElements,
} ) => {
	const product = productProp || {};
	const productId = product.id || 0;
	const [ variationId, setVariationId ] = useState( 0 );
	const [ variationData, setVariationData ] = useState( {} );
	const [ cartItemData, setCartItemData ] = useState( {} );
	const [ quantity, setQuantity ] = useState( 1 );
	const {
		addToCart: storeAddToCart,
		addingToCart: formSubmitting,
		cartQuantity: quantityInCart,
		cartIsLoading,
	} = useStoreAddToCart( productId );

	// This will ensure any add to cart events update legacy fragments using jQuery.
	useTriggerFragmentRefresh( quantityInCart );

	/**
	 * @todo Introduce Validation Emitter for the Add to Cart Form
	 *
	 * The add to cart form may have several inner form elements which need to run validation and
	 * change whether or not the form can be submitted. They may also need to show errors and
	 * validation notices.
	 */
	const formInitialized = ! cartIsLoading && productId > 0;
	const formDisabled =
		formSubmitting ||
		! formInitialized ||
		! productIsPurchasable( product );

	// Events.
	const onSubmit = useCallback( () => {
		/**
		 * @todo Surface add to cart errors in the single product block.
		 *
		 * If the addToCart function within useStoreAddToCart fails, a notice should be shown on the product page.
		 */
		storeAddToCart( quantity );
	}, [ storeAddToCart, quantity ] );

	/**
	 * @todo Add Event Callbacks to the Add to Cart Form.
	 *
	 * - onChange should trigger when a form element changes, so for example, a variation picker could indicate that it's ready.
	 * - onSuccess should trigger after a successful add to cart. This could be used to reset form elements, do a redirect, or show something to the user.
	 * - onFail should trigger when adding to cart fails. Form elements might show extra notices or reset. A fallback might be to redirect to the core product page in case of incompatibilities.
	 */
	const onChange = useCallback( () => {}, [] );
	const onSuccess = useCallback( () => {}, [] );
	const onFail = useCallback( () => {}, [] );

	/**
	 * @type {AddToCartFormContext}
	 */
	const contextValue = {
		product,
		productId,
		variationId,
		variationData,
		cartItemData,
		quantity,
		minQuantity: 1,
		maxQuantity: product.quantity_limit || 99,
		quantityInCart,
		setQuantity,
		setVariationId,
		setVariationData,
		setCartItemData,
		showFormElements,
		formInitialized,
		formDisabled,
		formSubmitting,
		onChange,
		onSubmit,
		onSuccess,
		onFail,
	};

	return (
		<AddToCartFormContext.Provider value={ contextValue }>
			{ children }
		</AddToCartFormContext.Provider>
	);
};

/**
 * Check a product object to see if it can be purchased.
 *
 * @param {Object} product Product object.
 */
const productIsPurchasable = ( product ) => {
	const { is_purchasable: isPurchasable = false } = product;

	return isPurchasable;
};
