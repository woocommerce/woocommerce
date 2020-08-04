/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import Button from '@woocommerce/base-components/button';
import { Icon, done as doneIcon } from '@woocommerce/icons';
import { useState } from '@wordpress/element';
import { useAddToCartFormContext } from '@woocommerce/base-context';

/**
 * Add to Cart Form Button Component.
 */
const AddToCartButton = () => {
	const {
		showFormElements,
		product,
		quantityInCart,
		formDisabled,
		formSubmitting,
		onSubmit,
	} = useAddToCartFormContext();

	const {
		is_purchasable: isPurchasable = true,
		has_options: hasOptions,
		add_to_cart: addToCartButtonData = {
			url: '',
			text: '',
		},
	} = product;

	// If we are showing form elements, OR if the product has no additional form options, we can show
	// a functional direct add to cart button, provided that the product is purchasable.
	// No link is required to the full form under these circumstances.
	if ( ( showFormElements || ! hasOptions ) && isPurchasable ) {
		return (
			<ButtonComponent
				className="wc-block-components-product-add-to-cart-button"
				quantityInCart={ quantityInCart }
				disabled={ formDisabled }
				loading={ formSubmitting }
				onClick={ onSubmit }
			/>
		);
	}

	return (
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
	loading,
	disabled,
	onClick,
} ) => {
	const [ wasClicked, setWasClicked ] = useState( false );

	return (
		<Button
			className={ className }
			disabled={ disabled }
			showSpinner={ loading }
			onClick={ () => {
				onClick();
				setWasClicked( true );
			} }
		>
			{ quantityInCart > 0
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
			{ wasClicked && (
				<Icon
					srcElement={ doneIcon }
					alt={ __( 'Done', 'woocommerce' ) }
				/>
			) }
		</Button>
	);
};

export default AddToCartButton;
