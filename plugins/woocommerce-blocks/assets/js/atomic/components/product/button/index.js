/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import classnames from 'classnames';
import { _n, sprintf } from '@wordpress/i18n';
import { useEffect, useRef } from '@wordpress/element';
import { useStoreAddToCart } from '@woocommerce/base-hooks';
import { useProductLayoutContext } from '@woocommerce/base-context';
import { decodeEntities } from '@wordpress/html-entities';

const Event = window.Event || null;

const ProductButton = ( { product, className } ) => {
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
	const { layoutStyleClassPrefix } = useProductLayoutContext();
	const addedToCart = Number.isFinite( cartQuantity ) && cartQuantity > 0;
	const firstMount = useRef( true );
	const getButtonText = () => {
		if ( addedToCart ) {
			return sprintf(
				// translators: %s number of products in cart.
				_n(
					'%d in cart',
					'%d in cart',
					cartQuantity,
					'woo-gutenberg-products-block'
				),
				cartQuantity
			);
		}
		return decodeEntities( productCartDetails.text );
	};

	// This is a hack to trigger cart updates till we migrate to block based cart
	// that relies on the store, see
	// https://github.com/woocommerce/woocommerce-gutenberg-products-block/issues/1247
	useEffect( () => {
		if ( firstMount.current ) {
			firstMount.current = false;
			return;
		}
		// In IE, Event is an object and can't be instantiated with `new Event()`.
		if ( typeof Event === 'function' ) {
			const event = new Event( 'wc_fragment_refresh', {
				bubbles: true,
				cancelable: true,
			} );
			document.body.dispatchEvent( event );
		} else {
			const event = document.createEvent( 'Event' );
			event.initEvent( 'wc_fragment_refresh', true, true );
			document.body.dispatchEvent( event );
		}
	}, [ cartQuantity ] );

	const wrapperClasses = classnames(
		className,
		`${ layoutStyleClassPrefix }__product-add-to-cart`,
		'wp-block-button'
	);

	const buttonClasses = classnames(
		'wp-block-button__link',
		'add_to_cart_button',
		{
			loading: addingToCart,
			added: addedToCart,
		}
	);

	if ( Object.keys( product ).length === 0 || cartIsLoading ) {
		return (
			<div className={ wrapperClasses }>
				<button className={ buttonClasses } disabled={ true } />
			</div>
		);
	}
	const allowAddToCart = ! hasOptions && isPurchasable && isInStock;
	return (
		<div className={ wrapperClasses }>
			{ allowAddToCart ? (
				<button
					onClick={ addToCart }
					aria-label={ decodeEntities(
						productCartDetails.description
					) }
					className={ buttonClasses }
					disabled={ addingToCart }
				>
					{ getButtonText() }
				</button>
			) : (
				<a
					href={ permalink }
					aria-label={ decodeEntities(
						productCartDetails.description
					) }
					className={ buttonClasses }
					rel="nofollow"
				>
					{ getButtonText() }
				</a>
			) }
		</div>
	);
};

ProductButton.propTypes = {
	className: PropTypes.string,
	product: PropTypes.object.isRequired,
};

export default ProductButton;
