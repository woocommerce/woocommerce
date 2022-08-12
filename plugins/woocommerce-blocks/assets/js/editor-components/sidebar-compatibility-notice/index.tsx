/**
 * External dependencies
 */
import { Notice, ExternalLink } from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './editor.scss';
import { useCompatibilityNotice } from './use-compatibility-notice';

export const CartCheckoutSidebarCompatibilityNotice = ( {
	block,
}: {
	block: 'cart' | 'checkout';
} ) => {
	const [ isVisible, dismissNotice ] = useCompatibilityNotice( block );

	const noticeText = createInterpolateElement(
		__(
			'The Cart & Checkout Blocks are a beta feature to optimize for faster checkout. To make sure this feature is right for your store, <a>review the list of compatible extensions</a>.',
			'woo-gutenberg-products-block'
		),
		{
			a: (
				// Suppress the warning as this <a> will be interpolated into the string with content.
				// eslint-disable-next-line jsx-a11y/anchor-has-content
				<ExternalLink href="https://woocommerce.com/document/cart-checkout-blocks-support-status/#section-3" />
			),
		}
	);

	return (
		<div
			className="wc-blocks-sidebar-compatibility-notice"
			style={ { display: isVisible ? 'block' : 'none' } }
		>
			<Notice
				onRemove={ dismissNotice }
				className={ 'wc-blocks-sidebar-compatibility-notice__notice' }
			>
				{ noticeText }
			</Notice>
		</div>
	);
};
