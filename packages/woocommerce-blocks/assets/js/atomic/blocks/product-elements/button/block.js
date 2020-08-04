/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import classnames from 'classnames';
import { __, _n, sprintf } from '@wordpress/i18n';
import { useEffect, useRef } from '@wordpress/element';
import { useStoreAddToCart } from '@woocommerce/base-hooks';
import { decodeEntities } from '@wordpress/html-entities';
import { triggerFragmentRefresh } from '@woocommerce/base-utils';
import {
	useInnerBlockLayoutContext,
	useProductDataContext,
} from '@woocommerce/shared-context';
import { withProductDataContext } from '@woocommerce/shared-hocs';

/**
 * Internal dependencies
 */
import './style.scss';

/**
 * Product Button Block Component.
 *
 * @param {Object} props             Incoming props.
 * @param {string} [props.className] CSS Class name for the component.
 * @return {*} The component.
 */
const Block = ( { className } ) => {
	const { parentClassName } = useInnerBlockLayoutContext();
	const { product } = useProductDataContext();

	return (
		<div
			className={ classnames(
				className,
				'wp-block-button',
				'wc-block-components-product-button',
				{
					[ `${ parentClassName }__product-add-to-cart` ]: parentClassName,
				}
			) }
		>
			{ product.id ? (
				<AddToCartButton product={ product } />
			) : (
				<AddToCartButtonPlaceholder />
			) }
		</div>
	);
};

const AddToCartButton = ( { product } ) => {
	const firstMount = useRef( true );

	const {
		id,
		permalink,
		add_to_cart: productCartDetails,
		has_options: hasOptions,
		is_purchasable: isPurchasable,
		is_in_stock: isInStock,
	} = product;

	const { cartQuantity, addingToCart, addToCart } = useStoreAddToCart( id );

	useEffect( () => {
		// Avoid running on first mount when cart quantity is first set.
		if ( firstMount.current ) {
			firstMount.current = false;
			return;
		}
		triggerFragmentRefresh();
	}, [ cartQuantity ] );

	const addedToCart = Number.isFinite( cartQuantity ) && cartQuantity > 0;
	const allowAddToCart = ! hasOptions && isPurchasable && isInStock;
	const buttonAriaLabel = decodeEntities(
		productCartDetails?.description || ''
	);
	const buttonText = addedToCart
		? sprintf(
				// translators: %s number of products in cart.
				_n(
					'%d in cart',
					'%d in cart',
					cartQuantity,
					'woocommerce'
				),
				cartQuantity
		  )
		: decodeEntities(
				productCartDetails?.text ||
					__( 'Add to cart', 'woocommerce' )
		  );

	const ButtonTag = allowAddToCart ? 'button' : 'a';
	const buttonProps = {};

	if ( ! allowAddToCart ) {
		buttonProps.href = permalink;
		buttonProps.rel = 'nofollow';
	} else {
		buttonProps.onClick = () => {
			addToCart();
		};
	}

	return (
		<ButtonTag
			aria-label={ buttonAriaLabel }
			className={ classnames(
				'wp-block-button__link',
				'add_to_cart_button',
				'wc-block-components-product-button__button',
				{
					loading: addingToCart,
					added: addedToCart,
				}
			) }
			disabled={ addingToCart }
			{ ...buttonProps }
		>
			{ buttonText }
		</ButtonTag>
	);
};

const AddToCartButtonPlaceholder = () => {
	return (
		<button
			className={ classnames(
				'wp-block-button__link',
				'add_to_cart_button',
				'wc-block-components-product-button__button',
				'wc-block-components-product-button__button--placeholder'
			) }
			disabled={ true }
		/>
	);
};

Block.propTypes = {
	className: PropTypes.string,
};

export default withProductDataContext( Block );
