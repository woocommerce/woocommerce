/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Notice } from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';
import { getAdminLink } from '@woocommerce/settings';
import { CART_PAGE_ID, CHECKOUT_PAGE_ID } from '@woocommerce/block-settings';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import './editor.scss';

/**
 * Shows a notice about setting the default Cart and Checkout pages..
 *
 */
export function DefaultNotice( props: {
	block: 'cart' | 'checkout';
} ): JSX.Element | null {
	const idToCheck = props.block === 'cart' ? CART_PAGE_ID : CHECKOUT_PAGE_ID;
	const currentPostId = useSelect( ( select ) => {
		return select( 'core/editor' ).getCurrentPostId();
	} );

	return currentPostId !== idToCheck ? (
		<Notice
			className="wc-block-cart__page-notice"
			isDismissible={ false }
			status="warning"
		>
			{ createInterpolateElement(
				sprintf(
					/* translators: %s is the block name. It will be cart or checkout. */
					__(
						'If you would like to use this block as your default %s you must update your <a>page settings in WooCommerce</a>.',
						'woo-gutenberg-products-block'
					),
					props.block
				),
				{
					a: (
						// eslint-disable-next-line jsx-a11y/anchor-has-content
						<a
							href={ getAdminLink(
								'admin.php?page=wc-settings&tab=advanced'
							) }
							target="_blank"
							rel="noopener noreferrer"
						/>
					),
				}
			) }
		</Notice>
	) : null;
}
