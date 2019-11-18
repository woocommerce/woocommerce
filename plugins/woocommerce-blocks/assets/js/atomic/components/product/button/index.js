/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import classnames from 'classnames';
import { __, sprintf } from '@wordpress/i18n';
import {
	useMemo,
	useCallback,
	useState,
	useEffect,
	useRef,
} from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import { find } from 'lodash';
import { useCollection } from '@woocommerce/base-hooks';
import { COLLECTIONS_STORE_KEY as storeKey } from '@woocommerce/block-data';
import { useProductLayoutContext } from '@woocommerce/base-context/product-layout-context';

/**
 * A custom hook for exposing cart related data for a given product id and an
 * action for adding a single quantity of the product _to_ the cart.
 *
 * Currently this is internal only to the ProductButton component until we have
 * a clearer idea of the pattern that should emerge for a cart hook.
 *
 * @param {number} productId  The product id for the product connection to the
 *                            cart.
 *
 * @return {Object} Returns an object with the following properties:
 *    @type {number}   cartQuantity  The quantity of the product currently in
 *                                   the cart.
 *    @type {bool}     addingToCart  Whether the product is currently being
 *                                   added to the cart (true).
 *    @type {bool}     cartIsLoading Whether the cart is being loaded.
 *    @type {Function} addToCart     An action dispatcher for adding a single
 *                                   quantity of the product to the cart.
 *                                   Receives no arguments, it operates on the
 *                                   current product.
 */
const useAddToCart = ( productId ) => {
	const { results: cartResults, isLoading: cartIsLoading } = useCollection( {
		namespace: '/wc/store',
		resourceName: 'cart/items',
	} );
	const currentCartResults = useRef( null );
	const { __experimentalPersistItemToCollection } = useDispatch( storeKey );
	const cartQuantity = useMemo( () => {
		const productItem = find( cartResults, { id: productId } );
		return productItem ? productItem.quantity : 0;
	}, [ cartResults, productId ] );
	const [ addingToCart, setAddingToCart ] = useState( false );
	const addToCart = useCallback( () => {
		setAddingToCart( true );
		// exclude this item from the cartResults for adding to the new
		// collection (so it's updated correctly!)
		const collection = cartResults.filter( ( cartItem ) => {
			return cartItem.id !== productId;
		} );
		__experimentalPersistItemToCollection(
			'/wc/store',
			'cart/items',
			collection,
			{ id: productId, quantity: 1 }
		);
	}, [ productId, cartResults ] );
	useEffect( () => {
		if ( currentCartResults.current !== cartResults ) {
			if ( addingToCart ) {
				setAddingToCart( false );
			}
			currentCartResults.current = cartResults;
		}
	}, [ cartResults, addingToCart ] );
	return {
		cartQuantity,
		addingToCart,
		cartIsLoading,
		addToCart,
	};
};

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
	} = useAddToCart( id );
	const { layoutStyleClassPrefix } = useProductLayoutContext();
	const addedToCart = cartQuantity > 0;
	const getButtonText = () => {
		if ( Number.isFinite( cartQuantity ) && addedToCart ) {
			return sprintf(
				__( '%d in cart', 'woo-gutenberg-products-block' ),
				cartQuantity
			);
		}
		return productCartDetails.text;
	};
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
					aria-label={ productCartDetails.description }
					className={ buttonClasses }
					disabled={ addingToCart }
				>
					{ getButtonText() }
				</button>
			) : (
				<a
					href={ permalink }
					aria-label={ productCartDetails.description }
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
