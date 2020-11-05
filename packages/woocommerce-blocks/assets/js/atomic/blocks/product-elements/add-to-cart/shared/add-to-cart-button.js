/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import Button from '@woocommerce/base-components/button';
import { Icon, done as doneIcon } from '@woocommerce/icons';
import { useState, useEffect } from '@wordpress/element';
import { useAddToCartFormContext } from '@woocommerce/base-context';
import { useStoreAddToCart } from '@woocommerce/base-hooks';

/**
 * Add to Cart Form Button Component.
 */
const AddToCartButton = () => {
	const {
		showFormElements,
		productIsPurchasable,
		productHasOptions,
		product,
		productType,
		isDisabled,
		isProcessing,
		eventRegistration,
		hasError,
		dispatchActions,
	} = useAddToCartFormContext();
	const { cartQuantity } = useStoreAddToCart( product.id || 0 );
	const [ addedToCart, setAddedToCart ] = useState( false );
	const addToCartButtonData = product.add_to_cart || {
		url: '',
		text: '',
	};

	// Subscribe to emitter for after processing.
	useEffect( () => {
		const onSuccess = () => {
			if ( ! hasError ) {
				setAddedToCart( true );
			}
			return true;
		};
		const unsubscribeProcessing = eventRegistration.onAddToCartAfterProcessingWithSuccess(
			onSuccess,
			0
		);
		return () => {
			unsubscribeProcessing();
		};
	}, [ eventRegistration, hasError ] );

	/**
	 * We can show a real button if we are:
	 *
	 *  	a) Showing a full add to cart form.
	 * 		b) The product doesn't have options and can therefore be added directly to the cart.
	 * 		c) The product is purchasable.
	 *
	 * Otherwise we show a link instead.
	 */
	const showButton =
		( showFormElements ||
			( ! productHasOptions && productType === 'simple' ) ) &&
		productIsPurchasable;

	return showButton ? (
		<ButtonComponent
			className="wc-block-components-product-add-to-cart-button"
			quantityInCart={ cartQuantity }
			isDisabled={ isDisabled }
			isProcessing={ isProcessing }
			isDone={ addedToCart }
			onClick={ () => dispatchActions.submitForm() }
		/>
	) : (
		<LinkComponent
			className="wc-block-components-product-add-to-cart-button"
			href={ addToCartButtonData.url }
			text={
				addToCartButtonData.text ||
				__( 'View Product', 'woocommerce' )
			}
		/>
	);
};

/**
 * Button for non-purchasable products.
 */
const LinkComponent = ( { className, href, text } ) => {
	return (
		<Button className={ className } href={ href } rel="nofollow">
			{ text }
		</Button>
	);
};

/**
 * Button for purchasable products.
 */
const ButtonComponent = ( {
	className,
	quantityInCart,
	isProcessing,
	isDisabled,
	isDone,
	onClick,
} ) => {
	return (
		<Button
			className={ className }
			disabled={ isDisabled }
			showSpinner={ isProcessing }
			onClick={ onClick }
		>
			{ isDone && quantityInCart > 0
				? sprintf(
						// translators: %s number of products in cart.
						_n(
							'%d in cart',
							'%d in cart',
							quantityInCart,
							'woocommerce'
						),
						quantityInCart
				  )
				: __( 'Add to cart', 'woocommerce' ) }
			{ !! isDone && (
				<Icon
					srcElement={ doneIcon }
					alt={ __( 'Done', 'woocommerce' ) }
				/>
			) }
		</Button>
	);
};

export default AddToCartButton;
