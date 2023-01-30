/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { SHOP_URL } from '@woocommerce/block-settings';
import { Icon, cart } from '@woocommerce/icons';

const EmptyCart = () => {
	return (
		<div className="wc-block-checkout-empty">
			<Icon
				className="wc-block-checkout-empty__image"
				alt=""
				srcElement={ cart }
				size={ 100 }
			/>
			<strong className="wc-block-checkout-empty__title">
				{ __( 'Your cart is empty!', 'woo-gutenberg-products-block' ) }
			</strong>
			<p className="wc-block-checkout-empty__description">
				{ __(
					"Checkout is not available whilst your cart is empty—please take a look through our store and come back when you're ready to place an order.",
					'woo-gutenberg-products-block'
				) }
			</p>
			<span className="wp-block-button">
				<a href={ SHOP_URL } className="wp-block-button__link">
					{ __( 'Browse store', 'woo-gutenberg-products-block' ) }
				</a>
			</span>
		</div>
	);
};

export default EmptyCart;
