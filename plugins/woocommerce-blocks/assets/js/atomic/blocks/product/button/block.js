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

/**
 * Product Button Block Component.
 *
 * @param {Object} props             Incoming props.
 * @param {string} [props.className] CSS Class name for the component.
 * @param {Object} [props.product]   Optional product object. Product from context will be used if
 *                                   this is not provided.
 * @return {*} The component.
 */
const ProductButton = ( { className, ...props } ) => {
	const productDataContext = useProductDataContext();
	const product = props.product || productDataContext.product;

	const { layoutStyleClassPrefix } = useInnerBlockLayoutContext();
	const componentClass = `${ layoutStyleClassPrefix }__product-add-to-cart`;

	return (
		<div
			className={ classnames(
				className,
				componentClass,
				'wp-block-button',
				{
					'is-loading': ! product,
				}
			) }
		>
			{ product ? (
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

	const {
		cartQuantity,
		addingToCart,
		cartIsLoading,
		addToCart,
	} = useStoreAddToCart( id );

	useEffect( () => {
		// Avoid running on first mount when cart quantity is first set.
		if ( firstMount.current ) {
			firstMount.current = false;
			return;
		}
		triggerFragmentRefresh();
	}, [ cartQuantity ] );

	if ( cartIsLoading ) {
		return <AddToCartButtonPlaceholder />;
	}

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
					'woo-gutenberg-products-block'
				),
				cartQuantity
		  )
		: decodeEntities(
				productCartDetails?.text ||
					__( 'Add to cart', 'woo-gutenberg-products-block' )
		  );

	if ( ! allowAddToCart ) {
		return (
			<a
				href={ permalink }
				aria-label={ buttonAriaLabel }
				className={ classnames(
					'wp-block-button__link',
					'add_to_cart_button',
					{
						loading: addingToCart,
						added: addedToCart,
					}
				) }
				rel="nofollow"
			>
				{ buttonText }
			</a>
		);
	}

	return (
		<button
			onClick={ addToCart }
			aria-label={ buttonAriaLabel }
			className={ classnames(
				'wp-block-button__link',
				'add_to_cart_button',
				{
					loading: addingToCart,
					added: addedToCart,
				}
			) }
			disabled={ addingToCart }
		>
			{ buttonText }
		</button>
	);
};

const AddToCartButtonPlaceholder = () => {
	return (
		<button
			className={ classnames(
				'wp-block-button__link',
				'add_to_cart_button'
			) }
			disabled={ true }
		/>
	);
};

ProductButton.propTypes = {
	className: PropTypes.string,
	product: PropTypes.object,
};

export default ProductButton;
