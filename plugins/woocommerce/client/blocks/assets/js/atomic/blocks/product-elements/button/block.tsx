/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';
import clsx from 'clsx';
import { __, _n, sprintf } from '@wordpress/i18n';
import {
	useStoreEvents,
	useStoreAddToCart,
} from '@woocommerce/base-context/hooks';
import { useStyleProps } from '@woocommerce/base-hooks';
import { decodeEntities } from '@wordpress/html-entities';
import { CART_URL } from '@woocommerce/block-settings';
import { getSetting } from '@woocommerce/settings';
import {
	useInnerBlockLayoutContext,
	useProductDataContext,
} from '@woocommerce/shared-context';
import { withProductDataContext } from '@woocommerce/shared-hocs';

/**
 * Internal dependencies
 */
import './style.scss';
import type {
	BlockAttributes,
	AddToCartButtonAttributes,
	AddToCartButtonPlaceholderAttributes,
	AddToCartProductDetails,
} from './types';
import { useProductTypeSelector } from '../../../../shared/stores/product-type-template-state';

const getButtonText = ( {
	cartQuantity,
	productCartDetails,
	isDescendantOfAddToCartWithOptions,
}: {
	cartQuantity: number;
	productCartDetails: AddToCartProductDetails;
	isDescendantOfAddToCartWithOptions: boolean | undefined;
} ) => {
	const addedToCart = Number.isFinite( cartQuantity ) && cartQuantity > 0;

	if ( addedToCart ) {
		return sprintf(
			/* translators: %s number of products in cart. */
			_n( '%d in cart', '%d in cart', cartQuantity, 'woocommerce' ),
			cartQuantity
		);
	}

	if (
		isDescendantOfAddToCartWithOptions &&
		productCartDetails?.single_text
	) {
		return productCartDetails?.single_text;
	}

	return productCartDetails?.text || __( 'Add to cart', 'woocommerce' );
};

const AddToCartButton = ( {
	product,
	isDescendantOfAddToCartWithOptions,
	className,
	style,
}: AddToCartButtonAttributes ): JSX.Element => {
	const {
		id,
		permalink,
		add_to_cart: productCartDetails,
		has_options: hasOptions,
		is_purchasable: isPurchasable,
		is_in_stock: isInStock,
	} = product;
	const { dispatchStoreEvent } = useStoreEvents();
	const { cartQuantity, addingToCart, addToCart } = useStoreAddToCart( id );
	const addedToCart = Number.isFinite( cartQuantity ) && cartQuantity > 0;
	const allowAddToCart = ! hasOptions && isPurchasable && isInStock;
	const buttonAriaLabel = decodeEntities(
		productCartDetails?.description || ''
	);
	const buttonText = getButtonText( {
		cartQuantity,
		productCartDetails,
		isDescendantOfAddToCartWithOptions,
	} );

	const ButtonTag = allowAddToCart ? 'button' : 'a';
	const buttonProps = {} as HTMLAnchorElement & { onClick: () => void };

	if ( ! allowAddToCart ) {
		buttonProps.href = permalink;
		buttonProps.rel = 'nofollow';
		buttonProps.onClick = () => {
			dispatchStoreEvent( 'product-view-link', {
				product,
			} );
		};
	} else {
		buttonProps.onClick = async () => {
			await addToCart();
			dispatchStoreEvent( 'cart-add-item', {
				product,
			} );
			// redirect to cart if the setting to redirect to the cart page
			// on cart add item is enabled
			const { cartRedirectAfterAdd }: { cartRedirectAfterAdd: boolean } =
				getSetting( 'productsSettings' );
			if ( cartRedirectAfterAdd ) {
				window.location.href = CART_URL;
			}
		};
	}

	return (
		<ButtonTag
			{ ...buttonProps }
			aria-label={ buttonAriaLabel }
			disabled={ addingToCart }
			className={ clsx(
				className,
				'wp-block-button__link',
				'wp-element-button',
				'add_to_cart_button',
				'wc-block-components-product-button__button',
				{
					loading: addingToCart,
					added: addedToCart,
				}
			) }
			style={ style }
		>
			{ buttonText }
		</ButtonTag>
	);
};

const LoadingAddToCartButton = ( {
	className,
	style,
}: {
	className: string;
	style: React.CSSProperties;
} ): JSX.Element => {
	return (
		<button
			className={ clsx(
				'wp-block-button__link',
				'wp-element-button',
				'add_to_cart_button',
				'wc-block-components-product-button__button',
				'wc-block-components-product-button__button--placeholder',
				className
			) }
			style={ style }
			disabled={ true }
		>
			{ __( 'Add to cart', 'woocommerce' ) }
		</button>
	);
};

const AddToCartButtonPlaceholder = ( {
	className,
	style,
	blockClientId,
}: AddToCartButtonPlaceholderAttributes ): JSX.Element => {
	const {
		current: currentProductType,
		registerListener,
		unregisterListener,
	} = useProductTypeSelector();

	useEffect( () => {
		if ( blockClientId ) {
			registerListener( blockClientId );
			return () => {
				unregisterListener( blockClientId );
			};
		}
	}, [ blockClientId, registerListener, unregisterListener ] );

	const buttonText =
		currentProductType?.slug === 'external'
			? __( 'Buy product', 'woocommerce' )
			: __( 'Add to cart', 'woocommerce' );

	return (
		<button
			className={ clsx(
				'wp-block-button__link',
				'wp-element-button',
				'add_to_cart_button',
				'wc-block-components-product-button__button',
				className
			) }
			style={ style }
			disabled={ true }
		>
			{ buttonText }
		</button>
	);
};

export const Block = ( props: BlockAttributes ): JSX.Element => {
	const { className, textAlign, blockClientId } = props;
	const styleProps = useStyleProps( props );
	const { parentClassName } = useInnerBlockLayoutContext();
	const { isLoading, product } = useProductDataContext();

	return (
		<div
			className={ clsx(
				className,
				'wp-block-button',
				'wc-block-components-product-button',
				{
					[ `${ parentClassName }__product-add-to-cart` ]:
						parentClassName,
					[ `align-${ textAlign }` ]: textAlign,
				}
			) }
		>
			{ isLoading ? (
				<LoadingAddToCartButton
					className={ styleProps.className }
					style={ styleProps.style }
				/>
			) : (
				<>
					{ product.id ? (
						<AddToCartButton
							product={ product }
							style={ styleProps.style }
							className={ styleProps.className }
							isDescendantOfAddToCartWithOptions={
								props[
									'woocommerce/isDescendantOfAddToCartWithOptions'
								]
							}
						/>
					) : (
						<AddToCartButtonPlaceholder
							style={ styleProps.style }
							className={ styleProps.className }
							isLoading={ isLoading }
							blockClientId={ blockClientId }
						/>
					) }
				</>
			) }
		</div>
	);
};

export default withProductDataContext( Block );
